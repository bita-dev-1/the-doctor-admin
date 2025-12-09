<?php
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';

$table = 'users';
$btn_text = $GLOBALS['language']['add'];
$result = false;
$where = "";
$breadcrumb = $GLOBALS['language']['add'];

// بيانات الجلسة الحالية
$session_role = $_SESSION['user']['role'];
$session_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;

// التحقق الصارم من السوبر أدمن (يجب أن يكون admin والعيادة null أو 0)
$is_session_super_admin = ($session_role === 'admin' && (is_null($session_cabinet_id) || $session_cabinet_id === '' || $session_cabinet_id == 0 || strtolower((string) $session_cabinet_id) === 'null'));

$title = $GLOBALS['language']['user'];

// هل نحن في صفحة البروفايل؟
$is_profile_page = (stripos(request_path(), 'profile') !== false);
if ($is_profile_page) {
    $id = $_SESSION['user']['id'];
    $title = $GLOBALS['language']['profile'];
}

$is_edit_mode = (isset($id) && !empty($id));

// --- إعدادات العرض الافتراضية ---
$show_medical_scheduler = true; // التذاكر وساعات العمل
$show_location = true;          // الولاية والبلدية
$show_social = true;            // فيسبوك وانستغرام
$show_specialty = true;         // التخصص والدرجة
$show_images = true;            // الصور الجانبية
$show_cabinet_select = true;    // اختيار العيادة

if ($is_edit_mode) {
    $btn_text = $GLOBALS['language']['save'];
    $breadcrumb = $GLOBALS['language']['edit'];
    $where = array("column" => "id", "val" => $id);

    $query = "SELECT u.*, c.id as communeId, w.id as willayaId FROM users u LEFT JOIN communes c ON c.id = u.commune_id LEFT JOIN willaya w ON w.id = c.id_willaya WHERE u.id = $id";

    // حماية: الأدمن العادي لا يرى إلا مستخدمي عيادته (إلا إذا كان يعدل نفسه)
    if ($session_role === 'admin' && !$is_session_super_admin && !$is_profile_page) {
        $query .= " AND u.cabinet_id = " . intval($session_cabinet_id);
    }

    $result = $GLOBALS['db']->select($query)[0] ?? false;

    if (!$result) {
        if (!$is_profile_page) {
            header('location:' . SITE_URL . '/users');
            exit();
        }
    }

    $ticket_days = json_decode($result['tickets_day'] ?? '[]', true);
    $work_hours = json_decode($result['travel_hours'] ?? '[]', true);

    // --- منطق إخفاء الحقول بناءً على دور المستخدم الذي يتم تعديله ---
    $target_role = $result['role'];
    $target_cabinet = $result['cabinet_id'];
    $is_target_super_admin = ($target_role === 'admin' && empty($target_cabinet));

    if ($is_target_super_admin) {
        $show_medical_scheduler = false;
        $show_location = false;
        $show_social = false;
        $show_specialty = false;
        $show_images = false;
        $show_cabinet_select = false;
    } elseif ($target_role === 'admin' || $target_role === 'nurse') {
        $show_medical_scheduler = false;
        $show_specialty = false;
        $show_social = false;
        $show_images = true;
        $show_location = true;
        $show_cabinet_select = true;
    }
}

// --- إعداد خيارات الأدوار (للقائمة المنسدلة) ---
$roles_options = [
    ["option_text" => $GLOBALS['language']['doctor'], "value" => "doctor"],
    ["option_text" => $GLOBALS['language']['nurse'], "value" => "nurse"]
];

// التعديل: فقط السوبر أدمن يرى خيارات الأدمن (سواء أدمن عيادة أو سوبر أدمن)
if ($is_session_super_admin) {
    array_unshift($roles_options, ["option_text" => "Admin Cabinet", "value" => "admin"]);
    array_unshift($roles_options, ["option_text" => "★ Super Admin (Plateforme)", "value" => "super_admin"]);
}
?>

<style>
    .tickets label,
    .work_hours label {
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
                        <h2 class="content-header-title float-start mb-0"><?= $breadcrumb . ' ' . $title; ?></h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                <li class="breadcrumb-item active"><a><?= $breadcrumb . ' ' . $title; ?></a></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-12">
            <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                data-update="<?= customEncryption(json_encode($where)); ?>">
                <?php set_csrf() ?>
                <div class="row">

                    <?php if (!$is_edit_mode): ?>
                        <!-- =================================================
                             MODE CRÉATION (ADD NEW)
                             ================================================== -->
                        <div class="col-md-12 col-12">
                            <div class="card">
                                <div class="card-header border-bottom py-1">
                                    <h4 class="card-title">Informations de base</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Role Selection -->
                                        <div class="col-lg-12 col-12 mb-1">
                                            <?php
                                            draw_select([
                                                "label" => $GLOBALS['language']['role'],
                                                "name_id" => "role_selector",
                                                "placeholder" => "Select Role",
                                                "clientSide" => $roles_options
                                            ]);
                                            draw_input(["type" => "hidden", "name_id" => "{$table}__role", "value" => ""]);
                                            ?>
                                        </div>

                                        <!-- Cabinet Selection -->
                                        <div class="col-lg-12 col-12 mb-1 field-cabinet">
                                            <?php
                                            if ($is_session_super_admin) {
                                                draw_select(["label" => "Cabinet", "name_id" => "{$table}__cabinet_id", "placeholder" => "Select Cabinet", "serverSide" => ["table" => "cabinets", "value" => "id", "text" => ["name"], "where" => "deleted = 0"]]);
                                            } else {
                                                draw_input(["type" => "hidden", "name_id" => "{$table}__cabinet_id", "value" => $_SESSION['user']['cabinet_id']]);
                                            }
                                            ?>
                                        </div>

                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                            <?php draw_input(["label" => $GLOBALS['language']['firstname'], "type" => "text", "name_id" => "{$table}__first_name", "placeholder" => $GLOBALS['language']['firstname'], "value" => ""]); ?>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                            <?php draw_input(["label" => $GLOBALS['language']['lastname'], "type" => "text", "name_id" => "{$table}__last_name", "placeholder" => $GLOBALS['language']['lastname'], "value" => ""]); ?>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                            <?php draw_input(["label" => $GLOBALS['language']['email'], "type" => "email", "name_id" => "{$table}__email", "placeholder" => $GLOBALS['language']['email'], "value" => ""]); ?>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                            <?php draw_input(["label" => $GLOBALS['language']['phone'], "type" => "text", "name_id" => "{$table}__phone", "placeholder" => $GLOBALS['language']['phone'], "value" => ""]); ?>
                                        </div>

                                        <div class="col-12 mb-1">
                                            <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary mt-2 w-auto ms-1 me-1"]); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- =================================================
                             MODE ÉDITION / PROFILE (EDIT MODE)
                             ================================================== -->

                        <?php if ($show_images): ?>
                            <div class="col-md-3 col-12 doctor-specific-fields">
                                <div class="card mb-2">
                                    <div class="card-header border-bottom py-1">
                                        <h4 class="card-title">Photo de profile</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="avatar-square">
                                            <?php draw_fileUpload(["type" => "avatar", "name_id" => "{$table}__image1", "accept" => ".png, .jpg, .jpeg, .jfif", "value" => $result['image1'] ?? null]); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($target_role === 'doctor'): ?>
                                    <div class="card mb-2">
                                        <div class="card-header border-bottom py-1">
                                            <h4 class="card-title">Image 2</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="avatar-square">
                                                <?php draw_fileUpload(["type" => "avatar", "name_id" => "{$table}__image2", "accept" => ".png, .jpg, .jpeg, .jfif", "value" => $result['image2'] ?? null]); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-2">
                                        <div class="card-header border-bottom py-1">
                                            <h4 class="card-title">Image 3</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="avatar-square">
                                                <?php draw_fileUpload(["type" => "avatar", "name_id" => "{$table}__image3", "accept" => ".png, .jpg, .jpeg, .jfif", "value" => $result['image3'] ?? null]); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="<?= $show_images ? 'col-md-9' : 'col-md-12' ?> col-12">
                            <div class="row">

                                <?php if ($show_medical_scheduler): ?>
                                    <div class="col-lg-5 col-12">
                                        <div class="card">
                                            <div class="card-header border-bottom py-1">
                                                <h4 class="card-title"><?= $GLOBALS['language']['Tickets'] ?></h4>
                                            </div>
                                            <div class="card-body tickets">
                                                <?php
                                                $days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
                                                $db_day_keys = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
                                                foreach ($days as $index => $day_lang_key) {
                                                    $db_day_key = $db_day_keys[$index];
                                                    echo '<div class="col-12 d-flex align-items-center mb-1">';
                                                    draw_input(["label" => $GLOBALS['language'][$day_lang_key], "type" => "text", "name_id" => $db_day_key, "class" => "excluded", "value" => $ticket_days[$db_day_key] ?? 0]);
                                                    echo '</div>';
                                                }
                                                draw_input(["type" => "hidden", "name_id" => "{$table}__tickets_day", "value" => htmlspecialchars($result['tickets_day'] ?? '[]')]);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header border-bottom py-1">
                                                <h4 class="card-title">Heures de travail</h4>
                                            </div>
                                            <div class="card-body work_hours">
                                                <?php foreach ($days as $index => $day_lang_key) {
                                                    $db_day_key = $db_day_keys[$index]; ?>
                                                    <div class="col-12 d-flex align-items-center mb-1">
                                                        <?php draw_input(["label" => $GLOBALS['language'][$day_lang_key], "type" => "text", "name_id" => "{$db_day_key}__from", "placeholder" => "À partir", "class" => "excluded me-50", "value" => $work_hours[$db_day_key]["from"] ?? ""]);
                                                        draw_input(["label" => "", "type" => "text", "name_id" => "{$db_day_key}__to", "placeholder" => "De", "class" => "excluded", "value" => $work_hours[$db_day_key]["to"] ?? ""]); ?>
                                                    </div>
                                                <?php } ?>
                                                <?php draw_input(["type" => "hidden", "name_id" => "{$table}__travel_hours", "value" => htmlspecialchars($result['travel_hours'] ?? '[]')]); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="<?= $show_medical_scheduler ? 'col-lg-7' : 'col-lg-12' ?> col-12"
                                    id="main-info-col">
                                    <div class="card">
                                        <div class="card-header border-bottom py-1">
                                            <h4 class="card-title"><?= $GLOBALS['language']['Details_of'] . ' ' . $title; ?>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">

                                                <?php if ($show_specialty): ?>
                                                    <div class="col-lg-2 col-md-2 col-3 mb-1">
                                                        <?php draw_input(["label" => "Degré", "type" => "text", "name_id" => "{$table}__degree", "placeholder" => "Degré", "value" => $result['degree'] ?? '']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div
                                                    class="<?= $show_specialty ? 'col-lg-5 col-md-5 col-9' : 'col-lg-6 col-md-6 col-12' ?> mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['firstname'], "type" => "text", "name_id" => "{$table}__first_name", "placeholder" => $GLOBALS['language']['firstname'], "value" => $result['first_name'] ?? '']); ?>
                                                </div>
                                                <div
                                                    class="<?= $show_specialty ? 'col-lg-5 col-md-5 col-12' : 'col-lg-6 col-md-6 col-12' ?> mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['lastname'], "type" => "text", "name_id" => "{$table}__last_name", "placeholder" => $GLOBALS['language']['lastname'], "value" => $result['last_name'] ?? '']); ?>
                                                </div>

                                                <?php if ($session_role === 'admin' && !$is_profile_page): ?>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php
                                                        $is_editing_super = ($result['role'] === 'admin' && empty($result['cabinet_id']));
                                                        $selected_role_val = $result['role'];
                                                        if ($is_editing_super)
                                                            $selected_role_val = 'super_admin';

                                                        draw_select(["label" => $GLOBALS['language']['role'], "name_id" => "role_selector", "placeholder" => "Select Role", "clientSideSelected" => $selected_role_val, "clientSide" => $roles_options]);
                                                        draw_input(["type" => "hidden", "name_id" => "{$table}__role", "value" => $result['role']]);
                                                        ?>
                                                    </div>

                                                    <div class="col-lg-6 col-12 mb-1 field-cabinet">
                                                        <?php draw_select(["label" => "Cabinet", "name_id" => "{$table}__cabinet_id", "placeholder" => "Select Cabinet", "serverSide" => ["table" => "cabinets", "value" => "id", "text" => ["name"], "selected" => $result['cabinet_id'] ?? null, "where" => "deleted=0"]]); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($show_specialty): ?>
                                                    <div class="col-lg-12 col-12 mb-1">
                                                        <?php draw_select(["label" => $GLOBALS['language']['speciality'], "name_id" => "{$table}__specialty_id", "placeholder" => $GLOBALS['language']['speciality'], "serverSide" => ["table" => "specialty", "value" => "id", "text" => ["namefr"], "selected" => $result ? ($result['specialty_id'] ?? null) : null, "where" => ""]]); ?>
                                                    </div>
                                                    <div class="col-lg-12 col-12 my-2">
                                                        <?php draw_switch(["label" => "Ouverture", "name_id" => "{$table}__is_opened", "checked" => $result['is_opened'] ?? 0]); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['email'], "type" => "email", "name_id" => "{$table}__email", "placeholder" => $GLOBALS['language']['email'], "value" => $result['email'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['phone'], "type" => "text", "name_id" => "{$table}__phone", "placeholder" => $GLOBALS['language']['phone'], "value" => $result['phone'] ?? '']); ?>
                                                </div>

                                                <?php if ($show_location): ?>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php draw_select(["label" => $GLOBALS['language']['willaya'], "name_id" => "regien", "placeholder" => $GLOBALS['language']['willaya'], "class" => "excluded", "serverSide" => ["table" => "willaya", "value" => "id", "text" => ["willaya"], "selected" => $result ? ($result['willayaId'] ?? null) : null, "where" => ""]]); ?>
                                                    </div>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php draw_select(["label" => $GLOBALS['language']['commune'], "name_id" => "{$table}__commune_id", "placeholder" => $GLOBALS['language']['commune'], "his_parent" => "#regien", "serverSide" => ["table" => "communes", "value" => "id", "value_parent" => "id_willaya", "text" => ["name"], "selected" => $result ? ($result['communeId'] ?? null) : null, "where" => ""]]); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($show_social): ?>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php draw_input(["label" => "Facebook", "type" => "text", "name_id" => "{$table}__facebook", "placeholder" => "Facebook", "value" => $result['facebook'] ?? '']); ?>
                                                    </div>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php draw_input(["label" => "Instagram", "type" => "text", "name_id" => "{$table}__instagram", "placeholder" => "Instagram", "value" => $result['instagram'] ?? '']); ?>
                                                    </div>
                                                    <div class="col-lg-12 col-12 mb-1">
                                                        <?php draw_text_area(["label" => $GLOBALS['language']['description'], "rows" => "6", "name_id" => "{$table}__description", "placeholder" => $GLOBALS['language']['description'], "value" => $result['description'] ?? '']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="col-12 mb-1">
                                                    <?php
                                                    draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary mt-2 w-auto ms-1 me-1"]);

                                                    if (stripos(request_path(), 'profile') !== false) {
                                                        echo '<a href="' . SITE_URL . '/profile/password" class="btn btn-outline-secondary mt-2 w-auto ms-1 me-1">Changer mot de passe</a>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </form>
        </div>
    </div>
</div>
<?php include_once 'foot.php'; ?>
<script>
    $(document).ready(function () {

        function handleRoleChange() {
            var selectedRole = $('#role_selector').val();
            var realRoleInput = $('input[name="<?= $table; ?>__role"]');

            // Default: Show everything
            $('.doctor-specific-fields').show();
            $('.field-cabinet').show();

            // Adjust layout for full width if sidebars are hidden
            if (selectedRole === 'super_admin') {
                $('.col-md-3.doctor-specific-fields').hide();
                $('#main-info-col').removeClass('col-lg-7').addClass('col-lg-12');
            } else {
                $('.col-md-3.doctor-specific-fields').show();
                $('#main-info-col').removeClass('col-lg-12').addClass('col-lg-7');
            }

            if (selectedRole === 'super_admin') {
                realRoleInput.val('admin');
                $('.field-cabinet').hide();
                $('#<?= $table; ?>__cabinet_id').val(null).trigger('change');
                $('.doctor-specific-fields').hide();

            } else if (selectedRole === 'admin') {
                realRoleInput.val('admin');
                $('.field-cabinet').show();
                $('.doctor-specific-fields').hide();

            } else {
                realRoleInput.val(selectedRole);
                $('.field-cabinet').show();
                $('.doctor-specific-fields').show();
            }
        }

        if ($('#role_selector').length > 0) {
            handleRoleChange();
            $('#role_selector').on('change', handleRoleChange);
        }

        $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__first_name': { required: true },
                '<?= $table; ?>__last_name': { required: true },
                '<?= $table; ?>__email': { required: true, email: true },
                '<?= $table; ?>__phone': {
                    required: function (element) {
                        var role = $('#role_selector').val();
                        return role !== 'super_admin';
                    }
                },
                '<?= $table; ?>__cabinet_id': {
                    required: function (element) {
                        var role = $('#role_selector').val();
                        return role !== 'super_admin' && $('.field-cabinet').is(':visible');
                    }
                },
                '<?= $table; ?>__role': { required: true }
            }
        });

        <?php if ($is_edit_mode): ?>
            $(document).on('input', '.tickets input', function (e) {
                e.preventDefault();
                let tickets = {};
                $('.tickets input').each(function () {
                    tickets[$(this).attr('id')] = $(this).val();
                });
                $("#<?= $table; ?>__tickets_day").val(JSON.stringify(tickets));
            });

            $(document).on('input', '.work_hours input', function (e) {
                e.preventDefault();
                let workHours = {};
                $('.work_hours .d-flex').each(function () {
                    let day = $(this).find('input:first').attr('id').split('__')[0];
                    let from = $(this).find('input[id$="__from"]').val();
                    let to = $(this).find('input[id$="__to"]').val();
                    workHours[day] = { "from": from, "to": to };
                });
                $("#<?= $table; ?>__travel_hours").val(JSON.stringify(workHours));
            });
        <?php endif; ?>
    });
</script>