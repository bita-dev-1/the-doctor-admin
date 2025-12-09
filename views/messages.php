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
        <div class="offcanvas offcanvas-start" tabindex="-1" id="createConversation"
            aria-labelledby="createConversationLabel">
            <div class="offcanvas-header">
                <h4 class="offcanvas-title" id="createConversationLabel"><?php echo 'Create a conversation'; ?></h4>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form class="post-conversation" method="post">
                    <?php set_csrf(); ?>
                    <?php
                    $input = array(
                        "label" => "",
                        "name_id" => "participants[]",
                        "placeholder" => 'add users to the conversation',
                        "class" => "subParts",
                        "attr" => "data-subPart = '" . ("participant") . "'",
                        "multiple" => false,
                        "max_select" => "",
                        "serverSide" => array(
                            "table" => "users",
                            "value" => "id",
                            "value_parent" => "",
                            "text" => array("first_name", "last_name"),
                            "selected" => "",
                            "where" => "id != " . $_SESSION['user']['id']
                        )
                    );

                    draw_select($input);
                    ?>
                    <?php
                    $button = array(
                        "text" => 'Démarrer',
                        "type" => "submit",
                        "name_id" => "submit",
                        "class" => "btn btn btn-primary text-center waves-effect waves-float waves-light fw-bolder position-absolute"
                    );
                    draw_button($button);
                    ?>
                </form>
            </div>
        </div>
        <div class="sidebar-left">
            <div class="sidebar">

                <!-- Chat Sidebar area -->
                <div class="sidebar-content">
                    <span class="sidebar-close-icon">
                        <i data-feather="x"></i>
                    </span>
                    <!-- Sidebar header start -->
                    <div class="chat-fixed-search">
                        <div class="d-flex align-items-center w-100">
                            <div class="sidebar-profile-toggle" data-profile="<?php echo $_SESSION['user']['id']; ?>"
                                data-image="<?php echo $_SESSION['user']['image1']; ?>">
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
                                    placeholder="<?php echo 'Search or start a new chat'; ?> "
                                    aria-label="<?php echo 'seek...'; ?> ." aria-describedby="chat-search" />
                            </div>
                        </div>
                    </div>
                    <!-- Sidebar header end -->

                    <!-- Sidebar Users start -->
                    <div id="users-list" class="chat-user-list-wrapper list-group">
                        <div class="d-flex align-items-center justify-content-between chat-list-title mb-1">
                            <h4 class="chat-list-title m-0"><?php echo 'chats'; ?></h4>
                            <i data-feather="plus-circle" class="cursor-pointer" style="width: 20px; height: 20px;"
                                data-bs-toggle="offcanvas" href="#createConversation" role="button"
                                aria-controls="createConversation"></i>
                        </div>

                        <ul class="chat-users-list chat-list media-list">
                            <?php
                            if (isset($chat_list['chat_list']) && is_array($chat_list['chat_list'])) {
                                foreach ($chat_list['chat_list'] as $user) {
                                    // تحديد الرسالة الأخيرة ونوعها
                                    $lastMsg = $user['last_msg']['message'] ?? '';
                                    $lastType = $user['last_msg']['type'] ?? 0;
                                    $displayMsg = $lastMsg;

                                    if ($lastType == 1)
                                        $displayMsg = 'vous a envoyé une photo';
                                    elseif ($lastType == 2)
                                        $displayMsg = 'vous a envoyé un fichier';

                                    // تحديد الكلاس النشط
                                    $activeClass = (isset($conversationId) && $conversationId == $user['id']) ? 'active' : '';

                                    echo '
                                            <li class="' . $activeClass . '" data-express="' . ($user['id']) . '">
                                                <span class="avatar">
                                                    <img src="' . ($user['image'] ?? '/assets/images/default_User.png') . '" height="42" width="42" alt="" />
                                                </span>
                                                <div class="chat-info flex-grow-1">
                                                    <h5 class="mb-0">' . ($user['participants'][0]['user'] ?? 'Unknown User') . '</h5>
                                                    <p class="card-text text-truncate">' . $displayMsg . '</p>
                                                </div>
                                                <div class="chat-meta text-nowrap">
                                                    <small class="float-end mb-25 chat-time"></small>
                                                </div>
                                            </li>
                                            ';
                                }
                            }
                            if (empty($chat_list['chat_list'])) {
                                echo '<li class="no-results"><h6 class="mb-0">No conversations found</h6></li>';
                            }
                            ?>
                        </ul>
                    </div>
                    <!-- Sidebar Users end -->
                </div>
                <!--/ Chat Sidebar area -->

            </div>
        </div>
        <div class="content-right">
            <div class="content-wrapper container-xxl p-0">
                <div class="content-header row">
                </div>
                <div class="content-body">
                    <div class="body-content-overlay"></div>
                    <!-- Main chat area -->
                    <section class="chat-app-window">
                        <!-- To load Conversation -->
                        <!-- المنطق: يظهر إذا لم تكن هناك رسائل ولم يتم تحديد محادثة صالحة -->
                        <div
                            class="start-chat-area <?php echo !empty($chat_list['data']['messages']) || (isset($conversationId) && is_numeric($conversationId)) ? 'd-none' : ''; ?>">
                            <div class="mb-1 start-chat-icon">
                                <i data-feather="message-square"></i>
                            </div>
                            <h4 class="sidebar-toggle start-chat-text"><?php echo 'Start the conversation'; ?></h4>
                        </div>
                        <!--/ To load Conversation -->

                        <!-- Active Chat -->
                        <!-- المنطق: يظهر إذا كانت هناك رسائل أو تم تحديد محادثة صالحة -->
                        <div
                            class="active-chat <?php echo !empty($chat_list['data']['messages']) || (isset($conversationId) && is_numeric($conversationId)) ? '' : 'd-none'; ?>">

                            <!-- Chat Header -->
                            <div class="chat-navbar">
                                <header class="chat-header"
                                    data-express="<?php echo isset($conversationId) ? $conversationId : ''; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="sidebar-toggle d-block d-lg-none me-1">
                                            <i data-feather="menu" class="font-medium-5"></i>
                                        </div>
                                        <div class="avatar avatar-border user-profile-toggle m-0 me-1">
                                            <img src="<?php echo (is_array($chat_list['data']['users']) && count($chat_list['data']['users']) > 0 && isset($chat_list['data']['users'][0]['image']) && $chat_list['data']['users'][0]['image'] != null ? ($chat_list['data']['users'][0]['image']) : '/assets/images/default_User.png'); ?>"
                                                alt="avatar" height="36" width="36" />
                                        </div>
                                        <h6 class="mb-0 current-conversation">
                                            <?php echo (is_array($chat_list['data']['users']) && count($chat_list['data']['users']) > 0 && isset($chat_list['data']['users'][0]['full_name']) ? $chat_list['data']['users'][0]['full_name'] : ''); ?>
                                        </h6>
                                    </div>
                                </header>
                            </div>
                            <!--/ Chat Header -->

                            <!-- User Chat messages -->
                            <div class="user-chats">
                                <div class="chats">
                                    <?php
                                    if (isset($chat_list['data']['messages']) && is_array($chat_list['data']['messages'])) {
                                        foreach ($chat_list['data']['messages'] as $message) {

                                            $isSender = ($message['id_sender'] == $_SESSION['user']['id']);
                                            $senderImg = $isSender ? $_SESSION['user']['image1'] : ($chat_list['data']['users'][0]['image'] ?? '/assets/images/default_User.png');

                                            echo '
                                                <div class="chat ' . ($isSender ? '' : 'chat-left') . '" data-express="' . $message['id'] . '">
                                                    <div class="chat-avatar">
                                                        <span class="avatar box-shadow-1 cursor-pointer">
                                                            <img src="' . ($senderImg ? $senderImg : '/assets/images/default_User.png') . '" alt="avatar" height="36" width="36" />
                                                        </span>
                                                    </div>
                                                    <div class="chat-body">
                                                        <div class="chat-content">
                                                            ' .
                                                (
                                                    $message['type'] == 1 ?
                                                    '<div class="attachement_item downloadable d-flex w-auto" data-file="' . $message['message'] . '">
                                                                <img class="img-fluid" src="' . $message['message'] . '" />
                                                            </div>'
                                                    : (
                                                        $message['type'] == 2 ?

                                                        '<div class="attachement_item downloadable d-flex pe-3 mt-1 w-auto" data-file="' . $message['message'] . '">
                                                                        <span class="attachement_type">' . pathinfo($message['message'], PATHINFO_EXTENSION) . '</span>
                                                                        <p class="m-0">' . basename($message['message']) . '</p>
                                                                    </div>' :

                                                        "<p>$message[message]</p>"
                                                    )
                                                )
                                                . '
                                                        </div>
                                                    </div>
                                                </div>
                                                ';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <!-- User Chat messages -->

                            <!-- Submit Chat form -->
                            <form class="chat-app-form position-relative" action="javascript:void(0);">
                                <input type="hidden" id="file-path-input" value="" />
                                <input type="hidden" name="conversation"
                                    value="conversationId-<?php echo ($current ?? ''); ?>" />

                                <div class="chat-app-form-inputs">
                                    <div class="input-group input-group-merge me-1 form-send-message">
                                        <input type="text" class="form-control message" name="message"
                                            placeholder="<?php echo 'Write your message'; ?>" />
                                        <span class="input-group-text">
                                            <label for="attach-doc" class="attachment-icon form-label mb-0"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseExample" aria-expanded="false">
                                                <i data-feather="image" class="cursor-pointer text-secondary"></i>
                                            </label>
                                        </span>
                                    </div>

                                    <button type="submit"
                                        class="btn btn-primary send d-flex align-items-center justify-content-between">
                                        <i data-feather="send" class="d-lg-none"></i><span
                                            class="d-none d-lg-block"><?php echo 'send'; ?></span>
                                    </button>
                                </div>
                                <div class="collapse" id="collapseExample">
                                    <div class="chat-app-form-files">
                                        <?php
                                        $input = array(
                                            "label" => "",
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
                            <!--/ Submit Chat form -->

                        </div>
                        <!--/ Active Chat -->
                    </section>
                    <!--/ Main chat area -->

                </div>
            </div>
        </div>
    </div>
</div>
<!-- END: Content-->

</div>
</div>
<?php include_once 'foot.php'; ?>

<script src="<?= SITE_URL; ?>/app-assets/js/scripts/pages/app-chat.js"></script>
<script>
    $(document).ready(function () {
        // التعامل مع النقر على المحادثات في القائمة الجانبية
        // بما أن الكود الجديد يستخدم data-express بدلاً من href، نحتاج لتوجيه المستخدم يدوياً
        $(document).on('click', '.chat-users-list li', function () {
            var conversationId = $(this).data('express');
            if (conversationId) {
                window.location.href = '<?= SITE_URL ?>/messages/' + conversationId;
            }
        });

        // إرسال الرسالة
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
                        if (res.message) alert(res.message);
                    }
                }
            });
        });

        // إنشاء محادثة جديدة
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