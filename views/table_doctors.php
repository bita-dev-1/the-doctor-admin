<?php 
    if(!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin'){
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
                        <div class="card-header border-bottom">
                            <h4 class="card-title"><?= $GLOBALS['language']['users'] ?></h4>
                        </div>
                        <div class="card-datatable">
                            <?php draw_table(array( 'query' => "qr_users_table", "table" => "users" )); ?>
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
        "query": "qr_users_table",
        "method": "data_table",
        "actions": [
            {
                "action" : "edit",
                "url" : "<?= SITE_URL; ?>/users/update/"
            },
            // --- MODIFIED ACTION FOR PASSWORD RESET ---
            {
                "action" : "reset_password",
                "class"  : "reset-password-record" 
                // The icon is now handled by the PHP function
            },
            {
                "action" : "delete",
                "url" : "#" // Action will be handled by JS for deactivation
            }
        ],
        "button":[
            {
                "text": "<?= $GLOBALS['language']['add'].' '.$GLOBALS['language']['user']; ?>",
                "class": "btn btn-primary",
                "url" : "<?= SITE_URL; ?>/users/insert"
            },
            {
                "text": "<?= $GLOBALS['language']['export']; ?>",
                "class": "btn btn-outline-secondary dropdown-toggle ms-50",
                "collection" : [
                    { "text": "Print", "role": "print", "exportOptions": { "columns": [ 0, 2, 3, 4, 5, 6, 7, 8 ] } },
                    { "text": "Csv", "role": "csv", "exportOptions": { "columns": [ 0, 2, 3, 4, 5, 6, 7, 8 ] } },
                    { "text": "Excel", "role": "excel", "exportOptions": { "columns": [ 0, 2, 3, 4, 5, 6, 7, 8 ] } },
                    { "text": "Pdf", "role": "pdf", "exportOptions": { "columns": [ 0, 2, 3, 4, 5, 6, 7, 8 ] } }
                ]

            }  
        ]
    };

   call_data_table(request);

    // --- NEW JAVASCRIPT FOR PASSWORD RESET ---
    $(document).on('click', '.reset-password-record', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var userName = $(this).closest('tr').find('td').eq(2).text(); // Get user's full name from the table

        Swal.fire({
            title: 'Êtes-vous sûr?',
            text: `Voulez-vous vraiment réinitialiser le mot de passe pour "${userName}" ? Un nouveau mot de passe sera envoyé par e-mail.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, réinitialiser!',
            cancelButtonText: 'Annuler',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-danger ms-1'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url: '<?= SITE_URL; ?>/handlers',
                    type: 'POST',
                    data: {
                        method: 'adminResetPassword',
                        id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.state === "true") {
                            Swal.fire({
                                icon: 'success',
                                title: 'Succès!',
                                text: response.message,
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur!',
                                text: response.message || 'Une erreur est survenue.',
                                customClass: {
                                    confirmButton: 'btn btn-danger'
                                }
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur!',
                            text: 'Une erreur de communication est survenue.',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            }
                        });
                    }
                });
            }
        });
    });

</script>