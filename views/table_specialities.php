<?php 
    // MODIFIED: Super Admin check
    if(!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin' || !empty($_SESSION['user']['cabinet_id'])){
        header('location:'.SITE_URL.'/'); // Redirect non-super-admins to dashboard
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
                        <div class="card-datatable">
                            <?php draw_table(array( 'query' => "qr_specialities_table", "table" => "specialty" )); ?>
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
        "query": "qr_specialities_table",
        "method": "data_table",
        "actions": [
            {
                "action" : "edit",
                "url" : "<?= SITE_URL; ?>/specialities/update/"
            },
            {
                "action" : "delete",
                "url" : "#"
            }
        ],
        "button":[
            {
                "text": "<?= $GLOBALS['language']['add'].' '.$GLOBALS['language']['speciality']; ?>",
                "class": "btn btn-primary",
                "url" : "<?= SITE_URL; ?>/specialities/insert"
            },
            {
                "text": "<?= $GLOBALS['language']['export']; ?>",
                "class": "btn btn-outline-secondary dropdown-toggle ms-50",
                "collection" : [
                    {
                        "text": "Print",
                        "role": "print",
                        "exportOptions": { "columns": [0, 1, 2, 3] }
                    },
                    {
                        "text": "Csv",
                        "role": "csv",
                        "exportOptions": { "columns": [0, 1, 2, 3] }
                    },
                    {
                        "text": "Excel",
                        "role": "excel",
                        "exportOptions": { "columns": [0, 1, 2, 3] }
                    },
                    {
                        "text": "Pdf",
                        "role": "pdf",
                        "exportOptions": { "columns": [0, 1, 2, 3] }
                    }
                ]
            }  
        ]
    };

   call_data_table(request);
</script>