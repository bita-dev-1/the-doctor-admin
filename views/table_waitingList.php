<?php 
    if(!isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php'; 
?>
<style>
    tr:hover {
        background-color: #f5f5f5;
    }
</style>

<div class="app-content content ">
    <div class="content-wrapper p-0">
        <section id="ajax-datatable">
            <div class="row">
                <div class="col-md-9 col-12">
                    <div class="card p-1">
                        <div class="card-datatable">
                            <?php draw_table(array( 'query' => "qr_waitingList_table", "table" => "rdv" )); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-12">
                    <div class="card">
                        <div class="card-body card-patient">
                            <div class="user-avatar-section">
                                <div class="d-flex align-items-center flex-column">
                                    <img class="img-fluid rounded mt-3 mb-2" src="<?= SITE_URL ?>/assets/images/default_User.png" height="130" width="130" alt="patient avatar">
                                    <div class="user-info text-center">
                                        <h4><?=  $GLOBALS['language']['lastname'].' '.$GLOBALS['language']['firstname'] ?></h4>
                                        <span class="badge bg-light-secondary"><?=  $GLOBALS['language']['patient'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <h4 class="fw-bolder border-bottom pb-50 my-1">Details</h4>
                            <div class="info-container">
                                <ul class="list-unstyled">
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['firstname'] ?>: </span> <span><?=  $GLOBALS['language']['patient'].' '.$GLOBALS['language']['firstname'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['lastname'] ?>: </span><span><?=  $GLOBALS['language']['patient'].' '.$GLOBALS['language']['lastname'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['email'] ?>: </span><span><?=  $GLOBALS['language']['patient'].' '.$GLOBALS['language']['email'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['phone'] ?>: </span><span><?=  $GLOBALS['language']['patient'].' '.$GLOBALS['language']['phone'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['Role'] ?>: </span><span><?=  $GLOBALS['language']['patient'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['willaya'] ?>: </span><span><?=  $GLOBALS['language']['patient'].' '.$GLOBALS['language']['willaya'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['commune'] ?>: </span><span><?=  $GLOBALS['language']['patient'].' '.$GLOBALS['language']['commune'] ?></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include_once 'foot.php'; ?>

<script>
    let request = {
        "query": "qr_waitingList_table",
        "method": "data_table",
        <?= $_SESSION['user']['data'][0]['type'] == 0 ? "condition: 'doctor_id = {$_SESSION['user']['data'][0]['id']}'" : "" ?>
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

        let ajax;
        $(document).on('click', 'tbody tr', function(e){
            e.preventDefault();
            e.stopPropagation();

            if(ajax)
                ajax.abort();
                
            let self = $(this), html = "",
            data = {
                id:     self.find('td:first-child').text(),
                method: "getRdvPatient" 
            };
           
            ajax = $.ajax({
                type: "POST",
                url: "<?= SITE_URL ?>/handlers",
                data: data,
                dataType: "json",
                beforeSend: function(){
                    self.attr("disabled","disabled");
                },
                success: function(data) {

                    if (data.length && data[0].hasOwnProperty('id')) {
                        
                        let img = $.trim(data[0].image) ? data[0].image : "<?= SITE_URL ?>/assets/images/default_User.png";
                        html = `
                            <div class="user-avatar-section">
                                <div class="d-flex align-items-center flex-column">
                                    <img class="img-fluid rounded mt-3 mb-2" src="${img}" height="130" width="130" alt="patient avatar">
                                    <div class="user-info text-center">
                                        <h4>${data[0].first_name} ${data[0].last_name}</h4>
                                        <span class="badge bg-light-secondary"><?= $GLOBALS['language']['patient'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <h4 class="fw-bolder border-bottom pb-50 my-1">Details</h4>
                            <div class="info-container">
                                <ul class="list-unstyled">
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['firstname'] ?>: </span> <span>${data[0].first_name}</span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['lastname'] ?>: </span><span>${data[0].last_name}</span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['email'] ?>: </span><span>${data[0].email}</span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['phone'] ?>: </span><span>${data[0].phone}</span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['Role'] ?>: </span><span><?=  $GLOBALS['language']['patient'] ?></span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['willaya'] ?>: </span><span>${data[0].willaya}</span></li>
                                    <li class="mb-75"><span class="fw-bolder me-25"><?=  $GLOBALS['language']['commune'] ?>: </span><span>${data[0].communeName}</span></li>
                                </ul>
                            </div>
                        `;
                        $('.card-patient').html(html);

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
                }
            });
        });
    });

</script>