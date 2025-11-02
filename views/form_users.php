
<?php  
    if(!isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 

    

    $table = 'doctor';
    $btn_text = $GLOBALS['language']['add'];
    $result = false;
    $where = "";
    $breadcrumb = $GLOBALS['language']['add']; 

    $type = 0;
    $title = $GLOBALS['language']['doctor'];
    if(stripos(request_path(), 'admins/') !== false){
        $type = 1;
        $title = $GLOBALS['language']['admin'];
    }

    if(stripos(request_path(), 'profile') !== false){
        $id = $_SESSION['user']['data'][0]['id'];
        $title = $GLOBALS['language']['profile'];
    }

    if(isset($id) && !empty($id)){
        $btn_text = $GLOBALS['language']['save'];
        $breadcrumb = $GLOBALS['language']['edit']; 
        $where = array( "column" => "id", "val" => $id );
        $result = getUsers($id)[0] ?? false;
        $ticket_days = json_decode($result['tickets_day'], true) ?? [];
		$work_hours = json_decode($result['travel_hours'], true) ?? [];
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
                                                    "type"          => "avatar", //dropArea , avatar, file
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
                                                    "type"          => "avatar", //dropArea , avatar, file
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
                                                    "type"          => "avatar", //dropArea , avatar, file
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
                                <?php  ?>
                                <div class="card">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title"><?= $GLOBALS['language']['Tickets'] ?></h4>
                                    </div>
                                    <div class="card-body tickets" >
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['sunday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Dimanche",
                                                    "placeholder"   => $GLOBALS['language']['sunday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Dimanche'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php 
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['monday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Lundi",
                                                    "placeholder"   => $GLOBALS['language']['monday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Lundi'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php 
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['tuesday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Mardi",
                                                    "placeholder"   => $GLOBALS['language']['tuesday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Mardi'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php 
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['wednesday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Mercredi",
                                                    "placeholder"   => $GLOBALS['language']['wednesday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Mercredi'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php 
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['thursday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Jeudi",
                                                    "placeholder"   => $GLOBALS['language']['thursday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Jeudi'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <div class="col-12 d-flex align-items-center mb-1">
                                            <?php 
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['friday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Vendredi",
                                                    "placeholder"   => $GLOBALS['language']['friday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Vendredi'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <div class="col-12 d-flex align-items-center">
                                            <?php 
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['saturday'],
                                                    "type"          => "text",
                                                    "name_id"       => "Samedi",
                                                    "placeholder"   => $GLOBALS['language']['saturday'],
                                                    "class"         => "excluded",
                                                    "value"         => $ticket_days['Samedi'] ?? 0
                                                );      
                                                draw_input($input); 
                                            ?>
                                        </div>
                                        <?php 
                                            $input = array(
                                                "label"         => "",
                                                "type"          => "hidden",
                                                "name_id"       => "{$table}__tickets_day",
                                                "placeholder"   => "",
                                                "class"         => "",
                                                "value"         => isset($result['tickets_day']) && !empty($result['tickets_day']) ? htmlspecialchars($result['tickets_day']) : htmlspecialchars(json_encode( ["Samedi"=> 0, "Mardi"=> 0, "Mercredi"=> 0, "Lundi"=> 0, "Jeudi"=> 0, "Vendredi"=> 0, "Dimanche"=> 0]))
                                            );      
                                            draw_input($input); 
                                        ?>
                                    </div>
                                </div>
								
								
								
								
								
								
								
								<div class="card">
									<div class="card-header border-bottom py-1">
										<h4 class="card-title">Heures de travail</h4>
									</div>
									<div class="card-body work_hours">
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['sunday'],
												"type"          => "text",
												"name_id"       => "Dimanche__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Dimanche']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Dimanche__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Dimanche']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['monday'],
												"type"          => "text",
												"name_id"       => "Lundi__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Lundi']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Lundi__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Lundi']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['tuesday'],
												"type"          => "text",
												"name_id"       => "Mardi__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Mardi']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Mardi__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Mardi']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['wednesday'],
												"type"          => "text",
												"name_id"       => "Mercredi__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Mercredi']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Mercredi__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Mercredi']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['thursday'],
												"type"          => "text",
												"name_id"       => "Jeudi__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Jeudi']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Jeudi__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Jeudi']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<div class="col-12 d-flex align-items-center mb-1">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['friday'],
												"type"          => "text",
												"name_id"       => "Vendredi__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Vendredi']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Vendredi__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Vendredi']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<div class="col-12 d-flex align-items-center">
											<?php
											$input = array(
												"label"         => $GLOBALS['language']['saturday'],
												"type"          => "text",
												"name_id"       => "Samedi__from",
												"placeholder"   => "À partir",
												"class"         => "excluded me-50",
												"value"         => $work_hours['Samedi']["from"] ?? ""
											);
											draw_input($input);
											$input = array(
												"label"         => "",
												"type"          => "text",
												"name_id"       => "Samedi__to",
												"placeholder"   => "De",
												"class"         => "excluded",
												"value"         => $work_hours['Samedi']["to"] ?? ""
											);
											draw_input($input);
											?>
										</div>
										<?php
										$input = array(
											"label"         => "",
											"type"          => "hidden",
											"name_id"       => "{$table}__travel_hours",
											"placeholder"   => "",
											"class"         => "",
											"value"         => isset($result['travel_hours']) && !empty($result['travel_hours']) ? htmlspecialchars($result['travel_hours']) : htmlspecialchars(json_encode(["Samedi" => ["from" => "", "to" => ""], "Mardi" => ["from" => "", "to" => ""], "Mercredi" => ["from" => "", "to" => ""], "Lundi" => ["from" => "", "to" => ""], "Jeudi" => ["from" => "", "to" => ""], "Vendredi" => ["from" => "", "to" => ""], "Dimanche" => ["from" => "", "to" => ""]]))
										);
										draw_input($input);
										?>
									</div>
								</div>
								
								
								
								
                            </div>
                            <div class="col-md-9 col-12">
                                <div class="card">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title"><?php echo $GLOBALS['language']['Details_of'].' '. $GLOBALS['language']['profile']; ?></h4>
                                        <?php if($type == 0){ ?>
                                        <h4  class="d-flex align-items-center mb-0">
                                            <div class="d-flex align-items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="88 88 360 360"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"> <path d="M1285 3830 c-163 -33 -300 -138 -374 -286 -58 -117 -56 -80 -56 -1094 l0 -935 22 -70 c52 -164 170 -284 347 -353 31 -12 96 -17 261 -21 l220 -6 5 -140 c4 -124 8 -144 27 -171 29 -41 79 -64 137 -64 42 0 65 13 331 190 l285 190 768 0 c841 0 816 -1 939 61 124 63 234 206 268 350 22 96 22 1852 0 1948 -45 190 -207 351 -397 396 -54 13 -254 15 -1403 14 -737 -1 -1358 -5 -1380 -9z m2738 -215 c103 -27 167 -82 216 -185 l26 -55 0 -915 c0 -862 -1 -918 -18 -968 -25 -70 -101 -152 -169 -183 l-53 -24 -802 -3 -803 -2 -247 -165 -248 -165 -5 135 c-5 131 -6 135 -33 162 l-27 28 -278 5 -277 5 -57 28 c-62 30 -115 83 -150 149 l-23 43 -3 929 c-3 1032 -6 979 64 1070 39 51 105 94 173 112 35 10 350 13 1351 13 1125 1 1314 -1 1363 -14z"/> <path d="M2065 3126 c-42 -18 -56 -37 -143 -183 l-77 -128 -165 -36 c-159 -36 -165 -38 -201 -76 -21 -23 -39 -54 -43 -76 -13 -67 7 -104 124 -237 80 -90 108 -130 105 -145 -11 -57 -27 -289 -21 -320 14 -74 98 -134 171 -122 18 3 97 33 176 67 l143 61 151 -66 c84 -36 165 -65 181 -65 41 1 110 34 129 63 32 49 35 84 19 248 l-15 159 104 118 c57 64 109 127 116 139 19 38 14 116 -12 153 -33 51 -68 66 -227 101 -80 17 -150 35 -156 40 -6 5 -42 62 -79 126 -38 65 -78 129 -89 142 -42 48 -128 65 -191 37z m150 -379 c32 -55 68 -105 79 -112 12 -8 77 -26 146 -41 69 -15 127 -29 129 -30 2 -2 -33 -45 -79 -96 -116 -132 -113 -121 -97 -283 15 -158 31 -148 -127 -79 -54 24 -113 44 -132 44 -18 0 -85 -23 -149 -51 -64 -28 -118 -49 -120 -47 -2 2 0 44 5 93 5 50 10 116 10 147 l0 57 -90 103 c-49 57 -90 106 -90 109 0 3 57 18 126 33 70 15 136 33 146 39 11 7 51 65 90 130 62 106 71 117 82 100 7 -10 39 -62 71 -116z"/> <path d="M3054 2976 c-17 -8 -39 -24 -48 -37 -21 -30 -21 -88 0 -118 35 -49 56 -51 465 -51 424 0 433 1 463 65 9 18 16 38 16 45 0 7 -7 27 -16 45 -30 64 -39 65 -466 65 -300 -1 -390 -4 -414 -14z"/> <path d="M3038 2543 c-56 -35 -66 -117 -19 -164 l29 -29 415 0 c400 0 415 1 440 20 38 30 52 80 33 123 -27 67 -29 67 -473 67 -353 -1 -401 -2 -425 -17z"/> <path d="M3019 2101 c-47 -48 -37 -130 21 -164 24 -15 62 -17 265 -17 266 0 285 4 311 67 19 43 5 93 -33 123 -24 19 -40 20 -280 20 l-255 0 -29 -29z"/> </g> </svg>
                                                <span class="ms-50"><?= $result['recomondation'] ?? 0 ?></span>
                                            </div>

                                            <div class="d-flex align-items-center ms-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="88 88 360 360"> <g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"> <path d="M2450 3826 c-314 -46 -607 -169 -840 -354 -82 -66 -211 -196 -275 -277 -79 -102 -464 -679 -477 -715 -7 -20 -5 -37 5 -62 18 -42 376 -582 459 -691 241 -316 599 -537 1019 -629 89 -19 134 -22 319 -23 184 0 231 3 326 22 376 77 726 278 965 556 39 45 174 237 301 426 216 323 230 347 225 381 -6 49 -435 691 -539 808 -257 290 -592 475 -993 548 -115 21 -379 26 -495 10z m460 -221 c185 -30 382 -104 535 -200 235 -148 335 -256 595 -645 111 -166 199 -308 196 -315 -15 -40 -376 -566 -442 -645 -211 -252 -520 -429 -864 -496 -142 -27 -408 -25 -550 5 -305 65 -546 195 -766 415 -111 112 -136 145 -323 424 l-203 304 177 266 c214 321 278 405 386 506 340 315 803 455 1259 381z"/> <path d="M2480 3395 c-327 -66 -604 -304 -715 -615 -101 -282 -64 -600 97 -847 293 -449 889 -573 1333 -278 300 200 467 555 425 905 -51 413 -355 749 -755 831 -106 22 -287 24 -385 4z m395 -225 c242 -76 422 -250 501 -485 39 -115 45 -286 15 -405 -35 -144 -91 -240 -201 -350 -110 -110 -206 -166 -350 -201 -119 -30 -290 -24 -405 15 -407 137 -616 578 -464 977 91 237 297 412 549 465 93 19 265 12 355 -16z"/> <path d="M2623 2975 c-33 -14 -63 -59 -63 -95 0 -61 60 -110 134 -110 58 0 142 -37 191 -86 57 -55 92 -128 101 -213 5 -53 12 -71 35 -93 47 -47 129 -35 164 24 14 25 16 43 11 99 -15 169 -116 329 -260 413 -97 57 -253 88 -313 61z"/> </g> </svg>
                                                <span class="ms-50"><?= $result['views'] ?? 0 ?></span>
                                            </div>
                                        </h4>
                                        <?php } ?>
                                    </div>
                                    <div class="card-body" >
                                        <div class="row">      
											<div class="col-lg-2 col-md-2 col-3 mb-1">
												<?php 
                                                    $input = array(
                                                        "label"         => "Degré",
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__degree",
                                                        "placeholder"   => "Degré",
                                                        "class"         => "",
                                                        "value"         => $result['degree'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
											</div>
                                            <div class="col-lg-5 col-md-5 col-9 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => "",
                                                        "type"          => "hidden",
                                                        "name_id"       => "{$table}__type",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $type
                                                    );      
                                                    draw_input($input); 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['firstname'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__first_name",
                                                        "placeholder"   => $GLOBALS['language']['firstname'],
                                                        "class"         => "",
                                                        "value"         => $result['first_name'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-5 col-md-5 col-12 mb-1"> 
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['lastname'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__last_name",
                                                        "placeholder"   => $GLOBALS['language']['lastname'],
                                                        "class"         => "",
                                                        "value"         => $result['last_name'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-12 col-md-12 col-12 mb-1">  
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['speciality'],
                                                        "name_id"       => "{$table}__specialty_id",
                                                        "placeholder"   => $GLOBALS['language']['speciality'],
                                                        "class"         => "",
                                                        "his_parent"    => "",
                                                        "serverSide"        => array(
                                                            "table"         => "specialty",
                                                            "value"         => "id",
                                                            "value_parent"  => "",
                                                            "text"          => array("namefr"),
                                                            "selected"      => $result['specialty_id'] ?? null,
                                                            "where"         => ""
                                                        )
                                                    );  
                                                    draw_select($input); 
                                                ?>
                                            </div>
											<div class="col-lg-12 col-md-12 col-12 my-2">  
												<?php
													$switch = array(
														"label"         => "Ouverture",
														"name_id"       => "{$table}__is_opened",
														"class"         => "",
														"checked"       => $result['is_opened'] ?? 0
													);

													draw_switch($switch);
												?>
											</div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['email'],
                                                        "type"          => "email",
                                                        "name_id"       => "{$table}__email",
                                                        "placeholder"   => $GLOBALS['language']['email'],
                                                        "class"         => "",
                                                        "value"         => $result['email'] ?? null
                                                    );
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['phone'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__phone",
                                                        "placeholder"   => $GLOBALS['language']['phone'],
                                                        "class"         => "",
                                                        "value"         => $result['phone'] ?? null
                                                    );
                                                    
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">  
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['willaya'],
                                                        "name_id"       => "{$table}__regien",
                                                        "placeholder"   => $GLOBALS['language']['willaya'],
                                                        "class"         => "excluded",
                                                        "his_parent"    => "",
                                                        "serverSide"        => array(
                                                            "table"         => "willaya",
                                                            "value"         => "id",
                                                            "value_parent"  => "",
                                                            "text"          => array("willaya"),
                                                            "selected"      => $result['willayaId'] ?? null,
                                                            "where"         => ""
                                                        )
                                                    );  
                                                    draw_select($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">  
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['commune'],
                                                        "name_id"       => "{$table}__commune_id",
                                                        "placeholder"   => $GLOBALS['language']['commune'],
                                                        "class"         => "",
                                                        "his_parent"    => "#{$table}__regien",
                                                        "serverSide"        => array(
                                                            "table"         => "communes",
                                                            "value"         => "id",
                                                            "value_parent"  => "id_willaya",
                                                            "text"          => array("name"),
                                                            "selected"      => $result['communeId'] ?? null, 
                                                            "where"         => ""
                                                        )
                                                    );    
                                                    draw_select($input); 
                                                ?>       
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['lat'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__lat",
                                                        "placeholder"   => $GLOBALS['language']['lat'],
                                                        "class"         => "",
                                                        "value"         => $result['lat'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1"> 
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['lng'],
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__lang",
                                                        "placeholder"   => $GLOBALS['language']['lng'],
                                                        "class"         => "",
                                                        "value"         => $result['lang'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => "Facebook",
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__facebook",
                                                        "placeholder"   => "Facebook",
                                                        "class"         => "",
                                                        "value"         => $result['facebook'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1"> 
                                                <?php 
                                                    $input = array(
                                                        "label"         => "Instagram",
                                                        "type"          => "text",
                                                        "name_id"       => "{$table}__instagram",
                                                        "placeholder"   => "Instagram",
                                                        "class"         => "",
                                                        "value"         => $result['instagram'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <?php if(!isset($id) || empty($id)){ ?>
                                                <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                    <?php 
                                                        $input = array(
                                                            "label"         => $GLOBALS['language']['password'],
                                                            "type"          => "password",
                                                            "name_id"       => "{$table}__password",
                                                            "placeholder"   => $GLOBALS['language']['password'],
                                                            "class"         => "",
                                                            "value"         => ""
                                                        );
                                                        draw_input($input); 
                                                    ?>
                                                </div>
                                                <div class="col-xs-6 col-md-6">    
                                                    <?php 
                                                        $input = array(
                                                            "label"         => $GLOBALS['language']['cpassword'],
                                                            "type"          => "password",
                                                            "name_id"       => "{$table}__cpassword",
                                                            "placeholder"   => $GLOBALS['language']['cpassword'],
                                                            "class"         => "excluded",
                                                            "value"         => ""
                                                        );      
                                                        draw_input($input); 
                                                    ?>
                                                </div>
                                            <?php } ?>
                                            <div class="col-lg-12 col-md-12 col-12 mb-1"> 
                                                <?php 
                                                    $textArea = array(
                                                        "label"         => $GLOBALS['language']['description'],
                                                        "rows"          => "6",
                                                        "name_id"       => "{$table}__description",
                                                        "placeholder"   => $GLOBALS['language']['description'],
                                                        "class"         => "",
                                                        "value"         => $result['description'] ?? null
                                                    );
                                                    
                                                    draw_text_area($textArea); 
                                                ?>
                                            </div>

                                            <div class="col-12 mb-1">   
                                                <?php                                                
                                                    $button = array(
                                                        "text"          => $btn_text,
                                                        "type"          => "submit",
                                                        "name_id"       => "submit",
                                                        "class"         => "btn-primary mt-2 w-auto ms-1 me-1"
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
    </div>
</div>
<?php  include_once 'foot.php'; ?>
<script>
     $(document).ready(function(){
        $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__username': {
                    required: true
                },
                '<?= $table; ?>__first_name': {
                    required: true
                },
                '<?= $table; ?>__last_name': {
                    required: true
                },
                '<?= $table; ?>__email': {
                    required: true,
                    email: true
                },
                '<?= $table; ?>__phone': {
                    required: true
                },
                '<?= $table; ?>__regien': {
                    required: true
                },
                '<?= $table; ?>__commune_id': {
                    required: true
                },
                '<?= $table; ?>__cpassword': {
                    required: true,
                    equalTo: '#<?= $table; ?>__password'
                },
                '<?= $table; ?>__password': {
                    required: true
                }
            }
        });

        $(document).on('input', '.tickets input', function(e){
            e.preventDefault();
            e.stopPropagation();

            let self = $(this), tickets = JSON.parse($("#<?= $table; ?>__tickets_day").val()); //{ Samedi: 0, Mardi: 0, Mercredi: 0, Lundi: 0, Jeudi: 0, Vendredi: 0, Dimanche: 0 }
            tickets[self.attr('id')] = self.val();
            $("#<?= $table; ?>__tickets_day").val(JSON.stringify(tickets));
        });
		 
		 $(document).on('input', '.work_hours input', function(e){
            e.preventDefault();
            e.stopPropagation();

            let self = $(this), tickets = JSON.parse($("#<?= $table; ?>__travel_hours").val()); 
			var arr = self.attr('id').split('__');
			 
            tickets[arr[0]][arr[1]] = self.val();
            $("#<?= $table; ?>__travel_hours").val(JSON.stringify(tickets));
        });
    });
</script>
