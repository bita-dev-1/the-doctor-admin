<?php
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin' || empty($_SESSION['user']['cabinet_id'])) {
    header('location:' . SITE_URL . '/');
    exit();
}
include_once 'header.php';

$table = 'cabinet_services';
$btn_text = 'Enregistrer';
$result = false;
$where = "";
$breadcrumb = 'Configuration';

if (isset($id) && !empty($id)) {
    $where = array("column" => "id", "val" => $id);
    $result = dataById($where, $table)[0] ?? false;
}

$pricing_rules = isset($result['pricing_rules']) ? $result['pricing_rules'] : '[]';
?>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="col-12 mb-2">
                <h2 class="content-header-title float-start mb-0"><?= $breadcrumb; ?> Service & Tarification</h2>
            </div>
        </div>
        <div class="col-md-12 col-12">
            <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                data-update="<?= customEncryption(json_encode($where)); ?>">
                <?php set_csrf() ?>
                <input type="hidden" name="<?= $table; ?>__cabinet_id" value="<?= $_SESSION['user']['cabinet_id'] ?>">
                <input type="hidden" name="<?= $table; ?>__pricing_rules" id="pricing_rules_input"
                    value='<?= $pricing_rules ?>'>

                <div class="row">
                    <!-- PART 1: BASIC INFO -->
                    <div class="col-md-6 col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Détails du Service</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- START: NEW CUSTOM NAME FIELD -->
                                    <div class="col-12 mb-1">
                                        <?php draw_input([
                                            "label" => "Nom personnalisé du service (Pour affichage)",
                                            "type" => "text",
                                            "name_id" => "{$table}__custom_name",
                                            "placeholder" => "Ex: Forfait Dos - 10 Séances",
                                            "value" => $result['custom_name'] ?? ''
                                        ]); ?>
                                        <small class="text-muted">Laissez vide pour utiliser le nom par défaut du
                                            type.</small>
                                    </div>
                                    <!-- END: NEW CUSTOM NAME FIELD -->

                                    <div class="col-12 mb-1">
                                        <?php
                                        $types_where = "deleted=0";
                                        draw_select(["label" => "Type de Rééducation (Technique)", "name_id" => "{$table}__reeducation_type_id", "placeholder" => "Choisir...", "serverSide" => ["table" => "reeducation_types", "value" => "id", "text" => ["name"], "selected" => $result['reeducation_type_id'] ?? null, "where" => $types_where]]);
                                        ?>
                                    </div>
                                    <div class="col-12 mb-1">
                                        <?php draw_select(["label" => "Modèle de Prix", "name_id" => "{$table}__pricing_model", "clientSideSelected" => $result['pricing_model'] ?? 'per_session', "clientSide" => [["option_text" => "Par Séance", "value" => "per_session"], ["option_text" => "Forfait (Package Global)", "value" => "package"]]]); ?>
                                    </div>

                                    <div class="col-12 mb-1 d-none" id="package-capacity-container">
                                        <?php draw_input(["label" => "Nombre de séances incluses dans le forfait", "type" => "number", "name_id" => "{$table}__package_capacity", "placeholder" => "Ex: 10", "value" => $result['package_capacity'] ?? '']); ?>
                                    </div>

                                    <div class="col-12 mb-1">
                                        <?php draw_input(["label" => "Durée moyenne de la séance (Minutes)", "type" => "number", "name_id" => "{$table}__session_duration", "value" => $result['session_duration'] ?? '30']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PART 2: DOCTOR COMMISSION -->
                        <div class="card mt-1">
                            <div class="card-header">
                                <h4 class="card-title">Rémunération du Praticien</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-1">
                                        <?php draw_select(["label" => "Type de Commission", "name_id" => "{$table}__commission_type", "clientSideSelected" => $result['commission_type'] ?? 'fixed', "clientSide" => [["option_text" => "Montant Fixe (DA)", "value" => "fixed"], ["option_text" => "Pourcentage (%)", "value" => "percent"]]]); ?>
                                    </div>
                                    <div class="col-6 mb-1">
                                        <?php draw_input(["label" => "Valeur", "type" => "number", "name_id" => "{$table}__commission_value", "attr" => "step='0.01'", "value" => $result['commission_value'] ?? '0']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PART 3: PRICING RULES (TIERS) -->
                    <div class="col-md-6 col-12">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Règles de Tarification (Paliers)</h4>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-tier-btn"><i
                                        data-feather="plus"></i> Ajouter Palier</button>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info p-1 mb-2">
                                    <small>Exemple: <br>1. De 1 à 5 séances : 2000 DA<br>2. De 6 à 999 séances : 800
                                        DA</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>De</th>
                                                <th>À</th>
                                                <th>Prix (DA)</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tiers-container"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-1">
                        <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary"]); ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>

<script>
    $(document).ready(function () {
        function updateIcons() { if (feather) feather.replace({ width: 14, height: 14 }); }

        function toggleCapacity() {
            var model = $('#<?= $table; ?>__pricing_model').val();
            if (model === 'package') {
                $('#package-capacity-container').removeClass('d-none');
                $('#<?= $table; ?>__package_capacity').attr('required', true);
            } else {
                $('#package-capacity-container').addClass('d-none');
                $('#<?= $table; ?>__package_capacity').removeAttr('required');
            }
        }
        $('#<?= $table; ?>__pricing_model').on('change', toggleCapacity);
        toggleCapacity();

        var tiersData = JSON.parse($('#pricing_rules_input').val() || '[]');
        if (tiersData.length === 0) { tiersData.push({ limit: 9999, price: 0 }); }

        function renderTiers() {
            var html = '';
            var previousLimit = 0;
            tiersData.sort((a, b) => a.limit - b.limit);
            tiersData.forEach((tier, index) => {
                var from = previousLimit + 1;
                html += `<tr>
                    <td><span class="fw-bold">${from}</span></td>
                    <td>${index === tiersData.length - 1 ? '<span class="badge badge-light-secondary">Reste</span>' : `<input type="number" class="form-control form-control-sm tier-limit" data-idx="${index}" value="${tier.limit}" style="width: 80px;">`}</td>
                    <td><input type="number" class="form-control form-control-sm tier-price" data-idx="${index}" value="${tier.price}" step="0.01"></td>
                    <td>${index === tiersData.length - 1 && index !== 0 ? `<button type="button" class="btn btn-icon btn-flat-danger remove-tier" data-idx="${index}"><i data-feather="trash"></i></button>` : ''}</td>
                </tr>`;
                previousLimit = tier.limit;
            });
            $('#tiers-container').html(html);
            updateIcons();
            updateHiddenInput();
        }

        function updateHiddenInput() {
            if (tiersData.length > 0) { tiersData[tiersData.length - 1].limit = 9999; }
            $('#pricing_rules_input').val(JSON.stringify(tiersData));
        }

        $('#add-tier-btn').on('click', function () {
            var lastTier = tiersData[tiersData.length - 1];
            var previousLimit = (tiersData.length > 1) ? tiersData[tiersData.length - 2].limit : 0;
            tiersData.pop();
            tiersData.push({ limit: previousLimit + 5, price: lastTier.price });
            tiersData.push({ limit: 9999, price: lastTier.price });
            renderTiers();
        });

        $(document).on('click', '.remove-tier', function () {
            var idx = $(this).data('idx');
            tiersData.splice(idx, 1); renderTiers();
        });

        $(document).on('change', '.tier-limit', function () {
            var idx = $(this).data('idx');
            var val = parseInt($(this).val());
            tiersData[idx].limit = val;
            renderTiers();
        });

        $(document).on('change', '.tier-price', function () {
            var idx = $(this).data('idx');
            tiersData[idx].price = parseFloat($(this).val());
            updateHiddenInput();
        });

        renderTiers();

        $('.codexForm').validate({
            rules: {
                '<?= $table; ?>__reeducation_type_id': { required: true },
                '<?= $table; ?>__session_duration': { required: true, min: 1 }
            }
        });
    });
</script>