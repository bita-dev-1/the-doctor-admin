<?php 
    if(!isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/login');
    }

    include_once 'header.php'; 
?>

    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper p-0">

            <div class="content-body">
                <div class="row">
                    <div class="col-12">

                        <!-- security -->

                        <div class="card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title">Changer mot de passe</h4>
                            </div>
                            <div class="card-body pt-1">
                                <!-- form -->
                                <form class="validate-form change-pass">
                                    <div class="row">
                                        <div class="col-12 col-sm-6 mb-1">
                                            <label class="form-label" for="account-old-password">Mot de passe actuel</label>
                                            <div class="input-group form-password-toggle input-group-merge">
                                                <input type="password" class="form-control" id="account-old-password" name="password" placeholder="Entrer le mot de passe actuel" data-msg="S'il vous plait mot de passe actuel" />
                                                <div class="input-group-text cursor-pointer">
                                                    <i data-feather="eye"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 col-sm-6 mb-1">
                                            <label class="form-label" for="account-new-password">nouveau mot de passe</label>
                                            <div class="input-group form-password-toggle input-group-merge">
                                                <input type="password" id="account-new-password" name="new-password" class="form-control" placeholder="Entrez un nouveau mot de passe" />
                                                <div class="input-group-text cursor-pointer">
                                                    <i data-feather="eye"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 mb-1">
                                            <label class="form-label" for="account-retype-new-password">Retaper le nouveau mot de passe</label>
                                            <div class="input-group form-password-toggle input-group-merge">
                                                <input type="password" class="form-control excluded" id="account-retype-new-password" name="confirm-new-password" placeholder="Confirmez votre nouveau mot de passe" />
                                                <div class="input-group-text cursor-pointer"><i data-feather="eye"></i></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary me-1 mt-1">Sauvegarder les modifications</button>
                                        </div>
                                    </div>
                                </form>
                                <!--/ form -->
                            </div>
                        </div>

                        <!--/ security -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END: Content-->
    
<?php include "foot.php";?>    

<script>
    $(document).ready(function(){
        $('.change-pass').validate({
          rules: {
            'password': {
              required: true
            },
            'new-password': {
              required: true
            },
            'confirm-new-password': {
                required: true,
                equalTo: '#account-new-password'
            },
          }
        });
    });
</script>
    