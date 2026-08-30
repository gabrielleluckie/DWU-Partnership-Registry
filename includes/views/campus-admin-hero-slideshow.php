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

foreach ($slots as $slot) {
    $found = null;

    foreach ($extensions as $ext) {
        $filename = $slot['stem'] . '.' . $ext;
        if (is_file($slideshowDir . DIRECTORY_SEPARATOR . $filename)) {
            $found = $filename;
            break;
        }
    }

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

$slideCount = count($slides);
?>
<section class="campus-hero-slideshow"
         data-campus-slideshow
         tabindex="0"
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

        <?php if ($slideCount > 1): ?>
            <div class="campus-hero-controls">
                <button type="button" class="campus-hero-nav" data-slide-prev aria-label="Previous partnership photo">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <div class="campus-hero-dots" role="tablist" aria-label="Choose partnership photo">
                    <?php foreach ($slides as $index => $slide): ?>
                        <button type="button"
                                class="campus-hero-dot<?= $index === 0 ? ' is-active' : '' ?>"
                                data-slide-dot="<?= (int) $index ?>"
                                role="tab"
                                aria-label="Show <?= e($slide['label']) ?>"
                                aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="campus-hero-nav" data-slide-next aria-label="Next partnership photo">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
                <button type="button"
                        class="campus-hero-pause"
                        data-slide-pause
                        aria-label="Pause slideshow"
                        aria-pressed="false">
                    <i class="bi bi-pause-fill" data-pause-icon aria-hidden="true"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>
