<section class="settings-wrap">
    <h1>Profile Settings</h1>

    <div class="settings-card settings-card--photo">
        <div class="avatar-shell">
            <div class="avatar-circle"><?php echo strtoupper(substr($profile_user->username, 0, 1)); ?></div>
            <div>
                <button type="button" class="secondary-button" disabled>Change Photo</button>
                <p>JPG, GIF or PNG. Max size of 2MB.</p>
            </div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card__head">
            <h2>Account Information</h2>
        </div>
        <?php echo form_open('profile', array('class' => 'settings-form')); ?>
            <div class="settings-grid">
                <label>
                    <span>Username</span>
                    <input type="text" value="<?php echo html_escape($profile_user->username); ?>" readonly>
                </label>
                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="<?php echo html_escape($profile_user->email); ?>" required>
                </label>
            </div>
            <div class="settings-actions">
                <button type="submit" class="primary-link primary-link--button">Save Changes</button>
            </div>
        <?php echo form_close(); ?>
    </div>

    <div class="settings-card">
        <div class="settings-card__head">
            <h2>Security</h2>
        </div>
        <?php echo form_open('profile/password', array('class' => 'settings-form')); ?>
            <label class="settings-single">
                <span>Current Password</span>
                <input type="password" name="current_password" required>
            </label>
            <div class="settings-grid">
                <label>
                    <span>New Password</span>
                    <input type="password" name="new_password" required>
                </label>
                <label>
                    <span>Confirm New Password</span>
                    <input type="password" name="confirm_password" required>
                </label>
            </div>
            <div class="settings-actions">
                <button type="submit" class="secondary-button">Change Password</button>
            </div>
        <?php echo form_close(); ?>
    </div>
</section>
