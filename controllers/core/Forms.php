<?php

function postForm($DB)
{
    try {
        $array_data = array();
        $table = trim(customDecrypt($_POST['class']));

        foreach ($_POST['data'] as $data) {
            if (!isset($data['name'])) {
                continue;
            }

            if (strpos($data['name'], '__') !== false) {
                $table_key = explode('__', $data['name'])[0];
                $column = explode('__', $data['name'])[1];
                $array_data[$table_key][$column] = $data['value'];
            } else if (stripos($data['name'], 'csrf') !== false) {
                $csrf = $data['value'];
            }
        }

        if (!isset($csrf) || !is_csrf_valid(customDecrypt($csrf))) {
            throw new Exception($GLOBALS['language']['The form is forged']);
        }

        $filteredData = array_filter($array_data, function ($key) use ($table) {
            return $key != $table;
        }, ARRAY_FILTER_USE_KEY);

        $restData = array_values(array_diff_key($array_data, $filteredData))[0];

        $user_role = $_SESSION['user']['role'] ?? null;
        $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
        $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

        // List of tables that have tracking columns
        $tables_with_tracking = ['users', 'reeducation_dossiers', 'patient', 'rdv'];
        if (in_array($table, $tables_with_tracking)) {
            $restData['created_by'] = $_SESSION['user']['id'];
        }

        // Special Logic for Creating Users
        if ($table === 'users' && !isset($_POST['update'])) {

            $restData['must_change_password'] = 1;

            $new_password = generateRandomPassword();
            $restData['password'] = sha1($new_password);
            $fullName = $restData['first_name'] . ' ' . $restData['last_name'];
            $subject = 'Bienvenue sur The Doctor App - Vos informations de connexion';
            $body = "<p>Bonjour {$fullName},</p><p>Un compte a été créé pour vous. Votre mot de passe temporaire est : <strong>{$new_password}</strong></p>";

            $emailSent = sendEmail($restData['email'], $fullName, $subject, $body);
            if ($emailSent !== true) {
                throw new Exception("L'e-mail de bienvenue n'a pas pu être envoyé. Erreur: " . $emailSent);
            }

            if (!$is_super_admin && $user_role === 'admin') {
                $restData['cabinet_id'] = $user_cabinet_id;

                if (isset($restData['role']) && $restData['role'] === 'admin') {
                    throw new Exception("Vous n'avez pas la permission de créer des comptes administrateur.");
                }

                $admin_data = $DB->select("SELECT specialty_id, commune_id, tickets_day, travel_hours, is_opened, image1, image2, image3, facebook, instagram, description FROM users WHERE id = {$_SESSION['user']['id']}")[0];
                if ($admin_data) {
                    $restData = array_merge($admin_data, $restData);
                }
            }
        }

        $DB->table = $table;
        $DB->data = $restData;
        $last_id = $DB->insert();
        $inserted = is_numeric($last_id) && $last_id > 0;

        if (!$inserted) {
            throw new Exception($DB->error ?? 'Main database insertion failed.');
        }

        // Handle sub-tables if any
        if (is_array($filteredData) && !empty($filteredData) && $inserted) {
            $unique_id = ((substr($table, -1) === 's') ? substr($table, 0, -1) : $table) . '_id';
            foreach ($filteredData as $table_name => $data) {
                $DB->table = $table_name;

                if (isset($data['multi'])) {
                    unset($data['multi']);
                    $data = array_map(function ($item) use ($unique_id, $last_id) {
                        return array_merge($item, [$unique_id => $last_id]);
                    }, $data);
                    $DB->multi = true;
                } else {
                    $data = array_merge($data, array("$unique_id" => $last_id));
                }

                $DB->data = $data;
                $DB->insert();
            }
        }

        echo json_encode(["state" => "true", "id" => $last_id, "message" => $GLOBALS['language']['Added successfully']]);

    } catch (Throwable $th) {
        http_response_code(500);
        echo json_encode([
            "state" => "false",
            "message" => "A precise error occurred.",
            "error_details" => $th->getMessage()
        ]);
    } finally {
        $DB = null;
    }
}

function updatForm($DB)
{

    if (isset($_POST['class']) && !empty($_POST['class']) && isset($_POST['object']) && !empty($_POST['object'])) {
        $table = trim(customDecrypt($_POST['class']));
        $whereCondition = json_decode(customDecrypt($_POST['object']));
        $unique_val = isset($_POST['codex_id']) ? $_POST['codex_id'] : $whereCondition->val;

        $array_data = array();
        foreach ($_POST['data'] as $data) {
            if (strpos($data['name'], '__') !== false) {
                $table_key = explode('__', $data['name'])[0];
                $column = explode('__', $data['name'])[1];
                if (stripos($column, 'password') !== false && !empty($data['value'])) {
                    $array_data[$table_key][$column] = sha1($data['value']);
                } else if (stripos($column, 'password') === false) {
                    $array_data[$table_key][$column] = $data['value'];
                }
            } else if (stripos($data['name'], 'csrf') !== false) {
                $csrf = $data['value'];
                unset($data['csrf']);
            }
        }

        if (isset($csrf)) {
            $csrf = customDecrypt($csrf);
            if (!is_csrf_valid($csrf)) {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }

        $filteredData = array_filter($array_data, function ($key) use ($table) {
            return $key != $table;
        }, ARRAY_FILTER_USE_KEY);

        $restData = array_diff_key($array_data, $filteredData);
        $restData = array_values($restData)[0];
        $restData = array_merge($restData, array("modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['id']));

        $DB->table = $table;
        $DB->data = $restData;
        $DB->where = $whereCondition->column . ' = ' . $unique_val;

        $updated = true && $DB->update();

        if (is_array($filteredData) && !empty($filteredData)) {
            $unique_id = ((substr($table, -1) === 's') ? substr($table, 0, -1) : $table) . '_id';

            foreach ($filteredData as $table_name => $data) {
                $DB->table = $table_name;
                $DB->data = $data;
                $DB->where = "$unique_id = $unique_val";
                $updated = $updated && $DB->update();
            }
        }

        if ($updated) {
            $response = ["state" => "true", "message" => $GLOBALS['language']['Edited successfully']];

            if ($table === 'users' && $unique_val == $_SESSION['user']['id'] && isset($restData['image1'])) {
                $_SESSION['user']['image1'] = $restData['image1'];
                $response['new_image_url'] = $restData['image1'];
            }
            echo json_encode($response);
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
        }


    } else {
        echo json_encode(["state" => "false", "message" => "Class OR Object not exist"]);
    }
    $DB = null;
}

function checkUnique($DB)
{
    if (isset($_POST['class']) && isset($_POST['name']) && isset($_POST['value']) && !empty($_POST['class']) && !empty($_POST['name']) && !empty($_POST['value'])) {
        $table = trim(customDecrypt($_POST['class']));
        $DB->table = $table;
        $DB->field = $_POST['name'];
        $DB->value = $_POST['value'];

        $unique = $DB->validateField();
        $DB = null;
        if (!$unique) {
            echo json_encode(true);
        } else {
            echo json_encode(false);
        }
    } else {
        echo json_encode(false);
    }
}
?>