<?php

/**
 * Campus Admin full-width partnership photo slideshow.
 *
 * Replace the placeholder slots by adding images named:
 *   assets/images/slideshow/slide-1.jpg
 *   assets/images/slideshow/slide-2.jpg
 *   assets/images/slideshow/slide-3.jpg
 *   assets/images/slideshow/slide-4.jpg
 *
 * .png, .webp, and .gif are also accepted. Extra files in that folder are
 * appended after the four slots.
 */

$slideshowDirRel = 'assets/images/slideshow';
$slideshowDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $slideshowDirRel);
$extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

$slots = [
    ['stem' => 'slide-1', 'label' => 'Partnership photo 1'],
    ['stem' => 'slide-2', 'label' => 'Partnership photo 2'],
    ['stem' => 'slide-3', 'label' => 'Partnership photo 3'],
    ['stem' => 'slide-4', 'label' => 'Partnership photo 4'],
];

$slides = [];
$usedFiles = [];

$findSlotFile = static function (string $dir, string $stem, array $extensions): ?string {
    foreach ($extensions as $ext) {
        $filename = $stem . '.' . $ext;
        if (is_file($dir . DIRECTORY_SEPARATOR . $filename)) {
            return $filename;
        }
    }

    // Windows often hides ".jpg", so "slide-1.jpg" is saved as "slide-1.jpg.jpg".
    $matches = glob($dir . DIRECTORY_SEPARATOR . $stem . '.*') ?: [];
    foreach ($matches as $path) {
        if (is_file($path) && preg_match('/\.(jpe?g|png|webp|gif)$/i', $path) === 1) {
            return basename($path);
        }
    }

    return null;
};

foreach ($slots as $slot) {
    $found = $findSlotFile($slideshowDir, $slot['stem'], $extensions);

    if ($found !== null) {
        $usedFiles[] = $found;
        $slides[] = [
            'src'   => assetUrl($slideshowDirRel . '/' . $found),
            'alt'   => $slot['label'],
            'slot'  => $slot['stem'],
            'label' => $slot['label'],
            'hint'  => $slideshowDirRel . '/' . $slot['stem'] . '.jpg',
        ];
    } else {
        $slides[] = [
            'src'   => null,
            'alt'   => $slot['label'],
            'slot'  => $slot['stem'],
            'label' => $slot['label'],
            'hint'  => $slideshowDirRel . '/' . $slot['stem'] . '.jpg',
        ];
    }
}

if (is_dir($slideshowDir)) {
    $extras = scandir($slideshowDir) ?: [];
    sort($extras, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($extras as $file) {
        if ($file === '.' || $file === '..' || in_array($file, $usedFiles, true)) {
            continue;
        }

        if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $file)) {
            continue;
        }

        $label = ucwords(str_replace(['-', '_'], ' ', (string) pathinfo($file, PATHINFO_FILENAME)));
        $slides[] = [
            'src'   => assetUrl($slideshowDirRel . '/' . $file),
            'alt'   => $label,
            'slot'  => pathinfo($file, PATHINFO_FILENAME),
            'label' => $label,
            'hint'  => $slideshowDirRel . '/' . $file,
        ];
    }
}
?>
<section class="campus-hero-slideshow"
         data-campus-slideshow
         aria-roledescription="carousel"
         aria-label="Partnership photo slideshow">
    <div class="campus-hero-photos">
        <?php foreach ($slides as $index => $slide): ?>
            <figure class="campus-hero-slide<?= $index === 0 ? ' is-active' : '' ?><?= $slide['src'] === null ? ' is-placeholder' : '' ?>"
                    data-slide
                    aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                <?php if ($slide['src'] !== null): ?>
                    <img src="<?= e($slide['src']) ?>"
                         alt="<?= e($slide['alt']) ?>"
                         <?= $index === 0 ? '' : 'loading="lazy"' ?>>
                <?php else: ?>
                    <div class="campus-hero-placeholder">
                        <i class="bi bi-image" aria-hidden="true"></i>
                        <p class="campus-hero-placeholder-title"><?= e($slide['label']) ?></p>
                        <p class="campus-hero-placeholder-hint">
                            Add your photo as<br>
                            <code><?= e($slide['hint']) ?></code>
                        </p>
                    </div>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>
</section>
