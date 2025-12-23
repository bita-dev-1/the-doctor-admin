<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> -
        <?= htmlspecialchars($doctor['specialty_fr']) ?></title>
    <meta name="description"
        content="Prenez rendez-vous avec Dr. <?= htmlspecialchars($doctor['last_name']) ?>, <?= htmlspecialchars($doctor['specialty_fr']) ?> à <?= htmlspecialchars($doctor['commune']) ?>.">
    <meta property="og:image" content="<?= $doctor['image1'] ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" type="image/x-icon" href="<?= SITE_URI ?>assets/images/favicon.ico">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS File (Updated with versioning to force reload) -->
    <link rel="stylesheet" href="<?= SITE_URI ?>assets/css/landing-page.css?v=<?= time() ?>">
</head>