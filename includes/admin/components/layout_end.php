            </div>
        </div>
    </div>
</div>
<script src="<?= asset('js/admin.js') ?>?v=6"></script>
<?php
if (!function_exists('adminGuideTours')) {
    require_once dirname(__DIR__, 2) . '/admin_guide_tours.php';
}
if (!isset($GLOBALS['__admin_guide_tours_json'])) {
    $GLOBALS['__admin_guide_tours_json'] = json_encode(adminGuideTours(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
<link rel="stylesheet" href="<?= adminAsset('css/admin-guide-tour.css') ?>">
<script>window.__ADMIN_GUIDE_TOURS__ = <?= $GLOBALS['__admin_guide_tours_json'] ?: '[]' ?>;</script>
<script src="<?= adminAsset('js/admin-guide-tour.js') ?>"></script>
<?php
if (!function_exists('feedbackRenderClientAssets')) {
    require_once dirname(__DIR__, 2) . '/feedback.php';
}
feedbackRenderClientAssets();
?>
</body>
</html>
