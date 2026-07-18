<?php

declare(strict_types=1);

if (!function_exists('renderSiteFooter')) {
    require_once __DIR__ . '/guard.php';
}

$pageScripts = $pageScripts ?? [];
?>
                </main>
            </div>
        </div>

        <?php renderSiteFooter(); ?>
    </div>

    <script src="<?= e(assetUrl('js/sidebar.js')) ?>"></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= e(assetUrl($script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
