<?php
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';
?>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <section id="ajax-datatable">
            <div class="row">
                <div class="col-12">
                    <div class="card p-1">
                        <div class="card-header border-bottom">
                            <h4 class="card-title">Dossiers de Rééducation</h4>
                        </div>
                        <div class="card-datatable">
                            <?php draw_table(array('query' => "qr_reeducation_dossiers_table", "table" => "reeducation_dossiers")); ?>
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
        "query": "qr_reeducation_dossiers_table",
        "method": "data_table",
        "actions": [
            { "action": "edit", "url": "<?= SITE_URL; ?>/reeducation/update/" },
            { "action": "delete", "url": "#" }
        ],
        "button": [
            {
                "text": "Nouveau Dossier",
                "class": "btn btn-primary",
                "url": "<?= SITE_URL; ?>/reeducation/insert"
            }
        ]
    };
    call_data_table(request);
</script>