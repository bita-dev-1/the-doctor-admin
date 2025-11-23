<?php
if (!isset($_SESSION['user']['id']) || !($_SESSION['user']['role'] === 'admin' && empty($_SESSION['user']['cabinet_id']))) {
    header('location:' . SITE_URL . '/');
    exit();
}
include_once 'header.php';

$table = 'reeducation_types';
$btn_text = 'Ajouter';
$result = false;
$where = "";
$breadcrumb = 'Nouveau';

if (isset($id) && !empty($id)) {
    $btn_text = 'Sauvegarder';
    $breadcrumb = 'Modifier';
    $where = array("column" => "id", "val" => $id);
    $result = dataById($where, $table)[0] ?? false;
}
?>
<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="col-12 mb-2">
                <h2 class="content-header-title float-start mb-0"><?= $breadcrumb; ?> Type de Rééducation</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="<?= SITE_URL; ?>/reeducation-types">Types de
                                Rééducation</a></li>
                        <li class="breadcrumb-item active"><a><?= $breadcrumb; ?> Type</a></li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-12">
            <form class="codexForm" method="post" data-express="<?= customEncryption($table); ?>"
                data-update="<?= customEncryption(json_encode($where)); ?>">
                <?php set_csrf() ?>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-1">
                                <?php draw_input(["label" => "Nom du Type", "type" => "text", "name_id" => "{$table}__name", "placeholder" => "Ex: Genou, Dos, Épaule...", "value" => $result['name'] ?? '']); ?>
                            </div>
                        </div>
                        <?php draw_button(["text" => $btn_text, "type" => "submit", "class" => "btn-primary"]); ?>
                    </div>
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
                '<?= $table; ?>__name': { required: true }
            }
        });
    });
</script>