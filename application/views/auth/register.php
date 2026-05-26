<section class="auth-card auth-card--single">
    <div class="auth-logo">
        <div class="auth-logo__icon">{ }</div>
    </div>
    <h1>Create your account</h1>
    <?php echo form_open('register', array('class' => 'auth-form')); ?>
        <label>
            <span>Email address</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Username</span>
            <input type="text" name="username" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>
        <label>
            <span>Confirm Password</span>
            <input type="password" name="password_confirm" required>
        </label>
        <button type="submit" class="auth-submit">Register</button>
    <?php echo form_close(); ?>
    <div class="auth-divider"><span>Already have an account?</span></div>
    <p class="auth-switch"><a href="<?php echo site_url('login'); ?>">Sign in</a></p>
</section>
