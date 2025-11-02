<?php
  if(!isset($_SESSION['user'])){
    header('location:'.SITE_URL.'/login');
  } 

  include_once "header.php";

  $btn_text = $GLOBALS['language']['save'];
  $where = array( "column" => "id", "val" => $_SESSION['user']['data'][0]['id'] );
  $result = dataById($where, 'users')[0] ?? false;

?>
<div class="app-content content ecommerce-application">
  <div class="content-wrapper container-xxl p-0"> 
    <div class="content-header row">
      <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-start mb-0"><?= $GLOBALS['language']['My account']; ?></h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a>
                        </li>
                        <li class="breadcrumb-item active"><?= $GLOBALS['language']['My account']; ?></li>
                    </ol>
                </div>
            </div>
        </div>
      </div>
    </div> 

    <div class="main-body">  
        <form id="codexForm" method="post" role="form" data-express="<?= customEncryption('users'); ?>" data-update="<?= customEncryption(json_encode($where)); ?>">
            <?php set_csrf(); ?>
            <div class="row gutters-sm">
                <div class="col-md-3 col-12">
                    <div class="card">
                        <div class="card-body" >
                            <div class="col-xs-6 col-md-12">
                                <div class="mb-1 avatar-square">
                                    <?php 
                                        $input = array(
                                            "label"         => "",
                                            "type"          => "avatar", //dropArea , avatar, file
                                            "name_id"       => "image",
                                            "accept"        => ".png, .jpg, .jpeg, .jfif",
                                            "class"         => "",
                                            "value"         => $result['image'] ?? null
                                        );
                                        draw_fileUpload($input);
                                    ?>
                                </div>
                            </div> 
                        </div>
                    </div>
                </div>
                <div class="col-md-9 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-12 mb-1">
                                    <?php 
                                        $input = array(
                                            "label"         => $GLOBALS['language']['username'],
                                            "type"          => "text",
                                            "name_id"       => "username",
                                            "placeholder"   => $GLOBALS['language']['username'],
                                            "class"         => "",
                                            "value"         => $result['username'] ?? null
                                        );
                                        
                                        draw_input($input); 
                                    ?>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-1">
                                    <?php 
                                        $input = array(
                                            "label"         => $GLOBALS['language']['firstname'],
                                            "type"          => "text",
                                            "name_id"       => "first_name",
                                            "placeholder"   => $GLOBALS['language']['firstname'],
                                            "class"         => "",
                                            "value"         => $result['first_name'] ?? null
                                        );
                                        
                                        draw_input($input); 
                                    ?>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-1">
                                    <?php 
                                        $input = array(
                                            "label"         => $GLOBALS['language']['lastname'],
                                            "type"          => "text",
                                            "name_id"       => "last_name",
                                            "placeholder"   => $GLOBALS['language']['lastname'],
                                            "class"         => "",
                                            "value"         => $result['last_name'] ?? null
                                        );
                                        
                                        draw_input($input); 
                                    ?>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-1">
                                    <?php 
                                        $input = array(
                                            "label"         => $GLOBALS['language']['email'],
                                            "type"          => "text",
                                            "name_id"       => "email",
                                            "placeholder"   => $GLOBALS['language']['email'],
                                            "class"         => "",
                                            "value"         => $result['email'] ?? null
                                        );
                                        
                                        draw_input($input); 
                                    ?>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-1">
                                    <?php 
                                        $input = array(
                                            "label"         => $GLOBALS['language']['phone'],
                                            "type"          => "text",
                                            "name_id"       => "phone",
                                            "placeholder"   => $GLOBALS['language']['phone'],
                                            "class"         => "",
                                            "value"         => $result['phone'] ?? null
                                        );
                                        
                                        draw_input($input); 
                                    ?>
                                </div>
                                <div class="col-md-6 col-sm-6 col-12 mb-1">
                                    <?php 
                                        $input = array(
                                            "label"         => $GLOBALS['language']['phone_2'],
                                            "type"          => "text",
                                            "name_id"       => "phone_2",
                                            "placeholder"   => $GLOBALS['language']['phone_2'],
                                            "class"         => "",
                                            "value"         => $result['phone_2'] ?? null
                                        );
                                        
                                        draw_input($input); 
                                    ?>
                                </div>

                                <div class="modal-footer pb-0">
                                    <?php 
                                        $button = array(
                                            "text"          => "Sauvgarder",
                                            "type"          => "submit",
                                            "name_id"       => "submit",
                                            "class"         => "btn-primary"
                                        );
                                        
                                        draw_button($button); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

  </div>
</div>

<?php include_once "foot.php"; ?>