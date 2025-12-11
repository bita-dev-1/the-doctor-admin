<?php

function getDoctorFullProfile($db, $id)
{
    $id = intval($id);

    if ($id <= 0) {
        return false;
    }

    // ============================================================
    // 1. Smart View Counter (زيادة عدد المشاهدات)
    // ============================================================
    $viewKey = 'viewed_doctor_' . $id;
    $cooldown = 3600; // ساعة واحدة

    if (!isset($_SESSION[$viewKey]) || (time() - $_SESSION[$viewKey] > $cooldown)) {
        try {
            // نستخدم وضع الصمت لتجنب توقف الصفحة في حال حدوث خطأ بسيط في التحديث
            $db->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $db->pdo->exec("UPDATE users SET views = views + 1 WHERE id = $id");
            $db->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $_SESSION[$viewKey] = time();
        } catch (Exception $e) {
            // تجاهل أخطاء العداد
        }
    }

    // ============================================================
    // 2. Fetch Data (جلب البيانات)
    // ============================================================

    // استخدام LEFT JOIN لضمان جلب الطبيب حتى لو كانت البيانات المرتبطة ناقصة
    $query = "SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.degree,
                u.description,
                u.phone,
                u.email,
                u.image1,
                u.image2,
                u.image3,
                u.facebook,
                u.instagram,
                u.travel_hours,
                u.is_opened,
                u.recomondation, 
                u.views,
                u.role,
                u.status,
                u.deleted,
                
                s.namefr AS specialty_fr,
                s.namear AS specialty_ar,
                
                c.name AS cabinet_name,
                c.address AS cabinet_address,
                c.phone AS cabinet_phone,
                
                com.name AS commune,
                w.willaya AS willaya

            FROM users u
            LEFT JOIN specialty s ON u.specialty_id = s.id
            LEFT JOIN cabinets c ON u.cabinet_id = c.id
            LEFT JOIN communes com ON u.commune_id = com.id
            LEFT JOIN willaya w ON com.id_willaya = w.id
            WHERE u.id = $id";

    $result = $db->select($query);

    if (!empty($result)) {
        $doctor = $result[0];

        // التحقق من أن المستخدم طبيب ونشط وغير محذوف
        if ($doctor['role'] !== 'doctor' || $doctor['status'] !== 'active' || $doctor['deleted'] != 0) {
            return false;
        }

        // معالجة مسارات الصور
        $fixPath = function ($path) {
            if (empty($path))
                return null;
            if (filter_var($path, FILTER_VALIDATE_URL))
                return $path;
            return SITE_URI . ltrim($path, '/');
        };

        $doctor['image1'] = $fixPath($doctor['image1']) ?? SITE_URI . 'assets/images/default_User.png';
        $doctor['image2'] = $fixPath($doctor['image2']);
        $doctor['image3'] = $fixPath($doctor['image3']);

        // معالجة ساعات العمل (JSON)
        $schedule = json_decode($doctor['travel_hours'] ?? '[]', true);
        $doctor['schedule'] = is_array($schedule) ? $schedule : [];
        unset($doctor['travel_hours']);

        // قيم افتراضية للحقول التي قد تكون فارغة بسبب LEFT JOIN
        $doctor['specialty_fr'] = $doctor['specialty_fr'] ?? 'Médecin';
        $doctor['commune'] = $doctor['commune'] ?? '';
        $doctor['willaya'] = $doctor['willaya'] ?? '';
        $doctor['cabinet_name'] = $doctor['cabinet_name'] ?? '';

        $doctor['lat'] = null;
        $doctor['lng'] = null;

        return $doctor;
    }

    return false;
}
?>