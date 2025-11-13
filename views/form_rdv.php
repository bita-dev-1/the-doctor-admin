<?php  
    // MODIFIED: Corrected security check to allow all logged-in users
    if(!isset($_SESSION['user']['id'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 

    $table = 'specialty'; // This seems incorrect for an RDV form, but I will leave it as is per your rules.
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
                                <h2 class="content-header-title float-start mb-0"><?= $breadcrumb .' '. $GLOBALS['language']['rdv']; ?></h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                        <li class="breadcrumb-item active"><a><?= $breadcrumb .' '. $GLOBALS['language']['rdv']; ?></a>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>    
                <div class="col-md-12 col-12">
                
                    <form class="rdvForm" method="post" role="form" data-express="<?= customEncryption($table); ?>" data-update="<?= customEncryption(json_encode($where)); ?>">                            
                        <?php set_csrf() ?>
                        <div class="row">
                            <div class="col-md-12 col-12">
                                <div class="card">
                                    <div class="card-body" >
                                        <div class="row">
                                            <?php if( $_SESSION['user']['role'] == 'admin' ){ ?>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                <?php
                                                    $doctor_where_clause = "role = 'doctor' AND deleted = 0";
                                                    if (!empty($_SESSION['user']['cabinet_id'])) {
                                                        $doctor_where_clause .= " AND cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
                                                    }
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['doctor'],
                                                        "name_id"       => "doctor_id",
                                                        "placeholder"   => $GLOBALS['language']['doctor'],
                                                        "class"         => "",
                                                        "his_parent"    => "",
                                                        "serverSide"        => array(
                                                            "table"         => "users",
                                                            "value"         => "id",
                                                            "value_parent"  => "",
                                                            "text"          => array("first_name", "last_name"),
                                                            "selected"      => $result['doctor_id'] ?? null,
                                                            "where"         => $doctor_where_clause
                                                        )
                                                    );  
                                                    draw_select($input); 
                                                ?>
                                            </div>
                                            <?php 
                                                }else{ 
                                                    $input = array(
                                                        "label"         => "",
                                                        "type"          => "hidden",
                                                        "name_id"       => "doctor_id",
                                                        "placeholder"   => "",
                                                        "class"         => "",
                                                        "value"         => $_SESSION['user']['id']
                                                    );      
                                                    draw_input($input); 
                                                } 
                                            ?>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                <?php
                                                    // START: MODIFIED - The 'where' clause is now empty, making the patient list global.
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['patient'],
                                                        "name_id"       => "patient_id",
                                                        "placeholder"   => 'Rechercher par nom, téléphone, ID...',
                                                        "class"         => "",
                                                        "his_parent"    => "",
                                                        "serverSide"        => array(
                                                            "table"         => "patient",
                                                            "value"         => "id",
                                                            "value_parent"  => "",
                                                            "text"          => array("first_name", "last_name", "phone", "id"),
                                                            "selected"      => $result['patient_id'] ?? null,
                                                            "where"         => "deleted = 0" 
                                                        )
                                                    );  
                                                    draw_select($input); 
                                                    // END: MODIFIED
                                                ?>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
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
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
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
                                            <div class="col-lg-4 col-md-6 col-12 mb-1">
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
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                <?php 
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['date'],
                                                        "type"          => "text",
                                                        "name_id"       => "date",
                                                        "placeholder"   => "YYYY-MM-DD",
                                                        "class"         => "picker",
                                                        "value"         => $result['date'] ?? null
                                                    );      
                                                    draw_input($input); 
                                                ?>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                <?php
                                                    $input = array(
                                                        "label"         => $GLOBALS['language']['rdv_num'],
                                                        "name_id"       => "rdv_num",
                                                        "placeholder"   => $GLOBALS['language']['rdv_num'],
                                                        "class"         => "rdv_num",
                                                        "attr"          => "data-search = '-1' ",
                                                        "his_parent"    => "",
                                                        "clientSideSelected"    => "",
                                                        "clientSide"   => array()
                                                    );
                                                    
                                                    draw_select($input); 
                                                ?>
                                            </div>
                                        </div>
                                        <?php 
                                            if(isset($id) && !empty($id)){
                                                $input = array(
                                                    "label"         => "",
                                                    "type"          => "hidden",
                                                    "name_id"       => "rdv_id",
                                                    "placeholder"   => "",
                                                    "class"         => "",
                                                    "value"         => $id
                                                );      
                                                draw_input($input); 
                                            }

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
        $('.rdvForm').validate({
            rules: {
                'doctor_id': {
                    required: true
                },
                'date': {
                    required: true
                },
                'rdv_num': {
                    required: true
                },
                'first_name': {
                    required: true
                },
                'last_name': {
                    required: true
                },
                'phone': {
                    required: true
                }
            }
        });
        
        $('.rdv_num.select2').select2({
            dropdownParent: $('.rdv_num.select2').parent(),
            placeholder: $('.rdv_num.select2').attr('placeholder'),
            ajax: {
                type: "post",
                dataType: "json",
                url:"<?= SITE_URL ?>/handlers",
                delay: 250,
                data: function (params) {
                    var query = { method: 'handleRdv_nbr' }
                    
                    if($('.picker').val() != "")
                        query.date = $('.picker').val();
                        
                    if($('#doctor_id').val() != null)
                        query.doctor = $('#doctor_id').val();
                    
                    return query;
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
        }).change(function () {
            $('.rdv_num.select2').valid();
        });
        
        // START: MODIFIED - Patient auto-fill logic
        function togglePatientFields(readonly) {
            $('#first_name').prop('readonly', readonly);
            $('#last_name').prop('readonly', readonly);
            $('#phone').prop('readonly', readonly);
        }

        $(document).on('select2:select', '#patient_id', function(e){
            e.preventDefault();
            e.stopPropagation();

            let self = $(this);
            let patientId = self.val();
            
            if (patientId) {
                $.ajax({
                    type: "POST",
                    url: "<?= SITE_URL ?>/handlers",
                    data: { id: patientId, method: "getPatients" },
                    dataType: "json",
                    success: function(data) {
                        if (data[0] && data[0].hasOwnProperty('id')) {
                            $('#first_name').val(data[0].first_name);
                            $('#last_name').val(data[0].last_name);
                            $('#phone').val(data[0].phone);
                            togglePatientFields(true); // Make fields readonly
                        }
                    }
                });
            }
        });

        $(document).on('select2:unselect', '#patient_id', function(e) {
            $('#first_name').val('');
            $('#last_name').val('');
            $('#phone').val('');
            togglePatientFields(false); // Make fields editable
        });
        // END: MODIFIED

        $(document).on('submit', '.rdvForm', function(e){
            e.preventDefault();
            e.stopPropagation();

            let self = $(this),
            data = {
                patient:    $('#patient_id').val(),
                doctor:     $('#doctor_id').val(),
                rdv_num:    $('#rdv_num').val(),
                date:       $('#date').val(),
                first_name: $('#first_name').val(),
                last_name:  $('#last_name').val(),
                phone:      $('#phone').val(),
                method:     "postRdv" 
            };
            
            $.ajax({
                type: "POST",
                url: "<?= SITE_URL ?>/handlers",
                data: data,
                dataType: "json",
                beforeSend: function(){
                    let svg = '<svg class="seloader ps-25" height="14" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="currentColor"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
                        self.find('button[type=submit]').attr("disabled","disabled").append(svg);
                },
                success: function(data) {
                    if (data.state != "false") {
                        Swal.fire({
                            title: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                history.back(-1);
                            }
                        });
                    }else{
                        Swal.fire({
                            title: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                    }
                },
                complete: function(){
                    self.find('button[type=submit]').removeAttr("disabled");
                    $('.seloader').remove();
                }
            });
        });

    });
</script>