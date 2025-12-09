<?php

function chat()
{
    $results = ((isset($_POST['conversation']) && is_numeric(str_replace('conversationId-', '', ($_POST['conversation'])))) ? messages(str_replace('conversationId-', '', ($_POST['conversation']))) : array());
    echo json_encode($results);
}

function send_msg()
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
        if (isset($_POST['conversation']) && !empty($_POST['conversation'])) {
            $conversationId = str_replace('conversationId-', '', ($_POST['conversation']));
        } else {
            $GLOBALS['db']->table = 'conversation';
            $GLOBALS['db']->data = array("id_creator" => $_SESSION['user']['id']);
            $conversationId = $GLOBALS['db']->insert();
            if ($conversationId) {
            }
        }
        if ($conversationId && is_numeric($conversationId)) {
            $message_content = '';
            $message_type = 0;
            if (isset($_POST['file']) && $_POST['file'] === 'true' && isset($_POST['file_path'])) {
                $message_content = $_POST['file_path'];
                $message_type = is_image($message_content) ? 1 : (is_fileExt($message_content) ? 2 : 0);
            } elseif (isset($_POST['message'])) {
                $message_content = $_POST['message'];
                $message_type = 0;
            } else {
                echo json_encode(array("state" => "false", "message" => "Message content is missing"));
                return;
            }
            $data = array(
                "id_conversation" => $conversationId,
                "id_sender" => $_SESSION['user']['id'],
                "message" => $message_content,
                "type" => $message_type
            );
            $GLOBALS['db']->table = 'messages';
            $GLOBALS['db']->data = $data;
            $inserted = $GLOBALS['db']->insert();
            if ($inserted) {
                $results = messages($conversationId, (isset($_POST['last']) ? ($_POST['last']) : 0));
                echo json_encode(array("state" => "true", "data" => $results));
            } else
                echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
        } else
            echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
    } else
        echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
}

function post_conversation()
{
    $user_id = $_SESSION['user']['id'] ?? 0;
    $data = array("id_creator" => $user_id);
    if (isset($_POST['name']) && !empty($_POST['name']))
        $data = array_merge($data, ["name" => $_POST['name']]);
    if (isset($_POST['csrf'])) {
        $csrf_token = customDecrypt($_POST['csrf']);
        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => 'The form is forged']);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => 'The form is forged']);
        exit();
    }
    $GLOBALS['db']->table = 'conversation';
    $GLOBALS['db']->data = $data;
    $inserted_conversation_id = $GLOBALS['db']->insert();
    if ($inserted_conversation_id) {
        if (isset($_POST['participants']) && !empty($_POST['participants'])) {
            $subData = [];
            foreach ($_POST['participants'] as $participant_id) {
                $subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'my_particib' => $user_id,
                    'id_particib' => $participant_id
                ];
                $subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'my_particib' => $participant_id,
                    'id_particib' => $user_id
                ];
            }
            $GLOBALS['db']->table = 'participant';
            $GLOBALS['db']->data = $subData;
            $GLOBALS['db']->multi = true;
            $secondinsert = $GLOBALS['db']->insert();
            if ($secondinsert) {
                echo json_encode(["state" => "true", "message" => 'Added successfully']);
            } else
                echo json_encode(["state" => "false", "message" => "something went wrong while adding participants"]);
        } else
            echo json_encode(["state" => "true", "message" => 'Added successfully']);
    } else {
        echo json_encode(["state" => "false", "message" => "something went wrong while creating conversation"]);
    }
}
?>