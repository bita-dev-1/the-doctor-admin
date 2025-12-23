<?php
// Security check
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}

// Include Controller
include_once 'controllers/custom/core/ChatController.php';
include_once 'header.php';

// تهيئة المتغير لتجنب الأخطاء
$conversationId = isset($conversationId) ? $conversationId : null;
$current = (isset($conversationId) && is_numeric($conversationId)) ? (int) $conversationId : NULL;

// جلب البيانات
$chat_list = chat_list($current);
?>

<!-- Link to New CSS -->
<link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/assets/css/messages.css">

<style>
    html .content.app-content .content-area-wrapper {
        display: flex;
        position: relative;
        overflow: hidden;
    }

    html .navbar-floating.footer-static .app-content .content-area-wrapper,
    html .navbar-floating.footer-static .app-content .kanban-wrapper {
        height: calc(100vh - calc(calc(2rem * 1) + 4.45rem + 3.35rem + 1.3rem + 0rem));
        height: calc(var(--vh, 1vh) * 100 - calc(calc(2rem * 1) + 4.45rem + 3.35rem + 1.3rem + 0rem));
    }
</style>

<div class="app-content content chat-application">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-area-wrapper p-0">

        <!-- Create Conversation Sidebar -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="createConversation"
            aria-labelledby="createConversationLabel">
            <div class="offcanvas-header">
                <h4 class="offcanvas-title text-primary" id="createConversationLabel">Nouvelle Conversation</h4>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form class="post-conversation" method="post">
                    <?php set_csrf(); ?>
                    <div class="mb-2">
                        <?php
                        $input = array(
                            "label" => "Participants",
                            "name_id" => "participants[]",
                            "placeholder" => 'Ajouter des utilisateurs...',
                            "class" => "subParts",
                            "attr" => "data-subPart = 'participant'",
                            "multiple" => false,
                            "serverSide" => array(
                                "table" => "users",
                                "value" => "id",
                                "text" => array("first_name", "last_name"),
                                "where" => "id != " . $_SESSION['user']['id'] . " AND deleted = 0"
                            )
                        );
                        draw_select($input);
                        ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 waves-effect waves-float waves-light">
                        <i data-feather="message-square" class="me-50"></i> Démarrer
                    </button>
                </form>
            </div>
        </div>

        <!-- Left Sidebar (Chat List) -->
        <div class="sidebar-left">
            <div class="sidebar">
                <div class="sidebar-content">
                    <span class="sidebar-close-icon">
                        <i data-feather="x"></i>
                    </span>

                    <!-- Search -->
                    <div class="chat-fixed-search">
                        <div class="d-flex align-items-center w-100">
                            <div class="sidebar-profile-toggle" data-profile="<?php echo $_SESSION['user']['id']; ?>">
                                <div class="avatar avatar-border">
                                    <img src="<?php echo (!empty($_SESSION['user']['image1']) ? $_SESSION['user']['image1'] : '/assets/images/default_User.png'); ?>"
                                        alt="user_avatar" height="42" width="42" />
                                    <span class="avatar-status-online"></span>
                                </div>
                            </div>
                            <div class="input-group input-group-merge ms-1 w-100">
                                <span class="input-group-text round"><i data-feather="search"
                                        class="text-muted"></i></span>
                                <input type="text" class="form-control round" id="chat-search"
                                    placeholder="Rechercher..." aria-label="Search..." />
                            </div>
                        </div>
                    </div>

                    <!-- Chat List -->
                    <div id="users-list" class="chat-user-list-wrapper list-group">
                        <div class="d-flex align-items-center justify-content-between chat-list-title mb-1 px-2 mt-1">
                            <h4 class="chat-list-title m-0 text-secondary">Discussions</h4>
                            <i data-feather="plus-circle" class="cursor-pointer text-primary"
                                style="width: 20px; height: 20px;" data-bs-toggle="offcanvas" href="#createConversation"
                                role="button"></i>
                        </div>

                        <ul class="chat-users-list chat-list media-list">
                            <?php
                            if (isset($chat_list['chat_list']) && is_array($chat_list['chat_list'])) {
                                foreach ($chat_list['chat_list'] as $user) {
                                    $lastMsg = $user['last_msg']['message'] ?? '';
                                    $lastType = $user['last_msg']['type'] ?? 0;
                                    $displayMsg = $lastMsg;

                                    if ($lastType == 1)
                                        $displayMsg = '<i data-feather="image" size="14"></i> Photo';
                                    elseif ($lastType == 2)
                                        $displayMsg = '<i data-feather="file" size="14"></i> Fichier';

                                    $activeClass = (isset($conversationId) && $conversationId == $user['id']) ? 'active' : '';

                                    echo '
                                    <li class="' . $activeClass . '" data-express="' . ($user['id']) . '">
                                        <span class="avatar">
                                            <img src="' . ($user['image'] ?? '/assets/images/default_User.png') . '" height="42" width="42" alt="" />
                                        </span>
                                        <div class="chat-info flex-grow-1">
                                            <h5 class="mb-0">' . ($user['participants'][0]['user'] ?? 'Utilisateur') . '</h5>
                                            <p class="card-text text-truncate">' . $displayMsg . '</p>
                                        </div>
                                    </li>';
                                }
                            }
                            if (empty($chat_list['chat_list'])) {
                                echo '<li class="no-results"><h6 class="mb-0 text-center p-2">Aucune conversation</h6></li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content (Chat Window) -->
        <div class="content-right">
            <div class="content-wrapper container-xxl p-0">
                <div class="content-header row"></div>
                <div class="content-body">
                    <div class="body-content-overlay"></div>

                    <section class="chat-app-window">
                        <!-- Empty State -->
                        <div
                            class="start-chat-area <?php echo !empty($chat_list['data']['messages']) || (isset($conversationId) && is_numeric($conversationId)) ? 'd-none' : ''; ?>">
                            <div class="mb-1 start-chat-icon">
                                <i data-feather="message-square" class="text-primary"></i>
                            </div>
                            <h4 class="sidebar-toggle start-chat-text text-secondary">Commencer une conversation</h4>
                        </div>

                        <!-- Active Chat -->
                        <div
                            class="active-chat <?php echo !empty($chat_list['data']['messages']) || (isset($conversationId) && is_numeric($conversationId)) ? '' : 'd-none'; ?>">

                            <!-- Header -->
                            <div class="chat-navbar">
                                <header class="chat-header"
                                    data-express="<?php echo isset($conversationId) ? $conversationId : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="sidebar-toggle d-block d-lg-none me-1">
                                            <i data-feather="menu" class="font-medium-5"></i>
                                        </div>
                                        <div class="avatar avatar-border user-profile-toggle m-0 me-1">
                                            <img src="<?php echo (isset($chat_list['data']['users'][0]['image']) ? $chat_list['data']['users'][0]['image'] : '/assets/images/default_User.png'); ?>"
                                                alt="avatar" height="36" width="36" />
                                        </div>
                                        <h6 class="mb-0 current-conversation text-secondary">
                                            <?php echo (isset($chat_list['data']['users'][0]['full_name']) ? $chat_list['data']['users'][0]['full_name'] : ''); ?>
                                        </h6>
                                    </div>
                                </header>
                            </div>

                            <!-- Messages -->
                            <div class="user-chats">
                                <div class="chats">
                                    <?php
                                    if (isset($chat_list['data']['messages']) && is_array($chat_list['data']['messages'])) {
                                        foreach ($chat_list['data']['messages'] as $message) {
                                            $isSender = ($message['id_sender'] == $_SESSION['user']['id']);
                                            $senderImg = $isSender ? $_SESSION['user']['image1'] : ($chat_list['data']['users'][0]['image'] ?? '/assets/images/default_User.png');

                                            echo '<div class="chat ' . ($isSender ? '' : 'chat-left') . '" data-express="' . $message['id'] . '">
                                                    <div class="chat-avatar">
                                                        <span class="avatar box-shadow-1 cursor-pointer">
                                                            <img src="' . ($senderImg ? $senderImg : '/assets/images/default_User.png') . '" alt="avatar" height="36" width="36" />
                                                        </span>
                                                    </div>
                                                    <div class="chat-body">
                                                        <div class="chat-content">';

                                            if ($message['type'] == 1) { // Image
                                                echo '<div class="attachement_item downloadable d-flex w-auto" data-file="' . $message['message'] . '">
                                                        <img class="img-fluid rounded" src="' . $message['message'] . '" />
                                                      </div>';
                                            } elseif ($message['type'] == 2) { // File
                                                echo '<div class="attachement_item downloadable d-flex align-items-center p-1 bg-light rounded" data-file="' . $message['message'] . '">
                                                        <i data-feather="file" class="me-1"></i>
                                                        <span class="text-truncate">' . basename($message['message']) . '</span>
                                                      </div>';
                                            } else { // Text
                                                echo "<p>" . htmlspecialchars($message['message']) . "</p>";
                                            }

                                            echo '      </div>
                                                    </div>
                                                </div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Input Area -->
                            <form class="chat-app-form position-relative" action="javascript:void(0);">
                                <input type="hidden" id="file-path-input" value="" />
                                <input type="hidden" name="conversation"
                                    value="conversationId-<?php echo ($current ?? ''); ?>" />

                                <div class="chat-app-form-inputs">
                                    <div class="input-group input-group-merge me-1 form-send-message">
                                        <input type="text" class="form-control message" name="message"
                                            placeholder="Écrivez votre message..." />
                                        <span class="input-group-text">
                                            <label for="attach-doc" class="attachment-icon form-label mb-0"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseExample" aria-expanded="false">
                                                <i data-feather="paperclip" class="cursor-pointer text-secondary"></i>
                                            </label>
                                        </span>
                                    </div>
                                    <button type="submit" class="btn btn-primary send">
                                        <i data-feather="send"></i>
                                    </button>
                                </div>

                                <!-- File Upload Collapse -->
                                <div class="collapse" id="collapseExample">
                                    <div class="chat-app-form-files p-2 border-top">
                                        <?php
                                        $input = array(
                                            "label" => "Joindre un fichier",
                                            "type" => "dropArea",
                                            "name_id" => "customFile",
                                            "accept" => ".png, .jpg, .jpeg, .pdf, .doc, .docx",
                                            "class" => "",
                                            "value" => ""
                                        );
                                        draw_fileUpload($input);
                                        ?>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>

<script src="<?= SITE_URL; ?>/app-assets/js/scripts/pages/app-chat.js"></script>
<script>
    $(document).ready(function () {
        // Fix for chat list click
        $(document).on('click', '.chat-users-list li', function () {
            var conversationId = $(this).data('express');
            if (conversationId) {
                window.location.href = '<?= SITE_URL ?>/messages/' + conversationId;
            }
        });

        // Send Message Handler
        $('.chat-app-form').on('submit', function (e) {
            e.preventDefault();
            var msg = $(this).find('.message').val();
            var file = $('#file-path-input').val();
            var convId = $('input[name="conversation"]').val();

            if (msg.trim() == '' && file == '') return;

            var data = {
                method: 'send_msg',
                conversation: convId,
                message: msg
            };

            if (file != '') {
                data.file = 'true';
                data.file_path = file;
            }

            $.ajax({
                url: '<?= SITE_URL ?>/handlers',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (res) {
                    if (res.state == 'true') {
                        location.reload();
                    } else {
                        if (res.message) Swal.fire('Erreur', res.message, 'error');
                    }
                }
            });
        });

        // Create Conversation Handler
        $('.post-conversation').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serializeArray();
            formData.push({ name: 'method', value: 'post_conversation' });

            $.ajax({
                url: '<?= SITE_URL ?>/handlers',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (res) {
                    if (res.state == 'true') {
                        location.reload();
                    }
                }
            });
        });
    });
</script>