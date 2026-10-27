            </div>
        </div>
    </div>
</div>
<script src="<?= asset('js/admin.js') ?>?v=6"></script>
<?php
if (!function_exists('feedbackRenderClientAssets')) {
    require_once dirname(__DIR__, 2) . '/feedback.php';
}
feedbackRenderClientAssets();
?>
</body>
</html>
