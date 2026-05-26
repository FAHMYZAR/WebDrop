<section class="auth-card auth-card--single">
    <div class="auth-logo">
        <div class="auth-logo__icon">{ }</div>
    </div>
    <h1>Sign in to WebDrop</h1>
    <?php echo form_open('login', array('class' => 'auth-form')); ?>
        <label>
            <span>Username</span>
            <input type="text" name="username" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="auth-submit">Sign in</button>
    <?php echo form_close(); ?>
    <div class="auth-divider"><span>Don't have an account?</span></div>
    <p class="auth-switch"><a href="<?php echo site_url('register'); ?>">Register now</a></p>
</section>
