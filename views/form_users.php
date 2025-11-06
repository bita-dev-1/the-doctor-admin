<?php  
    if(!isset($_SESSION['user']['id'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 

    $table = 'users';
    $btn_text = $GLOBALS['language']['add'];
    $result = false;
    $where = "";
    $breadcrumb = $GLOBALS['language']['add']; 
    $user_role = $_SESSION['user']['role'];
    $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
    
    // --- START: NEW LOGIC FOR ROLE-BASED ACCESS ---
    $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));
    // --- END: NEW LOGIC ---

    // Define the title based on the context
    $title = $GLOBALS['language']['user'];
    if (stripos(request_path(), 'profile') !== false) {
        $id = $_SESSION['user']['id'];
        $title = $GLOBALS['language']['profile'];
    }

    if(isset($id) && !empty($id)){
        $btn_text = $GLOBALS['language']['save'];
        $breadcrumb = $GLOBALS['language']['edit']; 
        $where = array( "column" => "id", "val" => $id );
        
        $query = "SELECT u.*, c.id as communeId, w.id as willayaId FROM users u LEFT JOIN communes c ON c.id = u.commune_id LEFT JOIN willaya w ON w.id = c.id_willaya WHERE u.id = $id";
        // A regular admin can only edit users within their own cabinet
        if ($user_role === 'admin' && !empty($user_cabinet_id) && stripos(request_path(), 'profile') === false) {
            $query .= " AND u.cabinet_id = " . intval($user_cabinet_id);
        }
        $result = $GLOBALS['db']->select($query)[0] ?? false;

        $ticket_days = json_decode($result['tickets_day'] ?? '[]', true);
		$work_hours = json_decode($result['travel_hours'] ?? '[]', true);
    }
?>
<style>
    .tickets label, .work_hours label {
        font-size: 15px;
        font-weight: 900;
        min-width: 40%;
    }
</style>
        <div class="app-content content ">
            <div class="content-wrapper p-0">
                <div class="content-header row">
                    <div class="content-header-left col-md-9 col-12 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0"><?= $breadcrumb .' '. $title; ?></h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                        <li class="breadcrumb-item active"><a><?= $breadcrumb .' '. $title; ?></a>
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
                            <div class="col-md-4 col-12 pe-0" >
                                <div class="card mb-2">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title">Image 1</h4>
                                    </div>
                                    <div class="card-body" >
                                        <div class="avatar-square">
                                            <?php 
                                                $input = array(
                                                    "label"         => "",
                                                    "type"          => "avatar",
                                                    "name_id"       => "{$table}__image1",
                                                    "accept"        => ".png, .jpg, .jpeg, .jfif",
                                                    "class"         => "",
                                                    "value"         => $result['image1'] ?? null
                                                );
                                                draw_fileUpload($input);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 pe-0" >
                                <div class="card mb-2">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title">Image 2</h4>
                                    </div>
                                    <div class="card-body" >
                                        <div class="avatar-square">
                                            <?php 
                                                $input = array(
                                                    "label"         => "",
                                                    "type"          => "avatar",
                                                    "name_id"       => "{$table}__image2",
                                                    "accept"        => ".png, .jpg, .jpeg, .jfif",
                                                    "class"         => "",
                                                    "value"         => $result['image2'] ?? null
                                                );
                                                draw_fileUpload($input);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 " >
                                <div class="card mb-2">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title">Image 3</h4>
                                    </div>
                                    <div class="card-body" >
                                        <div class="avatar-square">
                                            <?php 
                                                $input = array(
                                                    "label"         => "",
                                                    "type"          => "avatar",
                                                    "name_id"       => "{$table}__image3",
                                                    "accept"        => ".png, .jpg, .jpeg, .jfif",
                                                    "class"         => "",
                                                    "value"         => $result['image3'] ?? null
                                                );
                                                draw_fileUpload($input);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-12 pe-0">
                                <?php if ($user_role === 'admin' || ($result && $result['role'] === 'doctor')) { ?>
                                <div class="card">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title"><?= $GLOBALS['language']['Tickets'] ?></h4>
                                    </div>
                                    <div class="card-body tickets" >
                                        <?php 
                                            $days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
                                            $db_day_keys = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
                                            foreach($days as $index => $day_lang_key) {
                                                $db_day_key = $db_day_keys[$index];
                                        ?>
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php
                                                $input = array(
                                                    "label"         => $GLOBALS['language'][$day_lang_key],
                                                    "type"          => "text",
                                                    "name_id"       => $db_day_key,
                                                    "placeholder"   => $GLOBALS['language'][$day_lang_key],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days[$db_day_key] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <?php } ?>
                                        <?php 
                                            $input = array( "label" => "", "type" => "hidden", "name_id" => "{$table}__tickets_day", "placeholder"   => "", "class" => "", "value" => isset($result['tickets_day']) && !empty($result['tickets_day']) ? htmlspecialchars($result['tickets_day']) : htmlspecialchars(json_encode( ["Samedi"=> 0, "Mardi"=> 0, "Mercredi"=> 0, "Lundi"=> 0, "Jeudi"=> 0, "Vendredi"=> 0, "Dimanche"=> 0])) );      
                                            draw_input($input); 
                                        ?>
                                    </div>
                                </div>
								<div class="card">
									<div class="card-header border-bottom py-1">
										<h4 class="card-title">Heures de travail</h4>
									</div>
									<div class="card-body work_hours">
                                        <?php foreach($days as $index => $day_lang_key) { $db_day_key = $db_day_keys[$index]; ?>
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array( "label" => $GLOBALS['language'][$day_lang_key], "type" => "text", "name_id" => "{$db_day_key}__from", "placeholder" => "À partir", "class" => "excluded me-50", "value" => $work_hours[$db_day_key]["from"] ?? "" );
											draw_input($input);
											$input = array( "label" => "", "type" => "text", "name_id" => "{$db_day_key}__to", "placeholder" => "De", "class" => "excluded", "value" => $work_hours[$db_day_key]["to"] ?? "" );
											draw_input($input);
											?>
										</div>
                                        <?php } ?>
										<?php
										$input = array( "label" => "", "type" => "hidden", "name_id" => "{$table}__travel_hours", "placeholder"   => "", "class" => "", "value" => isset($result['travel_hours']) && !empty($result['travel_hours']) ? htmlspecialchars($result['travel_hours']) : htmlspecialchars(json_encode(["Samedi" => ["from" => "", "to" => ""], "Mardi" => ["from" => "", "to" => ""], "Mercredi" => ["from" => "", "to" => ""], "Lundi" => ["from" => "", "to" => ""], "Jeudi" => ["from" => "", "to" => ""], "Vendredi" => ["from" => "", "to" => ""], "Dimanche" => ["from" => "", "to" => ""]])) );
										draw_input($input);
										?>
									</div>
								</div>
                                <?php } ?>
                            </div>
                            <div class="col-md-9 col-12">
                                <div class="card">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title"><?php echo $GLOBALS['language']['Details_of'].' '. $GLOBALS['language']['profile']; ?></h4>
                                    </div>
                                    <div class="card-body" >
                                        <div class="row">
                                            
                                            <?php if ($user_role === 'admin' && stripos(request_path(), 'profile') === false) : ?>
                                                
                                                <?php if ($is_super_admin) : ?>
                                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                        <?php
                                                            $input = array(
                                                                "label"         => "Cabinet",
                                                                "name_id"       => "{$table}__cabinet_id",
                                                                "placeholder"   => "Select Cabinet",
                                                                "class"         => "",
                                                                "serverSide"    => array(
                                                                    "table"     => "cabinets",
                                                                    "value"     => "id",
                                                                    "text"      => array("name"),
                                                                    "selected"  => $result['cabinet_id'] ?? null,
                                                                    "where"     => "deleted = 0"
                                                                )
                                                            );
                                                            draw_select($input);
                                                        ?>
                                                    </div>
                                                <?php else : // It's a Cabinet Admin ?>
                                                    <input type="hidden" name="<?= "{$table}__cabinet_id" ?>" value="<?= $user_cabinet_id ?>">
                                                <?php endif; ?>

                                                <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                    <?php
                                                        $roles_options = [
                                                            ["option_text" => $GLOBALS['language']['doctor'], "value" => "doctor"],
                                                            ["option_text" => $GLOBALS['language']['nurse'], "value" => "nurse"]
                                                        ];
                                                        if ($is_super_admin) {
                                                            array_unshift($roles_options, ["option_text" => $GLOBALS['language']['admin'], "value" => "admin"]);
                                                        }
                                                        
                                                        $input = array(
                                                            "label"         => $GLOBALS['language']['role'],
                                                            "name_id"       => "{$table}__role",
                                                            "placeholder"   => "Select Role",
                                                            "class"         => "",
                                                            "clientSideSelected" => $result['role'] ?? 'doctor',
                                                            "clientSide"   => $roles_options
                                                        );
                                                        draw_select($input);
                                                    ?>
                                                </div>
                                            <?php endif; ?>


											<div class="col-lg-2 col-md-2 col-3 mb-1">
												<?php 
                                                    $input = array( "label" => "Degré", "type" => "text", "name_id" => "{$table}__degree", "placeholder"   => "Degré", "class" => "", "value" => $result['degree'] ?? null );      
                                                    draw_input($input); 
                                                ?>
											</div>
                                            <div class="col-lg-5 col-md-5 col-9 mb-1">
                                                <?php 
                                                    $input = array( "label" => $GLOBALS['language']['firstname'], "type" => "text", "name_id" => "{$table}__first_name", "placeholder" => $GLOBALS['language']['firstname'], "class" => "", "value" => $result['first_name'] ?? null );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-5 col-md-5 col-12 mb-1"> 
                                                <?php 
                                                    $input = array( "label" => $GLOBALS['language']['lastname'], "type" => "text", "name_id" => "{$table}__last_name", "placeholder" => $GLOBALS['language']['lastname'], "class" => "", "value" => $result['last_name'] ?? null );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            
                                            <div class="col-lg-12 col-md-12 col-12 mb-1">  
                                                <?php
                                                    $input = array( "label" => $GLOBALS['language']['speciality'], "name_id" => "{$table}__specialty_id", "placeholder" => $GLOBALS['language']['speciality'], "class" => "", "his_parent" => "",
                                                        "serverSide" => array( "table" => "specialty", "value" => "id", "text" => array("namefr"), "selected" => $result['specialty_id'] ?? null, "where" => "" )
                                                    );  
                                                    draw_select($input); 
                                                ?>
                                            </div>
											<div class="col-lg-12 col-md-12 col-12 my-2">  
												<?php
													$switch = array( "label" => "Ouverture", "name_id" => "{$table}__is_opened", "class" => "", "checked" => $result['is_opened'] ?? 0 );
													draw_switch($switch);
												?>
											</div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array( "label" => $GLOBALS['language']['email'], "type" => "email", "name_id" => "{$table}__email", "placeholder" => $GLOBALS['language']['email'], "class" => "", "value" => $result['email'] ?? null );
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array( "label" => $GLOBALS['language']['phone'], "type" => "text", "name_id" => "{$table}__phone", "placeholder" => $GLOBALS['language']['phone'], "class" => "", "value" => $result['phone'] ?? null );
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">  
                                                <?php
                                                    $input = array( "label" => $GLOBALS['language']['willaya'], "name_id" => "regien", "placeholder"   => $GLOBALS['language']['willaya'], "class" => "excluded", "his_parent" => "",
                                                        "serverSide" => array( "table" => "willaya", "value" => "id", "text" => array("willaya"), "selected" => $result['willayaId'] ?? null, "where" => "" )
                                                    );  
                                                    draw_select($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">  
                                                <?php
                                                    $input = array( "label" => $GLOBALS['language']['commune'], "name_id" => "{$table}__commune_id", "placeholder" => $GLOBALS['language']['commune'], "class" => "", "his_parent" => "#regien",
                                                        "serverSide" => array( "table" => "communes", "value" => "id", "value_parent"  => "id_willaya", "text" => array("name"), "selected" => $result['communeId'] ?? null, "where" => "" )
                                                    );    
                                                    draw_select($input); 
                                                ?>       
                                            </div>
                                            
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array( "label" => "Facebook", "type" => "text", "name_id" => "{$table}__facebook", "placeholder" => "Facebook", "class" => "", "value" => $result['facebook'] ?? null );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1"> 
                                                <?php 
                                                    $input = array( "label" => "Instagram", "type" => "text", "name_id" => "{$table}__instagram", "placeholder" => "Instagram", "class" => "", "value" => $result['instagram'] ?? null );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            
                                            <!-- REMOVED: Password fields are removed for creation -->
                                            
                                            <div class="col-lg-12 col-md-12 col-12 mb-1"> 
                                                <?php 
                                                    $textArea = array( "label" => $GLOBALS['language']['description'], "rows" => "6", "name_id" => "{$table}__description", "placeholder" => $GLOBALS['language']['description'], "class" => "", "value" => $result['description'] ?? null );
                                                    draw_text_area($textArea); 
                                                ?>
                                            </div>

                                            <div class="col-12 mb-1">   
                                                <?php                                                
                                                    $button = array( "text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary mt-2 w-auto ms-1 me-1" );                               
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
    </div>
</div>
<?php  include_once 'foot.php'; ?>
<script>
     $(document).ready(function(){
        // MODIFIED: Removed password validation rules for creation
         $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__first_name': { required: true },
                '<?= $table; ?>__last_name': { required: true },
                '<?= $table; ?>__email': { required: true, email: true },
                '<?= $table; ?>__phone': { required: true },
                'regien': { required: true },
                '<?= $table; ?>__commune_id': { required: true }
            }
        });

        $(document).on('input', '.tickets input', function(e){
            e.preventDefault();
            e.stopPropagation();
            let tickets = {};
            $('.tickets input').each(function() {
                tickets[$(this).attr('id')] = $(this).val();
            });
            $("#<?= $table; ?>__tickets_day").val(JSON.stringify(tickets));
        });
		 
		 $(document).on('input', '.work_hours input', function(e){
            e.preventDefault();
            e.stopPropagation();
            let workHours = {};
            $('.work_hours .d-flex').each(function() {
                let day = $(this).find('input:first').attr('id').split('__')[0];
                let from = $(this).find('input[id$="__from"]').val();
                let to = $(this).find('input[id$="__to"]').val();
                workHours[day] = { "from": from, "to": to };
            });
            $("#<?= $table; ?>__travel_hours").val(JSON.stringify(workHours));
        });
    });
</script>