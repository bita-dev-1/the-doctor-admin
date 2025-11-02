<?php 
    if(!isset($_SESSION['user']['data']) || $_SESSION['user']['data'][0]['type'] == 0){
        header('location:'.SITE_URL.'/login');
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
                            <?php draw_table(array( 'query' => "qr_patients_table", "table" => "patient" )); ?>
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
        "query": "qr_patients_table",
        "method": "data_table",
        "actions": [
            {
                "action" : "edit",
                "url" : "<?= SITE_URL; ?>/patients/update/"
            },
            {
                "action" : "delete",
                "url" : "#"
            }
        ],
        "button":[
            {
                "text": "<?= $GLOBALS['language']['add'].' '.$GLOBALS['language']['patient']; ?>",
                "class": "btn btn-primary",
                "url" : "<?= SITE_URL; ?>/patients/insert"
            },
            {
                "text": "<?= $GLOBALS['language']['export']; ?>",
                "class": "btn btn btn-outline-secondary dropdown-toggle ms-50",
                "collection" : [
                    {
                        "text": "Print",
                        "role": "print",
                        "exportOptions": { "columns": [0, 1, 2, 3, 4] }
                    },
                    {
                        "text": "Csv",
                        "role": "csv",
                        "exportOptions": { "columns": [0, 1, 2, 3, 4] }
                    },
                    {
                        "text": "Excel",
                        "role": "excel",
                        "exportOptions": { "columns": [0, 1, 2, 3, 4] }
                    },
                    {
                        "text": "Pdf",
                        "role": "pdf",
                        "exportOptions": { "columns": [0, 1, 2, 3, 4] }
                    }
                ]

            }  
        ]
    };

   call_data_table(request);
</script>