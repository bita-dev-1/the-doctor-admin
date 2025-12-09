<?php

function signUp($DB)
{
    // ... (الكود السابق كما هو) ...
    $array_data = array();
    foreach ($_POST['data'] as $data) {
        if (stripos($data['name'], 'password') !== false) {
            $array_data[$data['name']] = sha1($data['value']);
        } else {
            $array_data[$data['name']] = $data['value'];
        }
    }
    $csrf_token = customDecrypt($array_data['csrf']);
    unset($array_data['csrf']);
    if (!is_csrf_valid($csrf_token)) {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }
    $DB->table = 'users';
    $DB->data = $array_data;
    $inserted = $DB->insert();
    if ($inserted) {
        // MODIFIED: Added JOIN to fetch kine_enabled status immediately after signup
        $sql = 'SELECT users.id, users.role, users.cabinet_id, users.first_name, users.last_name, users.image1, cabinets.kine_enabled 
                FROM `users` 
                LEFT JOIN cabinets ON users.cabinet_id = cabinets.id 
                WHERE users.id = ' . $inserted;
        $user_data = $DB->select($sql);
        $_SESSION['user'] = $user_data[0];
        $DB = null;
        echo json_encode(["state" => "true", "id" => $inserted, "message" => $GLOBALS['language']['Added successfully']]);
    } else {
        echo json_encode(["state" => "false", "message" => $inserted]);
    }
}

function login($DB)
{
    $csrf_token = customDecrypt($_POST['csrf']);
    if (!is_csrf_valid($csrf_token)) {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }
    $email = $_POST['email'];
    $password = sha1($_POST['password']);

    // MODIFIED: Added LEFT JOIN to fetch 'kine_enabled' from cabinets table
    $sql = "SELECT users.id, users.role, users.cabinet_id, users.first_name, users.last_name, users.image1, users.must_change_password, cabinets.kine_enabled 
            FROM `users` 
            LEFT JOIN cabinets ON users.cabinet_id = cabinets.id 
            WHERE users.deleted = 0 AND users.status = 'active' AND users.email = '" . $email . "' AND users.password = '" . $password . "'";

    $user_data = $DB->select($sql);

    $DB = null;
    if (count($user_data)) {
        $_SESSION['user'] = $user_data[0];

        if ($user_data[0]['must_change_password'] == 1) {
            echo json_encode(array("state" => "redirect", "url" => SITE_URL . "/force_change_password"));
        } else {
            echo json_encode(array("state" => "true", "message" => $GLOBALS['language']['You are logged in successfully']));
        }
    } else {
        echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Incorrect username or password!!']));
    }
}

function logout()
{
    session_destroy();
    unset($_SESSION['user']);
    echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Signed out']]);
}

function changePassword($DB)
{
    // ... (باقي الدوال كما هي بدون تغيير) ...
    $password = sha1($_POST['password']);
    $sql = 'SELECT id FROM `users` WHERE (`password` ="' . $password . '") AND id = ' . $_SESSION['user']['id'];
    $user_data = $DB->select($sql);

    if (count($user_data)) {
        $newpassword = $_POST['new-password'];
        $ConNewpassword = $_POST['confirm-new-password'];

        if ($newpassword === $ConNewpassword) {
            $DB->table = 'users';
            $DB->data = array('password' => sha1($newpassword), 'must_change_password' => 0);
            $DB->where = 'id = ' . $_SESSION['user']['id'];
            $updated = $DB->update();

            if ($updated) {
                $_SESSION['user']['must_change_password'] = 0;
            }

            $DB = null;
            if ($updated)
                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
            else
                echo json_encode(["state" => "false", "message" => "Database update failed"]);

        } else {
            echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Please enter the same password again.']));
        }
    } else {
        echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Old password incorrect!!']));
    }
}

function skipPasswordChange($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Not logged in"]);
        return;
    }
    $DB->table = 'users';
    $DB->data = array('must_change_password' => 0);
    $DB->where = 'id = ' . $_SESSION['user']['id'];
    $updated = $DB->update();
    if ($updated) {
        $_SESSION['user']['must_change_password'] = 0;
        echo json_encode(["state" => "true"]);
    } else {
        echo json_encode(["state" => "false", "message" => "Database update failed"]);
    }
}
?>