<div class="card card-modern h-100">
    <div class="card-body">
        <div class="section-header">
            <div class="section-icon" style="background: rgba(0, 207, 232, 0.12); color: #00cfe8;"><i
                    data-feather="calendar"></i></div>
            <h4 class="section-title">Planification</h4>
        </div>

        <div class="mb-1">
            <?php
            // --- التعديل هنا: تحديد التقني تلقائياً إذا كان هو المستخدم الحالي ---
            $selected_tech = $result['technician_id'] ?? null;
            if (empty($selected_tech) && $_SESSION['user']['role'] === 'doctor') {
                $selected_tech = $_SESSION['user']['id'];
            }

            draw_select([
                "label" => "Technicien Assigné",
                "name_id" => "{$table}__technician_id",
                "attr" => $readonly_attr,
                "placeholder" => "Choisir...",
                "serverSide" => [
                    "table" => "users",
                    "value" => "id",
                    "text" => ["first_name", "last_name"],
                    "selected" => $selected_tech,
                    "where" => "role='doctor' AND deleted=0 AND cabinet_id=" . intval($_SESSION['user']['cabinet_id'])
                ]
            ]);
            ?>
        </div>

        <!-- Hidden Commission Field -->
        <input type="hidden" name="<?= $table; ?>__technician_percentage" id="<?= $table; ?>__technician_percentage"
            value="<?= $result['technician_percentage'] ?? '0' ?>">

        <?php if (!$is_read_only): ?>
            <div class="planning-calendar-wrapper mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <button type="button" class="btn btn-icon btn-flat-secondary btn-sm rounded-circle" id="cal-prev"><i
                            data-feather="chevron-left"></i></button>
                    <span class="fw-bolder font-small-3">Sélection Dates</span>
                    <button type="button" class="btn btn-icon btn-flat-secondary btn-sm rounded-circle" id="cal-next"><i
                            data-feather="chevron-right"></i></button>
                </div>

                <div id="calendar-top" class="mb-1"></div>
                <div class="calendar-divider"></div>
                <div id="calendar-bottom"></div>

                <!-- Hidden input for dates -->
                <input type="hidden" name="initial_sessions_dates" id="initial_sessions_dates" value="[]">
            </div>

            <div id="day-info-alert" class="alert alert-primary p-50 mb-1 d-none font-small-2" role="alert">
                <i data-feather="info" class="me-50"></i> <span id="day-info-text"></span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold font-small-3">Sélection: <span id="selected-count" class="text-primary">0</span> /
                    <span id="max-sessions-display"><?= $result['sessions_prescribed'] ?? 1 ?></span></span>
                <button type="button" class="btn btn-sm btn-flat-danger" id="clear-selection-btn">Effacer</button>
            </div>

            <div class="d-grid gap-1">
                <?php if ($is_edit_mode): ?>
                    <button type="button" class="btn btn-outline-info" id="generate-sessions-btn" data-dossier-id="<?= $id ?>">
                        <i data-feather="refresh-cw" class="me-50"></i> Générer Séances
                    </button>
                <?php endif; ?>

                <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary btn-lg waves-effect waves-float waves-light"]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>