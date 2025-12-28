<!DOCTYPE html>
<html lang="<?= $lang_code ?>" dir="<?= $dir ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> -
        <?= htmlspecialchars($lang_code == 'ar' && !empty($doctor['specialty_ar']) ? $doctor['specialty_ar'] : $doctor['specialty_fr']) ?>
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- English/French Font: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Arabic Font: Cairo (Best for UI) -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="shortcut icon" type="image/x-icon" href="<?= SITE_URI ?>assets/images/favicon.ico">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS File -->
    <link rel="stylesheet" href="<?= SITE_URI ?>assets/css/landing-page.css?v=<?= time() ?>">
</head>