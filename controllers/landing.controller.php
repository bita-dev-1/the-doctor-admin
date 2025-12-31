<?php
// controllers/landing.controller.php

function getDoctorFullProfile($db, $id)
{
    $id = intval($id);

    if ($id <= 0) {
        return false;
    }

    // 1. Smart View Counter
    $viewKey = 'viewed_doctor_' . $id;
    $cooldown = 3600;

    if (!isset($_SESSION[$viewKey]) || (time() - $_SESSION[$viewKey] > $cooldown)) {
        try {
            // Use prepare/execute for update
            $stmt = $db->prepare("UPDATE users SET views = views + 1 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION[$viewKey] = time();
        } catch (Exception $e) {
            // Ignore view count errors
        }
    }

    // 2. Fetch Data (Secure)
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
                u.image4,
                u.image5,
                u.image6,
                u.facebook,
                u.instagram,
                u.travel_hours,
                u.is_opened,
                u.recomondation, 
                u.views,
                u.numero_ordre,
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
            WHERE u.id = ?";

    $result = $db->select($query, [$id]);

    if (!empty($result)) {
        $doctor = $result[0];

        // Allow 'admin' role (Doctor Owner) to have a landing page
        if (!in_array($doctor['role'], ['doctor', 'admin']) || $doctor['status'] !== 'active' || $doctor['deleted'] != 0) {
            return false;
        }

        // Fix Image Paths Helper
        $fixPath = function ($path) {
            if (empty($path))
                return null;
            if (filter_var($path, FILTER_VALIDATE_URL))
                return $path;
            return SITE_URI . ltrim($path, '/');
        };

        // Process ALL images
        $doctor['image1'] = $fixPath($doctor['image1']) ?? SITE_URI . 'assets/images/default_User.png';
        $doctor['image2'] = $fixPath($doctor['image2']);
        $doctor['image3'] = $fixPath($doctor['image3']);
        $doctor['image4'] = $fixPath($doctor['image4']);
        $doctor['image5'] = $fixPath($doctor['image5']);
        $doctor['image6'] = $fixPath($doctor['image6']);

        // Schedule
        $schedule = json_decode($doctor['travel_hours'] ?? '[]', true);
        $doctor['schedule'] = is_array($schedule) ? $schedule : [];
        unset($doctor['travel_hours']);

        // Defaults
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