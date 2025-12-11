<?php

require_once("router/router.php");

include_once('config/DB.php');
include_once('controllers/api.controller.php');

$db = new DB();

// ... (المسارات القديمة تبقى كما هي) ...
post('/api/v1/doctors', 'middleware/doctors.php');
post('/api/v1/rdv', 'middleware/rdv.php');
post('/api/v1/rdv/me', 'middleware/rdv.php');
any('/api/v1/notifications', 'middleware/notifications.php');

post('/api/v1/upload', 'middleware/upload.php');
post('/api/v1/endpoint', 'middleware/endpoint.php');

// --- START: NEW PUBLIC ROUTES FOR LANDING PAGE ---
any('/api/v1/doctor/landing', 'middleware/doctor_landing.php'); // Doctor Info
any('/api/v1/public/availability', 'middleware/public_availability.php'); // Check Slots
post('/api/v1/public/book', 'middleware/public_booking.php'); // Book Appointment
post('/api/v1/public/book', 'middleware/public_booking.php');

// مسار جديد لجلب حالة المواعيد
post('/api/v1/public/my-appointments', 'middleware/public_appointments.php');

// مسار التوصية الجديد
post('/api/v1/public/recommend', 'middleware/public_recommend.php');
// --- END: NEW PUBLIC ROUTES ---

any('/api/v2/endpoint', 'middleware/endpointBeta.php');

any('/404', 'middleware/404.php');

$db = null;