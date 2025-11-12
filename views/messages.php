<?php 
    // MODIFIED: Corrected security check
    if(!isset($_SESSION['user']['id'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 

    $current = isset($conversationId) ?  $conversationId : NULL;

	$chat_list = chat_list($current);

?>
<style>
html .content.app-content .content-area-wrapper {
  display: flex;
  position: relative;
  overflow: hidden; }

  html .navbar-floating.footer-static .app-content .content-area-wrapper,
html .navbar-floating.footer-static .app-content .kanban-wrapper {
 height: calc(
 100vh -
 calc(
 calc(2rem * 1) + 4.45rem + 3.35rem + 1.3rem + 0rem
 ));
  height: calc(
 var(--vh, 1vh) * 100 -
 calc(
 calc(2rem * 1) + 4.45rem + 3.35rem + 1.3rem + 0rem
 ));}
</style>
    <div class="app-content content chat-application">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-area-wrapper p-0">
            <div class="offcanvas offcanvas-start" tabindex="-1" id="createConversation" aria-labelledby="createConversationLabel">
                <div class="offcanvas-header">
                    <h4 class="offcanvas-title" id="createConversationLabel"><?php echo 'Create a conversation'; ?></h4>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <form class="post-conversation" method="post" >
                        <?php set_csrf(); ?>
                        <?php 
                         /*   $input = array(
                                "label"         => "",
                                "type"          => "text",
                                "name_id"       => "name",
                                "placeholder"   =>  'Conversational name (optional)',
                                "class"         => "mb-1 excluded",
                                "value"         => ""
                            );
                            
                            draw_input($input); */
                        ?>
                        <?php
                        
                            $input = array(
                                "label"         => "",
                                "name_id"       => "id_particib",
                                "placeholder"   => 'add users to the conversation',
                                "class"         => "subParts",
                                "attr"			=> "data-subPart = '".("participant")."'",
                                "multiple"      => false,
                                "max_select"	=> "",
                                "serverSide"        => array(
                                    "table"         => "patient",
                                    "value"         => "id",
                                    "value_parent"  => "",
                                    "text"          => array("first_name"),
                                    "selected"      => "",
                                    "where"         => ""
                                )
                            );
                            
                            draw_select($input);
                        
                        ?>
                        <?php
                            $button = array(
                                "text"          => 'construction',
                                "type"          => "submit",
                                "name_id"       => "submit",
                                "class"         => "btn btn btn-primary text-center waves-effect waves-float waves-light fw-bolder position-absolute"
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
                                <div class="sidebar-profile-toggle" data-profile="<?php echo $_SESSION['user']['id']; ?>" data-image="<?php echo $_SESSION['user']['image1']; ?>">
                                    <div class="avatar avatar-border">
                                        <img src="<?php echo (!empty($_SESSION['user']['image1']) ? $_SESSION['user']['image1'] : '/assets/images/default_User.png'); ?>" alt="user_avatar" height="42" width="42" />
                                        <span class="avatar-status-online"></span>
                                    </div>
                                </div>
                                <div class="input-group input-group-merge ms-1 w-100">
                                    <span class="input-group-text round"><i data-feather="search" class="text-muted"></i></span>
                                    <input type="text" class="form-control round" id="chat-search" placeholder="<?php echo 'Search or start a new chat'; ?> " aria-label="<?php echo 'seek...'; ?> ." aria-describedby="chat-search" />
                                </div>
                            </div>
                        </div>
                        <!-- Sidebar header end -->

                       <!-- Sidebar Users start -->
                       <div id="users-list" class="chat-user-list-wrapper list-group">
                            <div class="d-flex align-items-center justify-content-between chat-list-title mb-1">
                                <h4 class="chat-list-title m-0"><?php echo 'chats'; ?></h4>
                                <!-- MODIFIED: Re-enabled the button and changed the icon -->
                                <i data-feather="plus-circle" class="cursor-pointer" style="width: 20px; height: 20px;" data-bs-toggle="offcanvas" href="#createConversation" role="button" aria-controls="createConversation"></i>
                            </div>
                            
                            <ul class="chat-users-list chat-list media-list">
                                <?php 
                                    if (isset($chat_list['chat_list']) && is_array($chat_list['chat_list'])) {
                                        foreach($chat_list['chat_list'] as $user){
                                            echo '
                                            <li data-express="'.($user['id']).'" '.(isset($conversationId) && $conversationId == $user['id'] ? 'class="active"' : '').'>
                                                <span class="avatar">
                                                    <img src="/assets/images/default_User.png" height="42" width="42" alt="" />
                                                </span>
                                                <div class="chat-info flex-grow-1">
                                                    <h5 class="mb-0">'.($user['participants'][0]['user'] ?? 'Unknown User').'</h5>
                                                    <p class="card-text text-truncate">
                                                        '.(isset($user['last_msg']['message']) ? ( $user['last_msg']['type'] == 1 ? 'vous a envoyé une photo' : ( $user['last_msg']['message'] == 2 ? 'vous a envoyé une fichier' : ($user['last_msg']['message']))  ) : '').'
                                                    </p>
                                                </div>
                                                <div class="chat-meta text-nowrap">
                                                    <small class="float-end mb-25 chat-time"></small>
                                                </div>
                                            </li>
                                            ';
                                        }
                                    }
                                ?>
                                <li class="no-results">
                                    <h6 class="mb-0"><?php echo 'No conversations found'; ?></h6>
                                </li>
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
                            <div class="start-chat-area <?php echo !empty($chat_list['data']['messages']) || (empty($chat_list['data']['messages']) && isset($conversationId) && is_array($chat_list['chat_list']) && in_array($conversationId, array_column($chat_list['chat_list'], 'id'))) ? 'd-none' : ''; ?>">
                                <div class="mb-1 start-chat-icon">
                                    <i data-feather="message-square"></i>
                                </div>
                                <h4 class="sidebar-toggle start-chat-text"><?php echo 'Start the conversation'; ?></h4>
                            </div>
                            <!--/ To load Conversation -->

                            <!-- Active Chat -->
                            <div class="active-chat <?php echo empty($chat_list['data']['messages']) || (!empty($chat_list['data']['messages']) && isset($conversationId) && is_array($chat_list['chat_list']) && in_array($conversationId, array_column($chat_list['chat_list'], 'id'))) ? '' : 'd-none'; ?>">

                                <!-- Chat Header -->
                                <div class="chat-navbar">
                                    <header class="chat-header" data-express="<?php echo isset($conversationId) ? $conversationId : ''; ?>">

                                        <div class="d-flex align-items-center">
                                            <div class="sidebar-toggle d-block d-lg-none me-1">
                                                <i data-feather="menu" class="font-medium-5"></i>
                                            </div>
                                            <div class="avatar avatar-border user-profile-toggle m-0 me-1">
                                                <img src="<?php echo ( is_array($chat_list['data']['users']) && count($chat_list['data']['users']) > 0 && isset($chat_list['data']['users'][0]['image']) && $chat_list['data']['users'][0]['image'] != null ? ($chat_list['data']['users'][0]['image']) : '/assets/images/default_User.png' ); ?>" alt="avatar" height="36" width="36" />
                                            </div>
                                            <h6 class="mb-0 current-conversation"><?php echo (is_array($chat_list['data']['users']) && count($chat_list['data']['users']) > 0 && isset($chat_list['data']['users'][0]['full_name']) ? $chat_list['data']['users'][0]['full_name'] : ''); ?></h6>
                                        </div>
                                        
                                    </header>
                                </div>
                                <!--/ Chat Header -->

                                <!-- User Chat messages -->
                                <div class="user-chats">
                                    <div class="chats">
                                    <?php 
                                        if (isset($chat_list['data']['messages']) && is_array($chat_list['data']['messages'])) {
                                            foreach($chat_list['data']['messages'] as $message){
                                                
                                                $isSender = ($message['id_sender'] == $_SESSION['user']['id'] && ($message['my_particib'] == $_SESSION['user']['id'] || $message['id_particib'] == $_SESSION['user']['id']));
                                                
                                                echo '
                                                <div class="chat '.($isSender ? '' : 'chat-left').'" data-express="'.$message['id'].'">
                                                    <div class="chat-avatar">
                                                        <span class="avatar box-shadow-1 cursor-pointer">
                                                            <img src="'.($isSender ? $_SESSION['user']['image1'] : '/assets/images/default_User.png').'" alt="avatar" height="36" width="36" />
                                                        </span>
                                                    </div>
                                                    <div class="chat-body">
                                                        <div class="chat-content">
                                                            '.
                                                            (
                                                            $message['type'] == 1 ? 
                                                            '<div class="attachement_item downloadable d-flex w-auto" data-file="'.$message['message'].'">
                                                                <img class="img-fluid" src="'.$message['message'].'" />
                                                            </div>'
                                                                :(
                                                                    $message['type'] == 2 ?

                                                                    '<div class="attachement_item downloadable d-flex pe-3 mt-1 w-auto" data-file="'.$message['message'].'">
                                                                        <span class="attachement_type">'.pathinfo($message['message'], PATHINFO_EXTENSION).'</span>
                                                                        <p class="m-0">'.basename($message['message']).'</p>
                                                                    </div>' : 
                                                                    
                                                                    "<p>$message[message]</p>"
                                                                )                                                            
                                                            )
                                                            .'
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
                                    <!-- START: ADDED HIDDEN INPUT -->
                                    <input type="hidden" id="file-path-input" value="" />
                                    <!-- END: ADDED HIDDEN INPUT -->
                                    <div class="chat-app-form-inputs">
                                        <div class="input-group input-group-merge me-1 form-send-message">

                                            <input type="text" class="form-control message" placeholder="<?php echo 'Write your message'; ?>" />
                                            <span class="input-group-text">
                                                <label for="attach-doc" class="attachment-icon form-label mb-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false">
                                                    <i data-feather="image" class="cursor-pointer text-secondary"></i>
                                                </label>
                                            </span>
                                        </div>
                                            
                                        <button type="submit" class="btn btn-primary send d-flex align-items-center justify-content-between" disabled>
                                            <i data-feather="send" class="d-lg-none"></i><span class="d-none d-lg-block"><?php echo 'send'; ?></span>
                                        </button>
                                    </div>
                                    <div class="collapse" id="collapseExample">
                                        <div class="chat-app-form-files">
                                            <?php 
                                                $input = array(
                                                    "label"         => "",
                                                    "type"          => "dropArea", //dropArea , avatar, file
                                                    "name_id"       => "customFile",
                                                    "accept"        => ".png, .jpg, .jpeg, '.gif', '.bmp' , 'pdf' , '.doc' ,'.docx' ,'.ppt' ,'.psd', '.ai','.zip', '.txt', '.flv', '.xls', '.csv', '.webp', '.mp3'",
                                                    "class"         => "",
                                                    "value"         => ""
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