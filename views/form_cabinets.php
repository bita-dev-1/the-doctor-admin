<?php  
    // Super Admin check
    if(!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin' || !empty($_SESSION['user']['cabinet_id'])){
        header('location:'.SITE_URL.'/');
        exit();
    }
    include_once 'header.php'; 

    $table = 'cabinets';
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
                        <h2 class="content-header-title float-start mb-0"><?= $breadcrumb .' un Cabinet'; ?></h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/cabinets">Cabinets</a></li>
                                <li class="breadcrumb-item active"><a><?= $breadcrumb .' un Cabinet'; ?></a></li>
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
                    <div class="col-md-12 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-12 mb-1">
                                        <?php
                                            $input = array(
                                                "label"         => "Nom du Cabinet",
                                                "type"          => "text",
                                                "name_id"       => "{$table}__name",
                                                "placeholder"   => "Nom du cabinet",
                                                "class"         => "",
                                                "value"         => $result['name'] ?? null
                                            );
                                            draw_input($input); 
                                        ?>
                                    </div>
                                    <div class="col-lg-12 col-md-12 col-12 mb-1">
                                        <?php 
                                            $input = array(
                                                "label"         => "Adresse",
                                                "type"          => "text",
                                                "name_id"       => "{$table}__address",
                                                "placeholder"   => "Adresse du cabinet",
                                                "class"         => "",
                                                "value"         => $result['address'] ?? null
                                            );      
                                            draw_input($input); 
                                        ?>
                                    </div>
                                    <div class="col-lg-12 col-md-12 col-12 mb-1">
                                        <?php 
                                            $input = array(
                                                "label"         => "Téléphone",
                                                "type"          => "text",
                                                "name_id"       => "{$table}__phone",
                                                "placeholder"   => "Numéro de téléphone",
                                                "class"         => "",
                                                "value"         => $result['phone'] ?? null
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
<?php  include_once 'foot.php'; ?>
<script>
     $(document).ready(function(){
         $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__name': {
                    required: true
                }
            }
        });
    });
</script>