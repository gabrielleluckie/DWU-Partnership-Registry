<?php

/**
 * Campus Admin identity bar — name, photo, and photo change at the top of the dashboard.
 *
 * Expected: $user (array)
 */

$photoInputId = 'campusAdminProfilePhotoInput';
$currentUri = (string) ($_SERVER['REQUEST_URI'] ?? routePath('dashboard/campus-admin'));
?>
<div class="campus-admin-profile-bar campus-admin-header-profile">
    <div class="campus-admin-profile-identity">
        <form method="post"
              action="<?= e(profilePhotoUploadAction()) ?>"
              enctype="multipart/form-data"
              class="campus-admin-photo-form mb-0">
            <input type="hidden" name="redirect_to" value="<?= e($currentUri) ?>">
            <input type="file"
                   id="<?= e($photoInputId) ?>"
                   name="profile_photo"
                   class="d-none"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   data-profile-photo-input>
            <button type="button"
                    class="campus-admin-photo-trigger"
                    data-profile-photo-trigger="<?= e($photoInputId) ?>"
                    title="Change profile photo"
                    aria-label="Change profile photo">
                <img class="campus-admin-profile-photo"
                     src="<?= e($user['avatar']) ?>"
                     alt="<?= e($user['name']) ?>"
                     width="72"
                     height="72">
                <span class="campus-admin-photo-edit" aria-hidden="true">
                    <i class="bi bi-camera-fill"></i>
                </span>
            </button>
        </form>

        <div class="campus-admin-profile-copy">
            <p class="campus-admin-profile-kicker mb-0">Signed in as</p>
            <h2 class="campus-admin-profile-name h5 mb-0"><?= e($user['name']) ?></h2>
            <p class="campus-admin-profile-meta mb-0">
                <?= e($user['role']) ?>
                <?php if (!empty($user['campus'])): ?>
                    <span aria-hidden="true"> · </span><?= e($user['campus']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <a href="<?= e(logoutRoute()) ?>"
       class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2 px-3 app-dashboard-logout-btn">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        <span>Logout</span>
    </a>
</div>
