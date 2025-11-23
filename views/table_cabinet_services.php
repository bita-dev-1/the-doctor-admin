<?php
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin' || empty($_SESSION['user']['cabinet_id'])) {
    header('location:' . SITE_URL . '/');
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
                            <h4 class="card-title">Configuration des Tarifs & Services</h4>
                        </div>
                        <div class="card-datatable">
                            <?php
                            draw_table(array(
                                'query' => "qr_cabinet_services_table",
                                'table' => "cabinet_services",
                                // تم إضافة 'custom_name' و 'type_reeducation'
                                'columns' => ['id', 'custom_name', 'type_reeducation', 'pricing_model', 'commission_display', 'action']
                            ));
                            ?>
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
        "query": "qr_cabinet_services_table",
        "method": "data_table",
        "actions": [
            { "action": "edit", "url": "<?= SITE_URL; ?>/cabinet-services/update/" },
            { "action": "delete", "url": "#" }
        ],
        "button": [
            {
                "text": "Configurer un Service",
                "class": "btn btn-primary",
                "url": "<?= SITE_URL; ?>/cabinet-services/insert"
            }
        ]
    };
    call_data_table(request);
</script>