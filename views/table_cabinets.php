<?php 
    // Super Admin check
    if(!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin' || !empty($_SESSION['user']['cabinet_id'])){
        header('location:'.SITE_URL.'/');
        exit();
    }
    include_once 'header.php'; 
?>

<div class="app-content content ">
    <div class="content-wrapper p-0">
        <section id="ajax-datatable">
            <div class="row">
                <div class="col-12">
                    <div class="card p-1">
                        <div class="card-header border-bottom">
                            <h4 class="card-title">Gestion des Cabinets</h4>
                        </div>
                        <div class="card-datatable">
                            <?php draw_table(array( 'query' => "qr_cabinets_table", "table" => "cabinets" )); ?>
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
        "query": "qr_cabinets_table",
        "method": "data_table",
        "actions": [
            {
                "action" : "edit",
                "url" : "<?= SITE_URL; ?>/cabinets/update/"
            },
            {
                "action" : "delete",
                "url" : "#" // Action will be handled by JS
            }
        ],
        "button":[
            {
                "text": "Ajouter un Cabinet",
                "class": "btn btn-primary",
                "url" : "<?= SITE_URL; ?>/cabinets/insert"
            }
        ]
    };

   call_data_table(request);
</script>