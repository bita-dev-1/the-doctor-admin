<?php

function conversationsRoom($userId)
{
    global $db;
    $userId = intval($userId);

    $sql = "SELECT c.id, c.created_at 
            FROM conversation c
            INNER JOIN participant p ON c.id = p.id_conversation
            WHERE p.id_particib = ? AND c.deleted = 0
            ORDER BY c.created_at DESC";

    $conversations = $db->select($sql, [$userId]);
    $result = [];

    if (!empty($conversations)) {
        foreach ($conversations as $conv) {
            $p_sql = "SELECT u.first_name, u.last_name, u.image1
                      FROM participant p
                      INNER JOIN users u ON p.id_particib = u.id
                      WHERE p.id_conversation = ? AND p.id_particib != ?";
            $partner = $db->select($p_sql, [$conv['id'], $userId])[0] ?? null;

            $m_sql = "SELECT message, type, created_at FROM messages WHERE id_conversation = ? ORDER BY id DESC LIMIT 1";
            $last_msg = $db->select($m_sql, [$conv['id']])[0] ?? null;

            $full_name = $partner ? $partner['first_name'] . ' ' . $partner['last_name'] : 'Utilisateur inconnu';

            $result[] = [
                'id' => $conv['id'],
                'participants' => [['user' => htmlspecialchars($full_name)]],
                'image' => ($partner['image1'] ?? '/assets/images/default_User.png'),
                'last_msg' => $last_msg
            ];
        }
    }
    return $result;
}

function messages($conversationId, $lastMsgId = null)
{
    global $db;
    $conversationId = intval($conversationId);
    $params = [$conversationId];

    $where = "m.id_conversation = ?";
    if ($lastMsgId) {
        $where .= " AND m.id > ?";
        $params[] = intval($lastMsgId);
    }

    $sql = "SELECT m.*, m.id_sender as my_particib, m.id_sender as id_particib 
            FROM messages m 
            WHERE $where 
            ORDER BY m.created_at ASC";

    return $db->select($sql, $params);
}

function getConversationParticipants($conversationId)
{
    global $db;
    $user_id = $_SESSION['user']['id'];
    $conversationId = intval($conversationId);

    $sql = "SELECT u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.image1 as image
            FROM participant p
            JOIN users u ON p.id_particib = u.id
            WHERE p.id_conversation = ? AND u.id != ?";

    return $db->select($sql, [$conversationId, $user_id]);
}

function chat_list($current_conversation_id = null)
{
    $user_id = $_SESSION['user']['id'];
    $response = ['chat_list' => [], 'data' => ['messages' => [], 'users' => []]];

    $response['chat_list'] = conversationsRoom($user_id);

    if ($current_conversation_id && is_numeric($current_conversation_id)) {
        $conv_id = intval($current_conversation_id);
        $response['data']['messages'] = messages($conv_id);
        $response['data']['users'] = getConversationParticipants($conv_id);
    }

    return $response;
}

function chat()
{
    $conversationId = null;
    if (isset($_POST['conversation'])) {
        $rawId = str_replace('conversationId-', '', $_POST['conversation']);
        if (is_numeric($rawId)) {
            $conversationId = intval($rawId);
        }
    }

    $results = ($conversationId) ? messages($conversationId) : array();
    echo json_encode($results);
}

function send_msg()
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        $conversationId = null;

        if (isset($_POST['conversation']) && !empty($_POST['conversation'])) {
            $rawId = str_replace('conversationId-', '', $_POST['conversation']);
            if (is_numeric($rawId)) {
                $conversationId = intval($rawId);
            }
        }

        if (empty($conversationId)) {
            $GLOBALS['db']->table = 'conversation';
            $GLOBALS['db']->data = array("id_creator" => $userId);
            $conversationId = $GLOBALS['db']->insert();
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
                "id_sender" => $userId,
                "message" => $message_content,
                "type" => $message_type
            );

            $GLOBALS['db']->table = 'messages';
            $GLOBALS['db']->data = $data;
            $inserted = $GLOBALS['db']->insert();

            if ($inserted) {
                echo json_encode(array("state" => "true"));
            } else {
                echo json_encode(array("state" => "false", "message" => "une erreur s'est produite"));
            }
        } else {
            echo json_encode(array("state" => "false", "message" => "Conversation invalide."));
        }
    } else {
        echo json_encode(array("state" => "false", "message" => "Session expirée."));
    }
}

function post_conversation()
{
    $user_id = $_SESSION['user']['id'] ?? 0;
    $data = array("id_creator" => $user_id);
    if (isset($_POST['name']) && !empty($_POST['name']))
        $data = array_merge($data, ["name" => $_POST['name']]);

    $GLOBALS['db']->table = 'conversation';
    $GLOBALS['db']->data = $data;
    $inserted_conversation_id = $GLOBALS['db']->insert();
    if ($inserted_conversation_id) {
        if (isset($_POST['participants']) && !empty($_POST['participants'])) {
            $subData = [];
            $subData[] = [
                'id_conversation' => $inserted_conversation_id,
                'id_particib' => $user_id
            ];
            $participants = is_array($_POST['participants']) ? $_POST['participants'] : [$_POST['participants']];
            foreach ($participants as $participant_id) {
                $subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'id_particib' => $participant_id
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

function is_image($path)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
function is_fileExt($path)
{
    return !is_image($path);
}
?>