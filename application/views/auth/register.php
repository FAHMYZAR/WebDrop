<section class="w-full max-w-md border border-[#e0e0e0] bg-white p-8">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center border border-[#0f62fe] bg-[#0f62fe] text-white text-lg font-bold">WD</div>
        <h1 class="text-xl font-semibold text-[#161616]">Create your account</h1>
        <p class="mt-1 text-sm text-[#525252]">Mulai kelola project statis kamu.</p>
    </div>

    <?php echo form_open('register'); ?>
        <div class="space-y-4">
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-[#161616]">Email address</span>
                <input type="email" name="email" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="you@example.com">
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-[#161616]">Username</span>
                <input type="text" name="username" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="username">
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-[#161616]">Password</span>
                <input type="password" name="password" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-[#161616]">Confirm Password</span>
                <input type="password" name="password_confirm" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
            </label>
        </div>

        <button type="submit" class="mt-6 w-full border border-[#0f62fe] bg-[#0f62fe] px-4 py-3 text-sm font-semibold text-white hover:bg-[#0353e9]">Register</button>
    <?php echo form_close(); ?>

    <div class="mt-6 border-t border-[#e0e0e0] pt-6 text-center">
        <p class="text-sm text-[#525252]">Sudah punya akun? <a href="<?php echo site_url('login'); ?>" class="font-semibold text-[#0f62fe] hover:underline">Sign in</a></p>
    </div>
</section>
