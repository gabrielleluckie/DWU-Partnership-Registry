<?php if ($message = flashMessage('success')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3 py-2" role="alert">
        <?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($message = flashMessage('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3 py-2" role="alert">
        <?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
