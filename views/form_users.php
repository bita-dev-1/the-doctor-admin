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
$user_role = $_SESSION['user']['role'];
$user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
$is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

$title = $GLOBALS['language']['user'];

// Determine if we are on the profile page or an edit page
if (stripos(request_path(), 'profile') !== false) {
    $id = $_SESSION['user']['id']; // Force ID to current user for profile page
    $title = $GLOBALS['language']['profile'];
}

$is_edit_mode = (isset($id) && !empty($id));

if ($is_edit_mode) {
    $btn_text = $GLOBALS['language']['save'];
    $breadcrumb = $GLOBALS['language']['edit'];
    $where = array("column" => "id", "val" => $id);

    $query = "SELECT u.*, c.id as communeId, w.id as willayaId FROM users u LEFT JOIN communes c ON c.id = u.commune_id LEFT JOIN willaya w ON w.id = c.id_willaya WHERE u.id = $id";

    if ($user_role === 'admin' && !$is_super_admin && stripos(request_path(), 'profile') === false) {
        $query .= " AND u.cabinet_id = " . intval($user_cabinet_id);
    }

    $result = $GLOBALS['db']->select($query)[0] ?? false;

    if (!$result) {
        if (stripos(request_path(), 'profile') === false) {
            header('location:' . SITE_URL . '/users');
            exit();
        }
    }

    $ticket_days = json_decode($result['tickets_day'] ?? '[]', true);
    $work_hours = json_decode($result['travel_hours'] ?? '[]', true);
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
                                SIMPLIFIED FORM FOR CREATION MODE
                        ================================================== -->
                        <div class="col-md-12 col-12">
                            <div class="card">
                                <div class="card-header border-bottom py-1">
                                    <h4 class="card-title">Informations de base</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
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
                                        <?php if ($is_super_admin): ?>
                                            <div class="col-lg-12 col-12 mb-1">
                                                <?php draw_select(["label" => "Cabinet", "name_id" => "{$table}__cabinet_id", "placeholder" => "Select Cabinet", "serverSide" => ["table" => "cabinets", "value" => "id", "text" => ["name"], "where" => "deleted = 0"]]); ?>
                                            </div>
                                            <?php draw_input(["type" => "hidden", "name_id" => "{$table}__role", "value" => "admin"]); ?>
                                        <?php else: ?>
                                            <div class="col-lg-12 col-12 mb-1">
                                                <?php draw_select(["label" => $GLOBALS['language']['role'], "name_id" => "{$table}__role", "placeholder" => "Select Role", "clientSide" => [["option_text" => $GLOBALS['language']['doctor'], "value" => "doctor"], ["option_text" => $GLOBALS['language']['nurse'], "value" => "nurse"]]]); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="col-12 mb-1">
                                            <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary mt-2 w-auto ms-1 me-1"]); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- =================================================
                                FULL FORM FOR EDITING / PROFILE MODE
                        ================================================== -->
                        <div class="col-md-3 col-12">
                            <div class="card mb-2">
                                <div class="card-header border-bottom py-1">
                                    <h4 class="card-title">Image 1</h4>
                                </div>
                                <div class="card-body">
                                    <div class="avatar-square">
                                        <?php
                                        draw_fileUpload([
                                            "type" => "avatar",
                                            "name_id" => "{$table}__image1",
                                            "accept" => ".png, .jpg, .jpeg, .jfif",
                                            "value" => $result['image1'] ?? null
                                        ]);
                                        ?>
                                    </div>
                                </div>
                            </div>
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
                        </div>

                        <div class="col-md-9 col-12">
                            <div class="row">
                                <div class="col-lg-5 col-12">
                                    <?php if ($user_role === 'admin' || ($result && $result['role'] === 'doctor')) { ?>
                                        <div class="card">
                                            <div class="card-header border-bottom py-1">
                                                <h4 class="card-title"><?= $GLOBALS['language']['Tickets'] ?></h4>
                                            </div>
                                            <div class="card-body tickets">
                                                <?php $days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
                                                $db_day_keys = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
                                                foreach ($days as $index => $day_lang_key) {
                                                    $db_day_key = $db_day_keys[$index];
                                                    echo '<div class="col-12 d-flex align-items-center mb-1">';
                                                    draw_input(["label" => $GLOBALS['language'][$day_lang_key], "type" => "text", "name_id" => $db_day_key, "class" => "excluded", "value" => $ticket_days[$db_day_key] ?? 0]);
                                                    echo '</div>';
                                                }
                                                draw_input(["type" => "hidden", "name_id" => "{$table}__tickets_day", "value" => htmlspecialchars($result['tickets_day'] ?? '[]')]); ?>
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
                                                <?php } ?>        <?php draw_input(["type" => "hidden", "name_id" => "{$table}__travel_hours", "value" => htmlspecialchars($result['travel_hours'] ?? '[]')]); ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="col-lg-7 col-12">
                                    <div class="card">
                                        <div class="card-header border-bottom py-1">
                                            <h4 class="card-title"><?= $GLOBALS['language']['Details_of'] . ' ' . $title; ?>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-2 col-md-2 col-3 mb-1">
                                                    <?php draw_input(["label" => "Degré", "type" => "text", "name_id" => "{$table}__degree", "placeholder" => "Degré", "value" => $result['degree'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-5 col-md-5 col-9 mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['firstname'], "type" => "text", "name_id" => "{$table}__first_name", "placeholder" => $GLOBALS['language']['firstname'], "value" => $result['first_name'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-5 col-md-5 col-12 mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['lastname'], "type" => "text", "name_id" => "{$table}__last_name", "placeholder" => $GLOBALS['language']['lastname'], "value" => $result['last_name'] ?? '']); ?>
                                                </div>
                                                <?php if ($user_role === 'admin' && stripos(request_path(), 'profile') === false): ?>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php draw_select(["label" => "Cabinet", "name_id" => "{$table}__cabinet_id", "placeholder" => "Select Cabinet", "serverSide" => ["table" => "cabinets", "value" => "id", "text" => ["name"], "selected" => $result['cabinet_id'] ?? null, "where" => "deleted=0"]]); ?>
                                                    </div>
                                                    <div class="col-lg-6 col-12 mb-1">
                                                        <?php draw_select(["label" => $GLOBALS['language']['role'], "name_id" => "{$table}__role", "placeholder" => "Select Role", "clientSideSelected" => $result['role'] ?? 'doctor', "clientSide" => [["option_text" => $GLOBALS['language']['admin'], "value" => "admin"], ["option_text" => $GLOBALS['language']['doctor'], "value" => "doctor"], ["option_text" => $GLOBALS['language']['nurse'], "value" => "nurse"]]]); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="col-lg-12 col-12 mb-1">
                                                    <?php draw_select(["label" => $GLOBALS['language']['speciality'], "name_id" => "{$table}__specialty_id", "placeholder" => $GLOBALS['language']['speciality'], "serverSide" => ["table" => "specialty", "value" => "id", "text" => ["namefr"], "selected" => $result ? ($result['specialty_id'] ?? null) : null, "where" => ""]]); ?>
                                                </div>
                                                <div class="col-lg-12 col-12 my-2">
                                                    <?php draw_switch(["label" => "Ouverture", "name_id" => "{$table}__is_opened", "checked" => $result['is_opened'] ?? 0]); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['email'], "type" => "email", "name_id" => "{$table}__email", "placeholder" => $GLOBALS['language']['email'], "value" => $result['email'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_input(["label" => $GLOBALS['language']['phone'], "type" => "text", "name_id" => "{$table}__phone", "placeholder" => $GLOBALS['language']['phone'], "value" => $result['phone'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_select(["label" => $GLOBALS['language']['willaya'], "name_id" => "regien", "placeholder" => $GLOBALS['language']['willaya'], "class" => "excluded", "serverSide" => ["table" => "willaya", "value" => "id", "text" => ["willaya"], "selected" => $result ? ($result['willayaId'] ?? null) : null, "where" => ""]]); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_select(["label" => $GLOBALS['language']['commune'], "name_id" => "{$table}__commune_id", "placeholder" => $GLOBALS['language']['commune'], "his_parent" => "#regien", "serverSide" => ["table" => "communes", "value" => "id", "value_parent" => "id_willaya", "text" => ["name"], "selected" => $result ? ($result['communeId'] ?? null) : null, "where" => ""]]); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_input(["label" => "Facebook", "type" => "text", "name_id" => "{$table}__facebook", "placeholder" => "Facebook", "value" => $result['facebook'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-6 col-12 mb-1">
                                                    <?php draw_input(["label" => "Instagram", "type" => "text", "name_id" => "{$table}__instagram", "placeholder" => "Instagram", "value" => $result['instagram'] ?? '']); ?>
                                                </div>
                                                <div class="col-lg-12 col-12 mb-1">
                                                    <?php draw_text_area(["label" => $GLOBALS['language']['description'], "rows" => "6", "name_id" => "{$table}__description", "placeholder" => $GLOBALS['language']['description'], "value" => $result['description'] ?? '']); ?>
                                                </div>
                                                <div class="col-12 mb-1">
                                                    <?php
                                                    draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary mt-2 w-auto ms-1 me-1"]);

                                                    // --- START: ADDED BUTTON ---
                                                    if (stripos(request_path(), 'profile') !== false) {
                                                        echo '<a href="' . SITE_URL . '/profile/password" class="btn btn-outline-secondary mt-2 w-auto ms-1 me-1">Changer mot de passe</a>';
                                                    }
                                                    // --- END: ADDED BUTTON ---
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
        $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__first_name': { required: true },
                '<?= $table; ?>__last_name': { required: true },
                '<?= $table; ?>__email': { required: true, email: true },
                '<?= $table; ?>__phone': { required: true },
                '<?= $table; ?>__cabinet_id': { required: true },
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