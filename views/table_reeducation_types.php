<?php
// --- START: MODIFIED SECURITY CHECK ---
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    header('location:' . SITE_URL . '/');
    exit();
}
// --- END: MODIFIED SECURITY CHECK ---

include_once 'header.php';
?>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <section id="ajax-datatable">
            <div class="row">
                <div class="col-12">
                    <div class="card p-1">
                        <div class="card-header border-bottom">
                            <h4 class="card-title">Types de Rééducation</h4>
                        </div>
                        <div class="card-datatable">
                            <?php draw_table(array('query' => "qr_reeducation_types_table", "table" => "reeducation_types")); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include_once 'foot.php'; ?>

<script>
    var request = {
        "query": "qr_reeducation_types_table",
        "method": "data_table",
        "actions": [
            { "action": "edit", "url": "<?= SITE_URL; ?>/reeducation-types/update/" },
            { "action": "delete", "url": "#" }
        ],
        "button": [
            {
                "text": "Ajouter un Type",
                "class": "btn btn-primary",
                "url": "<?= SITE_URL; ?>/reeducation-types/insert"
            }
        ]
    };
    call_data_table(request);
</script>