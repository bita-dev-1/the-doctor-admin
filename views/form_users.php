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

// Session Data
$session_role = $_SESSION['user']['role'];
$session_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
$is_session_super_admin = ($session_role === 'admin' && (is_null($session_cabinet_id) || $session_cabinet_id === '' || $session_cabinet_id == 0));

$title = $GLOBALS['language']['user'];
$is_profile_page = (stripos(request_path(), 'profile') !== false);

if ($is_profile_page) {
    $id = $_SESSION['user']['id'];
    $title = $GLOBALS['language']['profile'];
}

$is_edit_mode = (isset($id) && !empty($id));

// Default Display Settings
$show_medical_scheduler = true;
$show_location = true;
$show_social = true;
$show_specialty = true;
$show_images = true;
$show_cabinet_select = true;
$show_motifs_section = false;

// Variables for Schedule
$ticket_days_raw = '{}';
$work_hours_raw = '{}';
$ticket_days_data = [];
$work_hours_data = [];

if ($is_edit_mode) {
    $btn_text = $GLOBALS['language']['save'];
    $breadcrumb = $GLOBALS['language']['edit'];
    $where = array("column" => "id", "val" => $id);

    $query = "SELECT u.*, c.id as communeId, w.id as willayaId FROM users u LEFT JOIN communes c ON c.id = u.commune_id LEFT JOIN willaya w ON w.id = c.id_willaya WHERE u.id = $id";

    if ($session_role === 'admin' && !$is_session_super_admin && !$is_profile_page) {
        $query .= " AND u.cabinet_id = " . intval($session_cabinet_id);
    }

    $result = $GLOBALS['db']->select($query)[0] ?? false;

    if (!$result && !$is_profile_page) {
        header('location:' . SITE_URL . '/users');
        exit();
    }

    // Prepare Raw Data for Hidden Inputs
    $ticket_days_raw = $result['tickets_day'] ?? '{}';
    $work_hours_raw = $result['travel_hours'] ?? '{}';
    
    $ticket_days_data = json_decode($ticket_days_raw, true) ?? [];
    $work_hours_data = json_decode($work_hours_raw, true) ?? [];

    $target_role = $result['role'];
    $target_cabinet = $result['cabinet_id'];
    $is_target_super_admin = ($target_role === 'admin' && empty($target_cabinet));

    // Visibility Logic
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
    } elseif ($target_role === 'doctor') {
        $show_motifs_section = true;
    }
}

$roles_options = [["option_text" => $GLOBALS['language']['doctor'], "value" => "doctor"], ["option_text" => $GLOBALS['language']['nurse'], "value" => "nurse"]];
if ($is_session_super_admin) {
    array_unshift($roles_options, ["option_text" => "Admin Cabinet", "value" => "admin"]);
    array_unshift($roles_options, ["option_text" => "★ Super Admin", "value" => "super_admin"]);
}
?>

<link rel="stylesheet" type="text/css" href="<?= SITE_URL ?>/assets/css/pages/form-users.css">

<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-start mb-0"><?= $breadcrumb . ' ' . $title; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-body">
            <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                data-update="<?= customEncryption(json_encode($where)); ?>">
                <?php set_csrf() ?>

                <div class="row">
                    <!-- LEFT COLUMN: Profile Card & Navigation -->
                    <div class="col-lg-3 col-md-4 col-12">

                        <!-- Profile Card -->
                        <div class="card card-profile shadow-sm">
                            <div class="card-header-img"></div>
                            <div class="card-body profile-body">
                                <div class="profile-img">
                                    <!-- Added codexFileUp class here for JS to work -->
                                    <div class="avatar-upload codexFileUp h-100 w-100 m-0">
                                        <div class="avatar-edit">
                                            <input type="file" id="image1" class="codexInputFile" name="users__image1"
                                                accept=".png, .jpg, .jpeg">
                                            <label for="image1"></label>
                                        </div>
                                        <div class="avatar-preview">
                                            <?php 
                                                $main_img = $result['image1'] ?? '';
                                                $main_src = (!empty($main_img)) ? (filter_var($main_img, FILTER_VALIDATE_URL) ? $main_img : SITE_URL . '/' . $main_img) : SITE_URL . '/assets/images/default_User.png';
                                            ?>
                                            <img src="<?= $main_src ?>" id="codexPreviewImage" class="w-100 h-100" style="object-fit: cover;">
                                        </div>
                                        <input type="hidden" class="codexFileData" data-name="users__image1"
                                            value="<?= $result['image1'] ?? '' ?>">
                                    </div>
                                </div>
                                <h4 class="mb-0 fw-bolder">
                                    <?= ($result['first_name'] ?? 'Nouveau') . ' ' . ($result['last_name'] ?? 'Utilisateur') ?>
                                </h4>
                                <span class="badge bg-light-primary mt-50"><?= ucfirst($result['role'] ?? 'Role') ?></span>

                                <div class="d-grid gap-1 mt-2">
                                    <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary"]); ?>
                                    <?php if ($is_edit_mode && stripos(request_path(), 'profile') !== false): ?>
                                        <a href="<?= SITE_URL ?>/profile/password" class="btn btn-outline-secondary">
                                            <i data-feather="lock" class="me-50"></i> Mot de passe
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Tabs -->
                        <div class="card shadow-sm">
                            <div class="card-body p-1">
                                <div class="nav flex-column nav-pills nav-pills-custom" id="v-pills-tab" role="tablist"
                                    aria-orientation="vertical">
                                    <button class="nav-link active" id="tab-general" data-bs-toggle="pill"
                                        data-bs-target="#content-general" type="button" role="tab">
                                        <i data-feather="user"></i> Général
                                    </button>

                                    <?php if ($show_specialty): ?>
                                        <button class="nav-link" id="tab-pro" data-bs-toggle="pill"
                                            data-bs-target="#content-pro" type="button" role="tab">
                                            <i data-feather="briefcase"></i> Professionnel
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($show_medical_scheduler): ?>
                                        <button class="nav-link" id="tab-planning" data-bs-toggle="pill"
                                            data-bs-target="#content-planning" type="button" role="tab">
                                            <i data-feather="calendar"></i> Planning
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($show_motifs_section): ?>
                                        <button class="nav-link" id="tab-motifs" data-bs-toggle="pill"
                                            data-bs-target="#content-motifs" type="button" role="tab">
                                            <i data-feather="list"></i> Motifs
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($show_images && $target_role === 'doctor'): ?>
                                        <button class="nav-link" id="tab-gallery" data-bs-toggle="pill"
                                            data-bs-target="#content-gallery" type="button" role="tab">
                                            <i data-feather="image"></i> Galerie
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Tab Content -->
                    <div class="col-lg-9 col-md-8 col-12">
                        <div class="tab-content" id="v-pills-tabContent">

                            <!-- 1. General Info Tab -->
                            <div class="tab-pane fade show active" id="content-general" role="tabpanel">
                                <div class="card">
                                    <div class="card-header border-bottom">
                                        <h4 class="card-title">Informations Personnelles</h4>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="row">
                                            <?php if (!$is_edit_mode || ($session_role === 'admin' && !$is_profile_page)): ?>
                                                <div class="col-md-6 mb-1">
                                                    <?php
                                                    $selected_role_val = $result['role'] ?? '';
                                                    if (isset($result['role']) && $result['role'] === 'admin' && empty($result['cabinet_id']))
                                                        $selected_role_val = 'super_admin';
                                                    draw_select(["label" => $GLOBALS['language']['role'], "name_id" => "role_selector", "placeholder" => "Select Role", "clientSideSelected" => $selected_role_val, "clientSide" => $roles_options]);
                                                    draw_input(["type" => "hidden", "name_id" => "{$table}__role", "value" => $result['role'] ?? '']);
                                                    ?>
                                                </div>
                                                <div class="col-md-6 mb-1 field-cabinet">
                                                    <?php
                                                    if ($is_session_super_admin) {
                                                        draw_select(["label" => "Cabinet", "name_id" => "{$table}__cabinet_id", "placeholder" => "Select Cabinet", "serverSide" => ["table" => "cabinets", "value" => "id", "text" => ["name"], "selected" => $result['cabinet_id'] ?? null, "where" => "deleted=0"]]);
                                                    } else {
                                                        draw_input(["type" => "hidden", "name_id" => "{$table}__cabinet_id", "value" => $_SESSION['user']['cabinet_id']]);
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="col-md-6 mb-1">
                                                <?php draw_input(["label" => $GLOBALS['language']['firstname'], "type" => "text", "name_id" => "{$table}__first_name", "placeholder" => "Prénom", "value" => $result['first_name'] ?? '']); ?>
                                            </div>
                                            <div class="col-md-6 mb-1">
                                                <?php draw_input(["label" => $GLOBALS['language']['lastname'], "type" => "text", "name_id" => "{$table}__last_name", "placeholder" => "Nom", "value" => $result['last_name'] ?? '']); ?>
                                            </div>
                                            <div class="col-md-6 mb-1">
                                                <?php draw_input(["label" => $GLOBALS['language']['email'], "type" => "email", "name_id" => "{$table}__email", "placeholder" => "Email", "value" => $result['email'] ?? '']); ?>
                                            </div>
                                            <div class="col-md-6 mb-1">
                                                <?php draw_input(["label" => $GLOBALS['language']['phone'], "type" => "text", "name_id" => "{$table}__phone", "placeholder" => "Téléphone", "value" => $result['phone'] ?? '']); ?>
                                            </div>

                                            <?php if ($show_location): ?>
                                                <div class="col-12">
                                                    <hr class="my-1">
                                                </div>
                                                <div class="col-md-6 mb-1">
                                                    <?php draw_select(["label" => $GLOBALS['language']['willaya'], "name_id" => "regien", "placeholder" => "Wilaya", "class" => "excluded", "serverSide" => ["table" => "willaya", "value" => "id", "text" => ["willaya"], "selected" => $result ? ($result['willayaId'] ?? null) : null, "where" => ""]]); ?>
                                                </div>
                                                <div class="col-md-6 mb-1">
                                                    <?php draw_select(["label" => $GLOBALS['language']['commune'], "name_id" => "{$table}__commune_id", "placeholder" => "Commune", "his_parent" => "#regien", "serverSide" => ["table" => "communes", "value" => "id", "value_parent" => "id_willaya", "text" => ["name"], "selected" => $result ? ($result['communeId'] ?? null) : null, "where" => ""]]); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Professional Tab -->
                            <?php if ($show_specialty): ?>
                                <div class="tab-pane fade" id="content-pro" role="tabpanel">
                                    <div class="card">
                                        <div class="card-header border-bottom">
                                            <h4 class="card-title">Détails Professionnels</h4>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div class="row">
                                                <div class="col-md-6 mb-1">
                                                    <?php draw_select(["label" => $GLOBALS['language']['speciality'], "name_id" => "{$table}__specialty_id", "placeholder" => "Spécialité", "serverSide" => ["table" => "specialty", "value" => "id", "text" => ["namefr"], "selected" => $result ? ($result['specialty_id'] ?? null) : null, "where" => ""]]); ?>
                                                </div>
                                                <div class="col-md-6 mb-1">
                                                    <?php draw_input(["label" => "Numéro d'ordre", "type" => "text", "name_id" => "{$table}__numero_ordre", "placeholder" => "Ex: 12345/DZ", "value" => $result['numero_ordre'] ?? '']); ?>
                                                </div>
                                                <div class="col-md-12 mb-1">
                                                    <?php draw_input(["label" => "Degré / Titre", "type" => "text", "name_id" => "{$table}__degree", "placeholder" => "Ex: Professeur, Spécialiste...", "value" => $result['degree'] ?? '']); ?>
                                                </div>
                                                <div class="col-md-12 mb-1">
                                                    <?php draw_text_area(["label" => "Biographie", "rows" => "3", "name_id" => "{$table}__description", "placeholder" => "Parlez de votre parcours...", "value" => $result['description'] ?? '']); ?>
                                                </div>

                                                <div class="col-12">
                                                    <hr class="my-1">
                                                </div>
                                                <div class="col-md-6 mb-1">
                                                    <div class="input-group input-group-merge">
                                                        <span class="input-group-text"><i
                                                                data-feather="facebook"></i></span>
                                                        <input type="text" class="form-control"
                                                            name="<?= $table ?>__facebook" placeholder="Lien Facebook"
                                                            value="<?= $result['facebook'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-1">
                                                    <div class="input-group input-group-merge">
                                                        <span class="input-group-text"><i
                                                                data-feather="instagram"></i></span>
                                                        <input type="text" class="form-control"
                                                            name="<?= $table ?>__instagram" placeholder="Lien Instagram"
                                                            value="<?= $result['instagram'] ?? '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-1">
                                                    <?php draw_switch(["label" => "Cabinet Ouvert (Statut)", "name_id" => "{$table}__is_opened", "checked" => $result['is_opened'] ?? 0]); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>


                            <!-- 3. Planning Tab -->
                            <?php if ($show_medical_scheduler): ?>
                                <div class="tab-pane fade" id="content-planning" role="tabpanel">
                                    <div class="card">
                                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                                            <h4 class="card-title"><i data-feather="calendar"></i> Planning & Horaires</h4>
                                            <button type="button" class="btn btn-sm btn-outline-primary waves-effect" id="apply-to-all-btn">
                                                <i data-feather="copy" class="me-50"></i> Appliquer Dimanche à tous
                                            </button>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div class="alert alert-primary p-1 mb-2">
                                                <i data-feather="info" class="me-50"></i> Activez les jours de travail et définissez les horaires.
                                            </div>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-borderless planning-table">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 25%;">Jour / Statut</th>
                                                            <th style="width: 20%;">Capacité (Tickets)</th>
                                                            <th style="width: 55%;">Horaires (Début - Fin)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $days_en = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
                                                    $days_fr = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];

                                                    foreach ($days_en as $index => $day_en) {
                                                        $day_fr = $days_fr[$index];
                                                        $input_id = $day_fr;

                                                        // Fetch Values
                                                        $val_tickets = 0;
                                                        if (isset($ticket_days_data[$day_fr])) $val_tickets = $ticket_days_data[$day_fr];
                                                        elseif (isset($ticket_days_data[$day_en])) $val_tickets = $ticket_days_data[$day_en];
                                                        elseif (isset($ticket_days_data[strtolower($day_en)])) $val_tickets = $ticket_days_data[strtolower($day_en)];
                                                        elseif (isset($ticket_days_data[strtolower($day_fr)])) $val_tickets = $ticket_days_data[strtolower($day_fr)];

                                                        $day_hours = [];
                                                        if (isset($work_hours_data[$day_fr])) $day_hours = $work_hours_data[$day_fr];
                                                        elseif (isset($work_hours_data[$day_en])) $day_hours = $work_hours_data[$day_en];
                                                        elseif (isset($work_hours_data[strtolower($day_en)])) $day_hours = $work_hours_data[strtolower($day_en)];
                                                        elseif (isset($work_hours_data[strtolower($day_fr)])) $day_hours = $work_hours_data[strtolower($day_fr)];

                                                        $val_from = $day_hours['from'] ?? '';
                                                        $val_to = $day_hours['to'] ?? '';

                                                        $is_open = (!empty($val_from) && !empty($val_to));
                                                        $row_class = $is_open ? '' : 'closed-day';
                                                        $switch_checked = $is_open ? 'checked' : '';
                                                        $disabled_attr = $is_open ? '' : 'disabled';
                                                        ?>
                                                        <tr class="schedule-row <?= $row_class ?>" id="row-<?= $input_id ?>">
                                                            <td>
                                                                <div class="day-wrapper">
                                                                    <div class="form-check form-switch form-check-primary">
                                                                        <input type="checkbox" class="form-check-input day-switch" 
                                                                               id="switch-<?= $input_id ?>" 
                                                                               data-target="<?= $input_id ?>" 
                                                                               <?= $switch_checked ?>>
                                                                        <label class="form-check-label" for="switch-<?= $input_id ?>"></label>
                                                                    </div>
                                                                    <span class="day-name"><?= $day_fr ?></span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-merge">
                                                                    <span class="input-group-text"><i data-feather="hash"></i></span>
                                                                    <input type="number" class="form-control schedule-input" 
                                                                           id="<?= $input_id ?>" 
                                                                           placeholder="0" 
                                                                           value="<?= $val_tickets ?>"
                                                                           <?= $disabled_attr ?>>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="input-group input-group-merge">
                                                                        <span class="input-group-text"><i data-feather="clock"></i></span>
                                                                        <input type="time" class="form-control schedule-input time-from" 
                                                                               id="<?= $input_id ?>__from" 
                                                                               value="<?= $val_from ?>"
                                                                               <?= $disabled_attr ?>>
                                                                    </div>
                                                                    <span class="time-separator"><i data-feather="arrow-right" size="14"></i></span>
                                                                    <div class="input-group input-group-merge">
                                                                        <span class="input-group-text"><i data-feather="clock"></i></span>
                                                                        <input type="time" class="form-control schedule-input time-to" 
                                                                               id="<?= $input_id ?>__to" 
                                                                               value="<?= $val_to ?>"
                                                                               <?= $disabled_attr ?>>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                    ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <input type="hidden" name="<?= $table ?>__tickets_day"
                                                id="<?= $table ?>__tickets_day"
                                                value='<?= htmlspecialchars($ticket_days_raw) ?>'>
                                            <input type="hidden" name="<?= $table ?>__travel_hours"
                                                id="<?= $table ?>__travel_hours"
                                                value='<?= htmlspecialchars($work_hours_raw) ?>'>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- 4. Motifs Tab -->
                            <?php if ($show_motifs_section): ?>
                                <div class="tab-pane fade" id="content-motifs" role="tabpanel">
                                    <div class="card">
                                        <div
                                            class="card-header border-bottom d-flex justify-content-between align-items-center">
                                            <h4 class="card-title">Motifs de Consultation</h4>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#addMotifModal">
                                                <i data-feather="plus"></i> Ajouter
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Titre</th>
                                                            <th>Durée</th>
                                                            <th>Prix</th>
                                                            <th class="text-end">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="motifs-table-body"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- 5. Gallery Tab (FIXED) -->
                            <?php if ($show_images && $target_role === 'doctor'): ?>
                                <div class="tab-pane fade" id="content-gallery" role="tabpanel">
                                    <div class="card">
                                        <div class="card-header border-bottom">
                                            <h4 class="card-title">Galerie Photos</h4>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div class="gallery-grid">
                                                <?php for ($i = 2; $i <= 6; $i++): 
                                                    $db_val = $result["image{$i}"] ?? '';
                                                    // Ensure correct path logic
                                                    if (!empty($db_val)) {
                                                        $img_src = (filter_var($db_val, FILTER_VALIDATE_URL)) ? $db_val : SITE_URL . '/' . ltrim($db_val, '/');
                                                    } else {
                                                        $img_src = SITE_URL . '/assets/images/default_product.png';
                                                    }
                                                ?>
                                                    <div class="gallery-upload-box">
                                                        <!-- Added codexFileUp class for JS -->
                                                        <div class="avatar-upload codexFileUp">
                                                            <div class="avatar-edit">
                                                                <input type="file" id="image<?= $i ?>" class="codexInputFile"
                                                                    name="users__image<?= $i ?>" accept=".png, .jpg, .jpeg">
                                                                <label for="image<?= $i ?>"></label>
                                                            </div>
                                                            <div class="avatar-preview">
                                                                <img src="<?= $img_src ?>"
                                                                    id="codexPreviewImage" style="object-fit: cover;">
                                                            </div>
                                                            <input type="hidden" class="codexFileData"
                                                                data-name="users__image<?= $i ?>"
                                                                value="<?= $db_val ?>">
                                                        </div>
                                                        <div class="gallery-label">Photo <?= ($i - 1) ?></div>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Motif -->
<?php if ($show_motifs_section): ?>
    <div class="modal fade" id="addMotifModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <h5 class="modal-title">Gérer le Motif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="motifForm">
                    <div class="modal-body">
                        <input type="hidden" name="motif_id" id="motif_id" value="0">
                        <input type="hidden" name="doctor_id" value="<?= $id ?>">
                        <div class="mb-1">
                            <label class="form-label">Titre du motif</label>
                            <input type="text" class="form-control" name="title" id="motif_title" required
                                placeholder="Ex: Consultation Générale">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-1">
                                <label class="form-label">Durée (min)</label>
                                <input type="number" class="form-control" name="duration" id="motif_duration" value="30"
                                    required>
                            </div>
                            <div class="col-6 mb-1">
                                <label class="form-label">Prix (DA)</label>
                                <input type="number" class="form-control" name="price" id="motif_price" value="0"
                                    step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once 'foot.php'; ?>

<script>
    $(document).ready(function () {
        // Role Change Logic
        function handleRoleChange() {
            var selectedRole = $('#role_selector').val();
            var realRoleInput = $('input[name="<?= $table; ?>__role"]');

            if (selectedRole === 'super_admin') {
                realRoleInput.val('admin');
                $('#<?= $table; ?>__cabinet_id').val(null).trigger('change');
                $('.field-cabinet').hide();
            } else if (selectedRole === 'admin') {
                realRoleInput.val('admin');
                $('.field-cabinet').show();
            } else {
                realRoleInput.val(selectedRole);
                $('.field-cabinet').show();
            }
        }

        if ($('#role_selector').length > 0) {
            handleRoleChange();
            $('#role_selector').on('change', handleRoleChange);
        }

        // Validation
        $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__first_name': { required: true },
                '<?= $table; ?>__last_name': { required: true },
                '<?= $table; ?>__email': { required: true, email: true },
                '<?= $table; ?>__phone': { required: true }
            }
        });

        // --- Planning Logic (FIXED) ---
        <?php if ($is_edit_mode && $show_medical_scheduler): ?>
            
            // 1. Function to collect data and update hidden JSON inputs
            function updateScheduleJson() {
                let tickets = {};
                let workHours = {};

                $('.schedule-row').each(function () {
                    // Only process if the switch is checked (Day is Open)
                    if ($(this).find('.day-switch').is(':checked')) {
                        let dayKey = $(this).find('input[type="number"]').attr('id');
                        let ticketVal = $(this).find('input[type="number"]').val();
                        let fromVal = $(this).find('.time-from').val();
                        let toVal = $(this).find('.time-to').val();

                        // Only add if times are set (optional validation)
                        if(fromVal && toVal) {
                            tickets[dayKey] = ticketVal;
                            workHours[dayKey] = { "from": fromVal, "to": toVal };
                        }
                    }
                });

                $("#<?= $table; ?>__tickets_day").val(JSON.stringify(tickets));
                $("#<?= $table; ?>__travel_hours").val(JSON.stringify(workHours));
            }

            // 2. Handle Switch Toggle (Open/Close Day)
            $('.day-switch').on('change', function() {
                let targetId = $(this).data('target');
                let row = $('#row-' + targetId);
                let inputs = row.find('.schedule-input');

                if ($(this).is(':checked')) {
                    row.removeClass('closed-day');
                    inputs.prop('disabled', false);
                } else {
                    row.addClass('closed-day');
                    inputs.prop('disabled', true);
                }
                updateScheduleJson();
            });

            // 3. Handle Input Changes
            $(document).on('input change', '.schedule-input', function () {
                updateScheduleJson();
            });

            // 4. "Apply to All" Feature
            $('#apply-to-all-btn').on('click', function() {
                // Get Source Data (Sunday / Dimanche)
                let sourceRow = $('#row-Dimanche');
                
                // Check if Sunday is active
                if (!sourceRow.find('.day-switch').is(':checked')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Attention',
                        text: 'Veuillez d\'abord activer et configurer le Dimanche.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                    return;
                }

                let srcTickets = sourceRow.find('input[type="number"]').val();
                let srcFrom = sourceRow.find('.time-from').val();
                let srcTo = sourceRow.find('.time-to').val();

                // Apply to all other rows
                $('.schedule-row').not('#row-Dimanche').each(function() {
                    let row = $(this);
                    
                    // Activate Switch
                    let switchBtn = row.find('.day-switch');
                    if (!switchBtn.is(':checked')) {
                        switchBtn.prop('checked', true).trigger('change');
                    }

                    // Set Values
                    row.find('input[type="number"]').val(srcTickets);
                    row.find('.time-from').val(srcFrom);
                    row.find('.time-to').val(srcTo);
                });

                updateScheduleJson();
                
                // Visual Feedback
                Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: 'Planning appliqué à tous les jours',
                    showConfirmButton: false,
                    timer: 1000
                });
            });

        <?php endif; ?>

        // Motifs Logic
        <?php if ($show_motifs_section): ?>
            function loadMotifs() {
                $.ajax({
                    url: '<?= SITE_URL ?>/handlers',
                    type: 'POST',
                    data: { method: 'get_doctor_motifs', doctor_id: <?= $id ?> },
                    dataType: 'json',
                    success: function (res) {
                        var html = '';
                        if (res.state === 'true' && res.data.length > 0) {
                            res.data.forEach(function (m) {
                                html += `<tr>
                                    <td><span class="fw-bold">${m.title}</span></td>
                                    <td>${m.duration} min</td>
                                    <td>${m.price > 0 ? m.price + ' DA' : 'Gratuit'}</td>
                                    <td class="text-end">
                                        <button class="btn btn-icon btn-flat-primary btn-sm edit-motif" data-id="${m.id}" data-title="${m.title}" data-duration="${m.duration}" data-price="${m.price}"><i data-feather="edit"></i></button>
                                        <button class="btn btn-icon btn-flat-danger btn-sm delete-motif" data-id="${m.id}"><i data-feather="trash"></i></button>
                                    </td>
                                </tr>`;
                            });
                        } else {
                            html = '<tr><td colspan="4" class="text-center text-muted p-3">Aucun motif configuré.</td></tr>';
                        }
                        $('#motifs-table-body').html(html);
                        if (feather) feather.replace();
                    }
                });
            }
            loadMotifs();

            $('#motifForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '<?= SITE_URL ?>/handlers',
                    type: 'POST',
                    data: $(this).serialize() + '&method=save_doctor_motif',
                    dataType: 'json',
                    success: function (res) {
                        if (res.state === 'true') {
                            $('#addMotifModal').modal('hide');
                            loadMotifs();
                            Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500, showConfirmButton: false });
                        } else {
                            Swal.fire('Erreur', res.message, 'error');
                        }
                    }
                });
            });

            $(document).on('click', '.edit-motif', function () {
                $('#motif_id').val($(this).data('id'));
                $('#motif_title').val($(this).data('title'));
                $('#motif_duration').val($(this).data('duration'));
                $('#motif_price').val($(this).data('price'));
                $('#addMotifModal').modal('show');
            });

            $(document).on('click', '.delete-motif', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= SITE_URL ?>/handlers',
                            type: 'POST',
                            data: { method: 'delete_doctor_motif', id: id },
                            dataType: 'json',
                            success: function (res) { loadMotifs(); }
                        });
                    }
                });
            });
            $('#addMotifModal').on('hidden.bs.modal', function () { $('#motifForm')[0].reset(); $('#motif_id').val(0); });
        <?php endif; ?>
    });
</script>