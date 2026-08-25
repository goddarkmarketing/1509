<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/certificate.php';
require_once dirname(__DIR__) . '/includes/student_auth.php';

$code = trim($_GET['code'] ?? '');
$cert = $code !== '' ? getCertificateByCode($code) : null;

if (!$cert && isStudentLoggedIn()) {
    $courseId = (int) ($_GET['course_id'] ?? 0);
    $student = currentStudent();
    if ($courseId > 0) {
        $cert = getStudentCertificate((int) $student['id'], $courseId);
        if (!$cert) {
            $cert = issueCertificateIfEligible((int) $student['id'], $courseId);
        }
        if ($cert) {
            redirect('/public/certificate.php?code=' . urlencode($cert['certificate_code']));
        }
    }
}

if (!$cert) {
    http_response_code(404);
    $pageTitle = 'ไม่พบใบประกาศ';
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<section class="section"><div class="container" style="text-align:center"><p>ไม่พบใบประกาศนียบัตร</p><a href="' . APP_URL . '/public/my-courses.php" class="btn btn-primary">กลับ</a></div></section>';
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$pageTitle = 'ใบประกาศนียบัตร';
$issuedDate = date('d/m/Y', strtotime($cert['issued_at']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(getSetting('site_title')) ?></title>
    <?php require dirname(__DIR__) . '/includes/views/fonts_head.php'; ?>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=114">
    <style>
      .cert-page { padding: 2rem 1rem; background: #f3f4f6; min-height: 100vh; }
      .cert-frame {
        max-width: 800px; margin: 0 auto; background: #fff;
        border: 8px solid #c41e24; padding: 3rem 2rem; text-align: center;
        box-shadow: 0 20px 50px rgba(0,0,0,.08);
      }
      .cert-frame h1 { font-size: 2rem; color: #c41e24; margin: 0 0 .5rem; }
      .cert-frame .cert-sub { color: #6b7280; margin-bottom: 2rem; }
      .cert-name { font-size: 1.75rem; font-weight: 700; margin: 1rem 0; color: #111; }
      .cert-course { font-size: 1.2rem; color: #374151; margin-bottom: 2rem; }
      .cert-code { font-size: .85rem; color: #9ca3af; margin-top: 2rem; }
      .cert-actions { margin-top: 1.5rem; display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
      @media print {
        .cert-actions, .no-print { display: none !important; }
        .cert-page { background: #fff; padding: 0; }
      }
    </style>
</head>
<body>
<div class="cert-page">
    <div class="cert-frame">
        <p class="cert-sub"><?= e(getSetting('site_title')) ?></p>
        <h1>ใบประกาศนียบัตร</h1>
        <p>ขอมอบให้แก่</p>
        <p class="cert-name"><?= e($cert['full_name']) ?></p>
        <p>ที่ได้เรียนจบคอร์ส</p>
        <p class="cert-course"><?= e($cert['course_title']) ?></p>
        <p>เมื่อวันที่ <?= e($issuedDate) ?></p>
        <p class="cert-code">รหัสตรวจสอบ: <?= e($cert['certificate_code']) ?></p>
    </div>
    <div class="cert-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">พิมพ์ / บันทึก PDF</button>
        <a href="<?= APP_URL ?>/public/my-courses.php" class="btn btn-outline">กลับคอร์สของฉัน</a>
    </div>
</div>
</body>
</html>
