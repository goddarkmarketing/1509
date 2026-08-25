<?php
declare(strict_types=1);

http_response_code(404);
$pageTitle = 'ไม่พบหน้า';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="section error-page">
    <div class="container" style="text-align:center;padding:4rem 1rem">
        <p class="error-page-code" aria-hidden="true">404</p>
        <h1>ไม่พบหน้าที่ต้องการ</h1>
        <p style="color:var(--gray-600);max-width:420px;margin:0 auto 1.5rem">หน้านี้อาจถูกลบหรือย้ายไปแล้ว ลองกลับไปเลือกคอร์สเรียนได้ครับ</p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
            <a href="<?= APP_URL ?>/public/index.php" class="btn btn-outline">หน้าแรก</a>
            <a href="<?= APP_URL ?>/public/courses.php" class="btn btn-primary">ดูคอร์สเรียน</a>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
