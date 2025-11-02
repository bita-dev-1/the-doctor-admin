
<?php  
    if(!isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 

    $tableName = 'users';
    $btn_text = $GLOBALS['language']['add'];
    $result = false;
    $whereCondition = "";

    if(isset($id) && !empty($id)){
        $btn_text = $GLOBALS['language']['save'];
        $whereCondition = array( "column" => "id", "val" => $id );
        $result = dataById($whereCondition, $tableName)[0] ?? false;
    }

?>
        <div class="app-content content ">
            <div class="content-wrapper p-0">
                <div class="content-header row">
                    <div class="content-header-left col-md-9 col-12 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0"><?= (isset($id) && !empty($id) ? $GLOBALS['language']['edit'] : $GLOBALS['language']['add']) .' '. $GLOBALS['language']['admin']; ?></h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/dashboard"><?= $GLOBALS['language']['Home']; ?></a></li>
                                        <li class="breadcrumb-item active"><a><?= (isset($id) && !empty($id) ? $GLOBALS['language']['edit'] : $GLOBALS['language']['add']) .' '. $GLOBALS['language']['admin']; ?></a>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>    
                <div class="col-md-12 col-12">
                
                    <form id="codexForm" method="post" role="form" data-express="<?= customEncryption($tableName); ?>" data-update="<?= customEncryption(json_encode($whereCondition)); ?>">                            
                        <?php set_csrf() ?>
                        <div class="row">
                            <div class="col-md-12 col-12">
                                <div class="card">
                                    <div class="card-body" >
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['username'],
                                                        "type"          => "text",
                                                        "name_id"       => "username",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['username'] ?? null
                                                    );
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['firstname'],
                                                        "type"          => "text",
                                                        "name_id"       => "first_name",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['first_name'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1"> 
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['lastname'],
                                                        "type"          => "text",
                                                        "name_id"       => "last_name",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['last_name'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['email'],
                                                        "type"          => "email",
                                                        "name_id"       => "email",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['email'] ?? null
                                                    );
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">  
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['phone1'],
                                                        "type"          => "number",
                                                        "name_id"       => "phone1",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['phone1'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">  
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['phone2'],
                                                        "type"          => "number",
                                                        "name_id"       => "phone2",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['phone2'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['state'],
                                                        "name_id"       => "state",
                                                        "placeholder"   => "state",
                                                        "class"         => "",
                                                        "his_parent"    => "",
                                                        "clientSideSelected"    => $result['state'] ?? 0,
                                                        "clientSide"   => array(
                                                            array(
                                                                "option_text"    => "Desactive",
                                                                "value"          => "0",
                                                            ),
                                                            array(
                                                                "option_text"    => "Active",
                                                                "value"          => "1",
                                                            )
                                                        )
                                                    );
                                                    
                                                    draw_select($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                <?php 
                                                    if(!isset($id) || empty($id)){
                                                        $input = array(
                                                            "label"         => $GLOBALS['language']['password'],
                                                            "type"          => "password",
                                                            "name_id"       => "password",
                                                            "placeholder"   => "",
                                                            "class"         => "",
                                                            "value"         => ""
                                                        );
                                                        draw_input($input); 
                                                    }
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">    
                                                <?php 
                                                    if(!isset($id) || empty($id)){
                                                        $input = array(
                                                            "label"         => $GLOBALS['language']['cpassword'],
                                                            "type"          => "password",
                                                            "name_id"       => "cpassword",
                                                            "placeholder"   => "",
                                                            "class"         => "excluded",
                                                            "value"         => ""
                                                        );      
                                                        draw_input($input); 
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <?php 
                                            $button = array(
                                                "text"          => $btn_text,
                                                "type"          => "submit",
                                                "name_id"       => "submit",
                                                "class"         => "btn-primary mt-2"
                                            );                               
                                            draw_button($button); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php  include_once 'foot.php'; ?>
<script>
     $(document).ready(function(){
         $('#codexForm').validate({
            rules: {
                'username': {
                    required: true
                },
                'first_name': {
                    required: true
                },
                'last_name': {
                    required: true
                },
                'email': {
                    required: true,
                    email: true
                },
                'percentage': {
                    required: true
                },
                'balance': {
                    required: true
                },
                'phone1': {
                    required: true
                },
                'cpassword': {
                    required: true,
                    equalTo: '#password'
                },
                'password': {
                    required: true
                }
            }
        });
    });
</script>
