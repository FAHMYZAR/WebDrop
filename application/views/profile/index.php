<section class="space-y-6">
    <div class="border border-[#e0e0e0] bg-white px-6 py-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#0f62fe]">Profile</p>
                <h1 class="mt-2 text-2xl font-semibold text-[#161616]">Settings</h1>
            </div>
        </div>
    </div>

    <div class="border border-[#e0e0e0] bg-white px-6 py-5">
        <div class="flex items-center gap-6">
            <div class="flex h-16 w-16 items-center justify-center border border-[#0f62fe] bg-[#0f62fe] text-2xl font-bold text-white">
                <?php echo strtoupper(substr($profile_user->username, 0, 1)); ?>
            </div>
            <div>
                <p class="text-sm text-[#525252]">Foto profil belum tersedia.</p>
                <p class="mt-1 text-xs text-[#8d8d8d]">JPG, GIF atau PNG. Maksimal 2MB.</p>
            </div>
        </div>
    </div>

    <div class="border border-[#e0e0e0] bg-white">
        <div class="border-b border-[#e0e0e0] px-6 py-4">
            <h2 class="text-base font-semibold text-[#161616]">Account Information</h2>
        </div>
        <?php echo form_open('profile'); ?>
            <div class="space-y-4 px-6 py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-[#161616]">Username</span>
                        <input type="text" value="<?php echo html_escape($profile_user->username); ?>" readonly class="w-full border border-[#e0e0e0] bg-[#f4f4f4] px-4 py-3 text-sm text-[#8d8d8d]">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-[#161616]">Email address</span>
                        <input type="email" name="email" value="<?php echo html_escape($profile_user->email); ?>" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]">
                    </label>
                </div>
            </div>
            <div class="border-t border-[#e0e0e0] px-6 py-4">
                <button type="submit" class="border border-[#0f62fe] bg-[#0f62fe] px-6 py-2.5 text-sm font-semibold text-white hover:bg-[#0353e9]">Save Changes</button>
            </div>
        <?php echo form_close(); ?>
    </div>

    <div class="border border-[#e0e0e0] bg-white">
        <div class="border-b border-[#e0e0e0] px-6 py-4">
            <h2 class="text-base font-semibold text-[#161616]">Security</h2>
            <p class="mt-1 text-sm text-[#525252]">Ubah password akun kamu.</p>
        </div>
        <?php echo form_open('profile/password'); ?>
            <div class="space-y-4 px-6 py-5">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-[#161616]">Current Password</span>
                    <input type="password" name="current_password" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-[#161616]">New Password</span>
                        <input type="password" name="new_password" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-[#161616]">Confirm New Password</span>
                        <input type="password" name="confirm_password" required class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="••••••••">
                    </label>
                </div>
            </div>
            <div class="border-t border-[#e0e0e0] px-6 py-4">
                <button type="submit" class="border border-[#e0e0e0] bg-white px-6 py-2.5 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Change Password</button>
            </div>
        <?php echo form_close(); ?>
    </div>
</section>
