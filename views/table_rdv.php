<?php 
    if(!isset($_SESSION['user']['data'])){
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
                            <?php draw_table(array( 'query' => "qr_rdv_table", "table" => "rdv" )); ?>
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
        "query": "qr_rdv_table",
        "method": "data_table",
        <?= $_SESSION['user']['data'][0]['type'] == 0 ? "condition: 'doctor_id = {$_SESSION['user']['data'][0]['id']}'," : "" ?>
        "button":[
            {
                "text": "<?= $GLOBALS['language']['add'].' '.$GLOBALS['language']['rdv']; ?>",
                "class": "btn btn-primary",
                "url" : "<?= SITE_URL; ?>/rdv/insert"
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

    $(document).ready(function(){
        $(document).on('click', '.buttonstate', function(e){
            e.preventDefault();
            e.stopPropagation();

            let self = $(this),
            data = {
                id:     self.data('id'), 
                state:  self.data('value'), 
                method: "updateState" 
            };
            
            $.ajax({
                type: "POST",
                url: "<?= SITE_URL ?>/handlers",
                data: data,
                dataType: "json",
                beforeSend: function(){
                    let svg = '<svg class="seloader ps-25" height="14" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="currentColor"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
                        self.attr("disabled","disabled");
                        self.append(svg);
                },
                success: function(data) {
                    if (data.state != "false") {
                        self.parents('.table.dataTable').DataTable().ajax.reload( null, false );
                    }else{
                        Swal.fire({
                            title: 'something went wrong!, reload and try again',
                            icon: 'error',
                            confirmButtonText: 'back',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                    }
                },
                complete: function(){
                    self.removeAttr("disabled");
                    $('.seloader').remove();
                }
            });
        });
    });
</script>