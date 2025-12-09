<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function forget_password()
{
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $sql = "SELECT * FROM `users` WHERE `deleted` = 0 AND `email` = ?";
    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute([$email]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $newPassword = generateRandomPassword();
        $password_hash = sha1($newPassword);

        $fullName = $user_data['first_name'] . ' ' . $user_data['last_name'];
        $subject = 'Réinitialisation de votre mot de passe - The Doctor App';
        $body = "
            <h3>Réinitialisation de Mot de Passe</h3>
            <p>Bonjour {$fullName},</p>
            <p>Votre mot de passe a été réinitialisé. Voici vos nouvelles informations de connexion :</p>
            <p><strong>Nouveau mot de passe :</strong> {$newPassword}</p>
            <p>Nous vous recommandons de changer ce mot de passe après votre première connexion.</p>
            <p>Merci,<br>L'équipe The Doctor</p>
        ";

        $emailSent = sendEmail($email, $fullName, $subject, $body);

        if ($emailSent === true) {
            $GLOBALS['db']->table = "users";
            $GLOBALS['db']->data = array("password" => $password_hash);
            $GLOBALS['db']->where = 'id = ' . $user_data['id'];
            if ($GLOBALS['db']->update()) {
                echo json_encode(["state" => "true", "message" => "Un nouveau mot de passe a été envoyé à votre adresse e-mail."]);
            } else {
                echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour du mot de passe."]);
            }
        } else {
            echo json_encode(["state" => "false", "message" => "Impossible d'envoyer l'e-mail. Veuillez contacter le support."]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "Aucun compte trouvé avec cette adresse e-mail."]);
    }
}

function adminResetPassword()
{
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }

    $target_user_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $admin_id = $_SESSION['user']['id'];
    $admin_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
    $is_super_admin = empty($admin_cabinet_id);

    $sql = "SELECT * FROM `users` WHERE `id` = ?";
    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target_user) {
        echo json_encode(["state" => "false", "message" => "Utilisateur non trouvé."]);
        return;
    }

    if (!$is_super_admin && $target_user['cabinet_id'] != $admin_cabinet_id) {
        echo json_encode(["state" => "false", "message" => "Vous n'avez pas la permission de réinitialiser le mot de passe de cet utilisateur."]);
        return;
    }

    $newPassword = generateRandomPassword();
    $password_hash = sha1($newPassword);

    $fullName = $target_user['first_name'] . ' ' . $target_user['last_name'];
    $subject = 'Votre mot de passe a été réinitialisé par un administrateur';
    $body = "
        <h3>Réinitialisation de Mot de Passe</h3>
        <p>Bonjour {$fullName},</p>
        <p>Votre mot de passe a été réinitialisé par un administrateur. Voici vos nouvelles informations de connexion :</p>
        <p><strong>Nouveau mot de passe :</strong> {$newPassword}</p>
        <p>Nous vous recommandons de changer ce mot de passe après votre prochaine connexion.</p>
        <p>Merci,<br>L'équipe The Doctor</p>
    ";

    $emailSent = sendEmail($target_user['email'], $fullName, $subject, $body);

    if ($emailSent === true) {
        $GLOBALS['db']->table = "users";
        $GLOBALS['db']->data = array("password" => $password_hash, "modified_by" => $admin_id, "modified_at" => date('Y-m-d H:i:s'));
        $GLOBALS['db']->where = 'id = ' . $target_user_id;
        if ($GLOBALS['db']->update()) {
            echo json_encode(["state" => "true", "message" => "Le mot de passe de l'utilisateur a été réinitialisé et envoyé par e-mail."]);
        } else {
            echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour du mot de passe dans la base de données."]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "Impossible d'envoyer l'e-mail de réinitialisation."]);
    }
}

function postuser()
{

    $array_data = array();
    $table = 'users';

    foreach ($_POST['data'] as $data) {
        if (stripos($data['name'], 'password') !== false) {
            $array_data[$data['name']] = sha1($data['value']);
        } else {
            $array_data[$data['name']] = $data['value'];
        }
    }

    if (isset($array_data['csrf'])) {
        $csrf_token = customDecrypt($array_data['csrf']);
        unset($array_data['csrf']);

        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    if (!empty($array_data)) {

        $default_com = array("card_id" => NULL, "percentage" => ($array_data['default_com'] ?? 0), "mobilis_com" => NULL, "ooredoo_com" => NULL, "djezzy_com" => NULL);
        if (isset($array_data['default_com']))
            unset($array_data['default_com']);

        $flexy_com = array("card_id" => NULL, "percentage" => NULL, "mobilis_com" => ($array_data['mobilis'] ?? 0), "ooredoo_com" => ($array_data['ooredoo'] ?? 0), "djezzy_com" => ($array_data['djezzy'] ?? 0));
        if (isset($array_data['mobilis']))
            unset($array_data['mobilis']);

        if (isset($array_data['ooredoo']))
            unset($array_data['ooredoo']);

        if (isset($array_data['djezzy']))
            unset($array_data['djezzy']);

        $type = abs(filter_var(customDecrypt($_POST['class']), FILTER_SANITIZE_NUMBER_INT));

        $array_data = array_merge($array_data, array("type" => $type, "parent" => ($_SESSION['user']['data'][0]['type'] == 0 ? (isset($array_data['parent']) ? $array_data['parent'] : 0) : $_SESSION['user']['data'][0]['id']), "created_by" => $_SESSION['user']['data'][0]['id']));
        $GLOBALS['db']->table = $table;
        $GLOBALS['db']->data = $array_data;

        $inserted = $GLOBALS['db']->insert();

        if ($inserted) {
            $users_com = array("parent" => ($_SESSION['user']['data'][0]['type'] == 0 ? (isset($array_data['parent']) ? $array_data['parent'] : 0) : $_SESSION['user']['data'][0]['id']), "child" => $inserted);

            $commissions = array();
            $commissions[] = array_merge($users_com, $default_com);
            $commissions[] = array_merge($users_com, $flexy_com);

            if (isset($_POST['commissions']) && !empty($_POST['commissions'])) {
                $cards_com = array_map(function ($subArray) use ($users_com) {
                    return array_merge($users_com, $subArray, array("mobilis_com" => NULL, "ooredoo_com" => NULL, "djezzy_com" => NULL));
                }, $_POST['commissions']);
                $commissions = array_merge($commissions, $cards_com);
            }

            $GLOBALS['db']->table = 'commissions';
            $GLOBALS['db']->data = $commissions;
            $GLOBALS['db']->multi = true;
            $GLOBALS['db']->insert();

            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]);
        } else {
            echo json_encode(["state" => "false", "message" => $inserted]);
        }
    } else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);

    $GLOBALS['db'] = null;
}

function updateuser()
{

    $id_user = abs(filter_var(customDecrypt($_POST['id']), FILTER_SANITIZE_NUMBER_INT));
    if ($id_user) {
        $array_data = array();
        $table = 'users';

        foreach ($_POST['data'] as $data) {
            $array_data[$data['name']] = $data['value'];
        }

        if (isset($array_data['csrf'])) {
            $csrf_token = customDecrypt($array_data['csrf']);
            unset($array_data['csrf']);

            if (!is_csrf_valid($csrf_token)) {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }

        $commissions = get_userCommissions($id_user);

        if (isset($array_data['default_com'])) {
            $default_com = array("percentage" => $array_data['default_com']);
            unset($array_data['default_com']);

            $GLOBALS['db']->table = 'commissions';
            $GLOBALS['db']->data = $default_com;
            $GLOBALS['db']->where = 'id = ' . ($commissions['default_com']['id'] ?? 0);
            $GLOBALS['db']->update();
        }

        if (isset($array_data['mobilis']) && isset($array_data['ooredoo']) && isset($array_data['djezzy'])) {
            $flexy_com = array("mobilis_com" => $array_data['mobilis'], "ooredoo_com" => $array_data['ooredoo'], "djezzy_com" => $array_data['djezzy']);

            unset($array_data['mobilis']);
            unset($array_data['ooredoo']);
            unset($array_data['djezzy']);

            $GLOBALS['db']->table = 'commissions';
            $GLOBALS['db']->data = $flexy_com;
            $GLOBALS['db']->where = 'id = ' . ($commissions['flexy']['id'] ?? 0);
            $GLOBALS['db']->update();
        }

        $datetime = date('Y-m-d H:i:s');
        $array_data = array_merge($array_data, array("modified_at" => "$datetime", "modified_by" => $_SESSION['user']['data'][0]['id']));

        $GLOBALS['db']->table = $table;
        $GLOBALS['db']->data = $array_data;
        $GLOBALS['db']->where = 'id = ' . $id_user;
        $updated = $GLOBALS['db']->update();

        if ($updated) {

            if (isset($commissions['commissions']) && !empty($commissions['commissions'])) {
                $deleted = array_values(array_column($commissions['commissions'], 'id'));
                $GLOBALS['db']->table = "commissions";
                $GLOBALS['db']->data = $deleted;
                $GLOBALS['db']->column = 'id';
                $GLOBALS['db']->multi = true;
                $GLOBALS['db']->Delete();
            }

            if (isset($_POST['commissions']) && !empty($_POST['commissions'])) {
                $users_com = array("parent" => ($_SESSION['user']['data'][0]['type'] == 0 ? (isset($array_data['parent']) ? $array_data['parent'] : 0) : $_SESSION['user']['data'][0]['id']), "child" => $id_user);

                $cards_com = array_map(function ($subArray) use ($users_com) {
                    return array_merge($users_com, $subArray);
                }, $_POST['commissions']);

                $GLOBALS['db']->table = 'commissions';
                $GLOBALS['db']->data = $cards_com;
                $GLOBALS['db']->multi = true;
                $GLOBALS['db']->insert();
            }

            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
        } else {
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    } else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);

    $GLOBALS['db'] = null;
}

function get_user()
{

    $sql = "SELECT first_name, last_name, email, phone1, balance, credit FROM users WHERE id = " . (isset($_POST['id']) ? $_POST['id'] : 0) . "";

    $response = $GLOBALS['db']->select($sql);
    $GLOBALS['db'] = null;
    echo json_encode($response[0]);

}

function acountState()
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])):
        $conversationId = NULL;
        if (isset($_POST['conversation']) && !empty($_POST['conversation'])) {
            $conversationId = ((int) str_replace('conversationId-', '', ($_POST['conversation'])));
            $conversationId = is_numeric($conversationId) ? $conversationId : NULL;
        }
        $results = conversationsRoom($_SESSION['user']['id']);
        $global_data['chat_list'] = $results;
        $global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId, (isset($_POST['last']) ? ($_POST['last']) : NULL)) : array());
        $global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());
        echo json_encode($global_data);
    else:
        echo json_encode(array());
    endif;
}
?>