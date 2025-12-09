<form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
    data-update="<?= customEncryption(json_encode($where)); ?>">
    <?php set_csrf() ?>

    <div class="row match-height">
        <!-- LEFT COLUMN: Patient & Financials -->
        <div class="col-lg-8 col-12">
            <!-- 1. Patient Info -->
            <div class="card card-modern h-100">
                <div class="card-body">
                    <div class="section-header">
                        <div class="section-icon"><i data-feather="user"></i></div>
                        <h4 class="section-title">Informations Patient & Orientation</h4>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-1">
                            <label class="form-label">Patient</label>
                            <div class="input-group">
                                <div style="flex-grow: 1;">
                                    <?php
                                    // --- التعديل هنا: إضافة فلتر العيادة ---
                                    $patient_where = "deleted=0";
                                    if (!empty($_SESSION['user']['cabinet_id'])) {
                                        $patient_where .= " AND cabinet_id=" . intval($_SESSION['user']['cabinet_id']);
                                    }

                                    draw_select([
                                        "label" => "",
                                        "name_id" => "{$table}__patient_id",
                                        "placeholder" => "Rechercher un patient...",
                                        "attr" => $readonly_attr,
                                        "serverSide" => [
                                            "table" => "patient",
                                            "value" => "id",
                                            "text" => ["first_name", "last_name"],
                                            "selected" => $result['patient_id'] ?? null,
                                            "where" => $patient_where // استخدام الفلتر الجديد
                                        ]
                                    ]);
                                    ?>
                                </div>
                                <?php if (!$is_read_only): ?>
                                    <button class="btn btn-outline-primary" type="button" id="btn-add-quick-patient"
                                        title="Nouveau">
                                        <i data-feather="plus"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 col-12 mb-1">
                            <?php draw_select([
                                "label" => "Type de Dossier",
                                "name_id" => "{$table}__dossier_type",
                                "attr" => $readonly_attr,
                                "clientSideSelected" => $result['dossier_type'] ?? 'externe',
                                "clientSide" => [["option_text" => "Externe (Ordonnance)", "value" => "externe"], ["option_text" => "Interne (Consultation)", "value" => "interne"]]
                            ]); ?>
                        </div>

                        <div class="col-md-6 col-12 mb-1 external-only-field">
                            <?php draw_input(["label" => "Médecin Prescripteur", "type" => "text", "name_id" => "{$table}__oriented_by", "attr" => $readonly_attr, "placeholder" => "Nom du médecin...", "value" => $result['oriented_by'] ?? '']); ?>
                        </div>

                        <div class="col-12 mb-1 external-only-field">
                            <label class="form-label">Lettre d'Orientation</label>
                            <?php if (!$is_read_only): ?>
                                <div class="codexFileUp">
                                    <input type="file" class="form-control codexInputFile" id="medical_letter_input"
                                        accept=".pdf, .png, .jpg, .jpeg">
                                    <input type="hidden" class="codexFileData" name="<?= $table; ?>__medical_letter_path"
                                        value="<?= $result['medical_letter_path'] ?? '' ?>">
                                    <div class="mt-1 codexMultiPreviewImage">
                                        <?php if (!empty($result['medical_letter_path'])): ?>
                                            <div class="badge bg-light-secondary p-1"><i data-feather="paperclip"></i> Document
                                                joint</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif (!empty($result['medical_letter_path'])): ?>
                                <div><a href="<?= $result['medical_letter_path'] ?>" target="_blank"
                                        class="btn btn-sm btn-outline-secondary"><i data-feather="eye"></i> Voir le
                                        document</a></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="section-header mt-2">
                        <div class="section-icon" style="background: rgba(40, 199, 111, 0.12); color: #28c76f;"><i
                                data-feather="dollar-sign"></i></div>
                        <h4 class="section-title">Tarification & Service</h4>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-1">
                            <?php
                            draw_select([
                                "label" => "Service (Acte)",
                                "name_id" => "{$table}__reeducation_type_id",
                                "attr" => "required $readonly_attr",
                                "placeholder" => "Sélectionnez le soin...",
                                "serverSide" => [
                                    "table" => "cabinet_services",
                                    "join" => [(object) ["type" => "JOIN", "table" => "reeducation_types", "condition" => "cabinet_services.reeducation_type_id=reeducation_types.id"]],
                                    "value" => "reeducation_types.id",
                                    "text" => ["IF(cabinet_services.custom_name IS NOT NULL AND cabinet_services.custom_name != '', cabinet_services.custom_name, reeducation_types.name)"],
                                    "selected" => $result['reeducation_type_id'] ?? null,
                                    "where" => "cabinet_services.cabinet_id = " . intval($_SESSION['user']['cabinet_id']) . " AND cabinet_services.deleted=0"
                                ]
                            ]);
                            ?>
                        </div>

                        <div class="col-md-4 col-12 mb-1">
                            <?php draw_input(["label" => "Nombre Séances", "type" => "number", "name_id" => "{$table}__sessions_prescribed", "attr" => "required min='1' $readonly_attr", "value" => $result['sessions_prescribed'] ?? 1]); ?>
                        </div>

                        <div class="col-md-4 col-12 mb-1">
                            <label class="form-label">Remise (DA)</label>
                            <input type="number" name="<?= $table; ?>__discount_amount"
                                id="<?= $table; ?>__discount_amount" class="form-control"
                                value="<?= $result['discount_amount'] ?? '0.00' ?>" step="0.01" <?= $readonly_attr ?>>
                        </div>

                        <div class="col-md-4 col-12 mb-1">
                            <label class="form-label">Mode Paiement</label>
                            <input type="text" class="form-control bg-light" id="display_payment_mode" readonly
                                value="<?= ($result['payment_mode'] ?? '') === 'package' ? 'Forfait' : 'Par Séance' ?>">
                            <input type="hidden" name="<?= $table ?>__payment_mode" id="<?= $table ?>__payment_mode"
                                value="<?= $result['payment_mode'] ?? '' ?>">
                        </div>

                        <div class="col-12 mt-1">
                            <div class="price-display-box">
                                <span class="text-muted font-small-3">Total à Payer</span>
                                <span class="price-amount">
                                    <input type="number" name="<?= $table; ?>__price" id="<?= $table; ?>__price"
                                        class="form-control border-0 bg-transparent text-center p-0 fw-bolder text-success"
                                        style="font-size: 1.8rem;" value="<?= $result['price'] ?? '0.00' ?>" step="0.01"
                                        <?= $pricing_readonly ?>>
                                </span>
                                <span class="text-muted">DZD</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Technician & Planning -->
        <div class="col-lg-4 col-12">
            <?php include 'views/reeducation/partials/_calendar.php'; ?>
        </div>
    </div>
</form>