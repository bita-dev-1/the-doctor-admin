
<?php  
    if(!isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 

    $table = 'specialty';
    $btn_text = $GLOBALS['language']['add'];
    $result = false;
    $where = "";
    $breadcrumb = $GLOBALS['language']['add']; 

    if(isset($id) && !empty($id)){
        $btn_text = $GLOBALS['language']['save'];
        $breadcrumb = $GLOBALS['language']['edit']; 
        $where = array( "column" => "id", "val" => $id );
        $result = dataById($where, $table)[0] ?? false;
    }

?>
        <div class="app-content content ">
            <div class="content-wrapper p-0">
                <div class="content-header row">
                    <div class="content-header-left col-md-9 col-12 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0"><?= $breadcrumb .' '. $GLOBALS['language']['specialty']; ?></h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                        <li class="breadcrumb-item active"><a><?= $breadcrumb .' '. $GLOBALS['language']['specialty']; ?></a>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>    
                <div class="col-md-12 col-12">
                
                    <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>" data-update="<?= customEncryption(json_encode($where)); ?>">                            
                        <?php set_csrf() ?>
                        <div class="row">
                            <div class="col-md-3 col-12">
                                <div class="card">
                                    <div class="card-body" >
                                        <div class="col-xs-6 col-md-12">
                                            <div class="mb-1 avatar-square">
                                                <?php 
                                                    $input = array(
                                                        "label"         => "",
                                                        "type"          => "avatar", //dropArea , avatar, file
                                                        "name_id"       => "{$table}__image",
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
                            <div class="col-md-9 col-12">
                                <div class="card">
                                    <div class="card-body" >
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['namefr'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__namefr",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['namefr'] ?? null
                                                    );
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['namear'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__namear",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $result['namear'] ?? null
                                                    );      
                                                    draw_input($input); 
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
         $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__namefr': {
                    required: true
                },
                '<?= $table; ?>__namear': {
                    required: true
                }
            }
        });
    });
</script>
