<?php
// MODIFIED: Corrected security check to allow all admin roles and doctors/nurses
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';

$table = 'patient';
$btn_text = $GLOBALS['language']['add'];
$result = false;
$where = "";
$breadcrumb = $GLOBALS['language']['add'];

if (isset($id) && !empty($id)) {
    $btn_text = $GLOBALS['language']['save'];
    $breadcrumb = $GLOBALS['language']['edit'];
    $where = array("column" => "id", "val" => $id);
    $result = getPatients($id, true)[0] ?? false;
}

?>
<div class="app-content content ">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-start mb-0">
                            <?= $breadcrumb . ' ' . $GLOBALS['language']['patient']; ?>
                        </h2>
                        <div class="breadcrumb-wrapper">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a
                                        href="<?= SITE_URL; ?>/"><?= $GLOBALS['language']['Home']; ?></a></li>
                                <li class="breadcrumb-item active">
                                    <a><?= $breadcrumb . ' ' . $GLOBALS['language']['patient']; ?></a>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-12">

            <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                data-update="<?= customEncryption(json_encode($where)); ?>">
                <?php set_csrf() ?>

                <!-- START: MODIFIED - Added hidden input for cabinet_id -->
                <input type="hidden" name="<?= $table; ?>__cabinet_id"
                    value="<?= $_SESSION['user']['cabinet_id'] ?? ''; ?>">
                <!-- END: MODIFIED -->

                <div class="row">
                    <div class="col-md-3 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="col-xs-6 col-md-12">
                                    <div class="mb-1 avatar-square">
                                        <?php
                                        $input = array(
                                            "label" => "",
                                            "type" => "avatar", //dropArea , avatar, file
                                            "name_id" => "{$table}__image",
                                            "accept" => ".png, .jpg, .jpeg, .jfif",
                                            "class" => "",
                                            "value" => $result['image'] ?? null
                                        );
                                        draw_fileUpload($input);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 col-12">
                        <div class="card">
                            <div class="card-header border-bottom">
                                <h4 class="card-title">
                                    <?php echo $GLOBALS['language']['Details_of'] . ' ' . $GLOBALS['language']['profile']; ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['username'],
                                            "type" => "text",
                                            "name_id" => "{$table}__username",
                                            "placeholder" => $GLOBALS['language']['username'],
                                            "class" => "",
                                            "value" => $result['username'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['firstname'],
                                            "type" => "text",
                                            "name_id" => "{$table}__first_name",
                                            "placeholder" => $GLOBALS['language']['firstname'],
                                            "class" => "",
                                            "value" => $result['first_name'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['lastname'],
                                            "type" => "text",
                                            "name_id" => "{$table}__last_name",
                                            "placeholder" => $GLOBALS['language']['lastname'],
                                            "class" => "",
                                            "value" => $result['last_name'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['email'],
                                            "type" => "email",
                                            "name_id" => "{$table}__email",
                                            "placeholder" => $GLOBALS['language']['email'],
                                            "class" => "",
                                            "value" => $result['email'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['phone'],
                                            "type" => "text",
                                            "name_id" => "{$table}__phone",
                                            "placeholder" => $GLOBALS['language']['phone'],
                                            "class" => "",
                                            "value" => $result['phone'] ?? null
                                        );

                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['willaya'],
                                            "name_id" => "{$table}__regien",
                                            "placeholder" => $GLOBALS['language']['willaya'],
                                            "class" => "excluded",
                                            "his_parent" => "",
                                            "serverSide" => array(
                                                "table" => "willaya",
                                                "value" => "id",
                                                "value_parent" => "",
                                                "text" => array("willaya"),
                                                "selected" => $result ? ($result['willayaId'] ?? null) : null,
                                                "where" => ""
                                            )
                                        );
                                        draw_select($input);
                                        ?>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['commune'],
                                            "name_id" => "{$table}__commune_id",
                                            "placeholder" => $GLOBALS['language']['commune'],
                                            "class" => "",
                                            "his_parent" => "#{$table}__regien",
                                            "serverSide" => array(
                                                "table" => "communes",
                                                "value" => "id",
                                                "value_parent" => "id_willaya",
                                                "text" => array("name"),
                                                "selected" => $result ? ($result['communeId'] ?? null) : null,
                                                "where" => ""
                                            )
                                        );
                                        draw_select($input);
                                        ?>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['lat'],
                                            "type" => "text",
                                            "name_id" => "{$table}__lat",
                                            "placeholder" => $GLOBALS['language']['lat'],
                                            "class" => "",
                                            "value" => $result['lat'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-12 mb-1">
                                        <?php
                                        $input = array(
                                            "label" => $GLOBALS['language']['lng'],
                                            "type" => "text",
                                            "name_id" => "{$table}__lang",
                                            "placeholder" => $GLOBALS['language']['lng'],
                                            "class" => "",
                                            "value" => $result['lang'] ?? null
                                        );
                                        draw_input($input);
                                        ?>
                                    </div>
                                    <div class="col-12 mb-1">
                                        <?php
                                        $button = array(
                                            "text" => $btn_text,
                                            "type" => "submit",
                                            "name_id" => "submit",
                                            "class" => "btn-primary mt-2 w-auto ms-1 me-1"
                                        );
                                        draw_button($button);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
</div>
<?php include_once 'foot.php'; ?>
<script>
    $(document).ready(function () {
        $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__username': {
                    required: true
                },
                '<?= $table; ?>__first_name': {
                    required: true
                },
                '<?= $table; ?>__last_name': {
                    required: true
                },
                '<?= $table; ?>__email': {
                    required: true,
                    email: true
                },
                '<?= $table; ?>__phone': {
                    required: true
                },
                '<?= $table; ?>__regien': {
                    required: true
                },
                '<?= $table; ?>__commune_id': {
                    required: true
                }
            }
        });
    });
</script>