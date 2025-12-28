<?php
// التحقق من الصلاحيات
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';

$table = 'rdv';
$btn_text = $GLOBALS['language']['add'];
$result = false;
$where = "";
$breadcrumb = $GLOBALS['language']['add'];

if (isset($id) && !empty($id)) {
    $btn_text = $GLOBALS['language']['save'];
    $breadcrumb = $GLOBALS['language']['edit'];
    $where = array("column" => "id", "val" => $id);
    $result = dataById($where, $table)[0] ?? false;
}
?>

<div class="app-content content ">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-start mb-0">
                            <?= $breadcrumb . ' ' . $GLOBALS['language']['rdv']; ?>
                        </h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                <li class="breadcrumb-item active">
                                    <a><?= $breadcrumb . ' ' . $GLOBALS['language']['rdv']; ?></a>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-12">

            <form class="rdvForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                data-update="<?= customEncryption(json_encode($where)); ?>">
                <?php set_csrf() ?>
                <div class="row">
                    <div class="col-md-12 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($_SESSION['user']['role'] == 'admin') { ?>
                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                            <?php
                                            $doctor_where_clause = "role = 'doctor' AND deleted = 0";
                                            if (!empty($_SESSION['user']['cabinet_id'])) {
                                                $doctor_where_clause .= " AND cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
                                            }
                                            $input = array(
                                                "label" => $GLOBALS['language']['doctor'],
                                                "name_id" => "doctor_id",
                                                "placeholder" => "Sélectionner Médecin",
                                                "class" => "",
                                                "his_parent" => "",
                                                "serverSide" => array(
                                                    "table" => "users",
                                                    "value" => "id",
                                                    "value_parent" => "",
                                                    "text" => array("first_name", "last_name"),
                                                    "selected" => $result['doctor_id'] ?? null,
                                                    "where" => $doctor_where_clause
                                                )
                                            );
                                            draw_select($input);
                                            ?>
                                        </div>
                                        <?php
                                    } else {
                                        $input = array(
                                            "label" => "",
                                            "type" => "hidden",
                                            "name_id" => "doctor_id",
                                            "placeholder" => "",
                                            "class" => "",
                                            "value" => $_SESSION['user']['id']
                                        );
                                        draw_input($input);
                                    }
                                    ?>
                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['patient'],
                                            "name_id" => "patient_id",
                                            "placeholder" => 'Rechercher par nom, téléphone, ID...',
                                            "class" => "",
                                            "his_parent" => "",
                                            "serverSide" => array(
                                                "table" => "patient",
                                                "value" => "id",
                                                "value_parent" => "",
                                                "text" => array("first_name", "last_name", "phone", "id"),
                                                "selected" => $result['patient_id'] ?? null,
                                                "where" => "deleted = 0"
                                            )
                                        );
                                        draw_select($input);
                                        ?>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['firstname'],
                                            "type" => "text",
                                            "name_id" => "first_name",
                                            "placeholder" => $GLOBALS['language']['firstname'],
                                            "class" => "",
                                            "value" => $result['first_name'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['lastname'],
                                            "type" => "text",
                                            "name_id" => "last_name",
                                            "placeholder" => $GLOBALS['language']['lastname'],
                                            "class" => "",
                                            "value" => $result['last_name'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['phone'],
                                            "type" => "text",
                                            "name_id" => "phone",
                                            "placeholder" => $GLOBALS['language']['phone'],
                                            "class" => "",
                                            "value" => $result['phone'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>

                                    <!-- START: Motif Field -->
                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $motif_where = "deleted = 0";
                                        if ($_SESSION['user']['role'] === 'doctor') {
                                            $motif_where .= " AND doctor_id = " . $_SESSION['user']['id'];
                                        }

                                        draw_select([
                                            "label" => "Motif de consultation",
                                            "name_id" => "motif_id",
                                            "placeholder" => "Sélectionner un motif",
                                            "class" => "motif-select",
                                            "serverSide" => [
                                                "table" => "doctor_motifs",
                                                "value" => "id",
                                                "text" => ["title"],
                                                "selected" => $result['motif_id'] ?? null,
                                                "where" => $motif_where
                                            ]
                                        ]);
                                        ?>
                                    </div>
                                    <!-- END: Motif Field -->

                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['date'],
                                            "type" => "text",
                                            "name_id" => "date",
                                            "placeholder" => "YYYY-MM-DD",
                                            "class" => "picker",
                                            "value" => $result['date'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['rdv_num'],
                                            "name_id" => "rdv_num",
                                            "placeholder" => $GLOBALS['language']['rdv_num'],
                                            "class" => "rdv_num",
                                            "attr" => "data-search = '-1' ",
                                            "his_parent" => "",
                                            "clientSideSelected" => "",
                                            "clientSide" => array()
                                        );

                                        draw_select($input);
                                        ?>
                                    </div>
                                </div>
                                <?php
                                if (isset($id) && !empty($id)) {
                                    $input = array(
                                        "label" => "",
                                        "type" => "hidden",
                                        "name_id" => "rdv_id",
                                        "placeholder" => "",
                                        "class" => "",
                                        "value" => $id
                                    );
                                    draw_input($input);
                                }

                                $button = array(
                                    "text" => $btn_text,
                                    "type" => "submit",
                                    "name_id" => "submit",
                                    "class" => "btn-primary mt-2"
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

<?php include_once 'foot.php'; ?>

<script>
    $(document).ready(function () {
        console.log("🚀 [DEBUG] Page Loaded: form_rdv.php");

        // 1. تهيئة التحقق من النموذج
        $('.rdvForm').validate({
            rules: {
                'doctor_id': { required: true },
                'date': { required: true },
                'rdv_num': { required: true },
                'first_name': { required: true },
                'last_name': { required: true },
                'phone': { required: true }
            }
        });

        // 2. تعريف متغير لحقل التذاكر للتحكم به
        var $rdvSelect = $('.rdv_num.select2');

        // 3. تهيئة Select2
        console.log("🔧 [DEBUG] Initializing Select2 for #rdv_num");
        $rdvSelect.select2({
            dropdownParent: $rdvSelect.parent(),
            placeholder: "Sélectionner Ticket",
            language: {
                noResults: function () { return "Aucun ticket disponible"; },
                searching: function () { return "Recherche..."; }
            },
            ajax: {
                type: "post",
                dataType: "json",
                url: "<?= SITE_URL ?>/handlers",
                delay: 250,
                data: function (params) {
                    var dateVal = $('#date').val();
                    var doctorVal = $('#doctor_id').val();

                    console.log("📡 [DEBUG] Select2 AJAX Request Triggered");
                    console.log("   -> Date Value:", dateVal);
                    console.log("   -> Doctor ID:", doctorVal);

                    return {
                        method: 'handleRdv_nbr',
                        date: dateVal,
                        doctor: doctorVal
                    };
                },
                processResults: function (data) {
                    console.log("✅ [DEBUG] Select2 AJAX Response Received:", data);
                    return {
                        results: data
                    };
                },
                cache: false
            }
        });

        // 4. تعطيل القائمة فور تحميل الصفحة
        console.log("🔒 [DEBUG] Disabling #rdv_num initially");
        $rdvSelect.prop('disabled', true);

        // --- دالة منطق تحديث حالة القائمة ---
        function updateRdvFieldState() {
            var selectedDate = $('#date').val();
            console.log("📅 [DEBUG] Updating RDV Field State. Date:", selectedDate);

            if (selectedDate && selectedDate !== "") {
                console.log("🔓 [DEBUG] Enabling #rdv_num");
                $rdvSelect.prop('disabled', false);
            } else {
                console.log("🔒 [DEBUG] Disabling #rdv_num (Date is empty)");
                $rdvSelect.prop('disabled', true);
            }
            // تصفير القيمة المختارة
            $rdvSelect.val(null).trigger('change');
        }

        // 5. ربط حدث التغيير بمكتبة Flatpickr
        var dateElement = document.querySelector("#date");
        if (dateElement && dateElement._flatpickr) {
            dateElement._flatpickr.config.onChange.push(function (selectedDates, dateStr, instance) {
                console.log("📅 [DEBUG] Flatpickr onChange detected: ", dateStr);
                // تحديث القيمة يدوياً (للتأكد)
                $('#date').val(dateStr);
                // استدعاء دالة التحديث مباشرة
                updateRdvFieldState();
            });
        }

        // 6. ربط حدث التغيير المباشر (للتأكد)
        $('#date').on('change', function () {
            console.log("📅 [DEBUG] Direct jQuery Change Event");
            updateRdvFieldState();
        });

        // عند تغيير الطبيب (للأدمن)
        $(document).on('change', '#doctor_id', function () {
            console.log("👨‍⚕️ [DEBUG] Doctor Changed. Resetting #rdv_num");
            $rdvSelect.val(null).trigger('change');
            // يمكن إضافة منطق هنا لتحديث قائمة الموتيف إذا لزم الأمر
        });

        // --- منطق الملء التلقائي للمريض ---
        function togglePatientFields(readonly) {
            $('#first_name').prop('readonly', readonly);
            $('#last_name').prop('readonly', readonly);
            $('#phone').prop('readonly', readonly);
        }

        $(document).on('select2:select', '#patient_id', function (e) {
            e.preventDefault();
            let self = $(this);
            let patientId = self.val();
            console.log("👤 [DEBUG] Patient Selected ID:", patientId);

            if (patientId) {
                $.ajax({
                    type: "POST",
                    url: "<?= SITE_URL ?>/handlers",
                    data: { id: patientId, method: "getPatients" },
                    dataType: "json",
                    success: function (data) {
                        console.log("📥 [DEBUG] Patient Data Received:", data);
                        if (data[0] && data[0].hasOwnProperty('id')) {
                            $('#first_name').val(data[0].first_name);
                            $('#last_name').val(data[0].last_name);
                            $('#phone').val(data[0].phone);
                            togglePatientFields(true);
                        }
                    }
                });
            }
        });

        $(document).on('select2:unselect', '#patient_id', function (e) {
            console.log("👤 [DEBUG] Patient Unselected");
            $('#first_name').val('');
            $('#last_name').val('');
            $('#phone').val('');
            togglePatientFields(false);
        });

        // --- إرسال النموذج ---
        $(document).on('submit', '.rdvForm', function (e) {
            e.preventDefault();
            e.stopPropagation();

            let self = $(this);
            if (!self.valid()) {
                console.log("❌ [DEBUG] Form Validation Failed");
                return;
            }

            let data = {
                patient: $('#patient_id').val(),
                doctor: $('#doctor_id').val(),
                rdv_num: $('#rdv_num').val(),
                date: $('#date').val(),
                motif_id: $('#motif_id').val(), // إضافة الموتيف
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                phone: $('#phone').val(),
                method: "postRdv"
            };

            console.log("📤 [DEBUG] Submitting Form Data:", data);

            $.ajax({
                type: "POST",
                url: "<?= SITE_URL ?>/handlers",
                data: data,
                dataType: "json",
                beforeSend: function () {
                    let svg = '<svg class="seloader ps-25" height="14" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="currentColor"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
                    self.find('button[type=submit]').attr("disabled", "disabled").append(svg);
                },
                success: function (data) {
                    console.log("✅ [DEBUG] Form Submission Response:", data);
                    if (data.state != "false") {
                        Swal.fire({
                            title: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-primary' },
                            buttonsStyling: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                history.back(-1);
                            }
                        });
                    } else {
                        Swal.fire({
                            title: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-primary' },
                            buttonsStyling: false
                        });
                    }
                },
                complete: function () {
                    self.find('button[type=submit]').removeAttr("disabled");
                    $('.seloader').remove();
                }
            });
        });

    });
</script>

<?php include_once 'foot.php'; ?>