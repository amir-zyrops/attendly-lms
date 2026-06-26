<?php
/**
 * Attendly Academic Portal - PHP Login Screen with OTP verification flow
 */
require_once __DIR__ . '/config.php';

$users = get_table(USERS_FILE);
$step = isset($_GET['step']) ? $_GET['step'] : 'select';
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';
$selected_role = in_array($selected_role, ['admin', 'faculty', 'student', 'parent'], true) ? $selected_role : '';
$default_email = '';
if ($selected_role && isset($users[$selected_role]) && !empty($users[$selected_role]['email'])) {
    $default_email = $users[$selected_role]['email'];
}
$otp_email = isset($_SESSION['otp_email']) ? $_SESSION['otp_email'] : $default_email;
$role_label = $selected_role ? role_display_name($selected_role) : '';
$show_email_step = $step === 'email' && $selected_role;
$show_verify_step = $step === 'verify' && $selected_role && isset($_SESSION['otp_code']);
?>
<div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950 p-4 font-sans">
    <div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-12 gap-0 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="col-span-1 md:col-span-5 bg-gradient-to-br from-blue-700 via-indigo-700 to-indigo-900 p-8 md:p-12 text-white flex flex-col justify-between relative">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white via-indigo-100 to-transparent"></div>
            <div class="space-y-4 relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-2xl">fingerprint</span>
                </div>
                <div>
                    <h2 class="text-3xl font-extrabold tracking-tight">Attendly</h2>
                    <p class="text-xs text-indigo-200 mt-1 uppercase font-semibold tracking-wider">OTP Portal Verification</p>
                </div>
            </div>
            <div class="mt-12 md:mt-0 space-y-4 relative z-10">
                <blockquote class="text-xs italic text-indigo-100 leading-relaxed font-medium">
                    "Secure portal sign-in now verifies access with a one-time password sent to your role email address."
                </blockquote>
                <div class="pt-4 border-t border-white/10 flex justify-between items-center text-[10px] text-indigo-200 font-mono">
                    <span>SMTP ENV: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM</span>
                    <span>Secure Code Expiry: <?php echo (OTP_TTL_SECONDS / 60); ?> min</span>
                </div>
            </div>
        </div>

        <div class="col-span-1 md:col-span-7 p-8 md:p-12 space-y-8 flex flex-col justify-center">
            <div class="space-y-2">
                <span class="text-[10px] uppercase font-mono font-bold tracking-widest text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2.5 py-1 rounded-md">
                    Academic Single Sign-On
                </span>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Access Course Portals</h1>
                <p class="text-xs text-slate-450">Select your portal, then authenticate using an email OTP code.</p>
            </div>

            <?php if ($show_verify_step): ?>
                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">Confirm your one-time password</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Enter the code sent to <span class="font-semibold"><?php echo h($otp_email); ?></span>.</p>
                        </div>
                        <span class="text-[12px] uppercase font-bold text-blue-600 bg-blue-50 dark:bg-blue-950/40 px-3 py-1 rounded-full tracking-wider"><?php echo h($role_label); ?></span>
                    </div>
                    <form action="index.php?action=verify_otp" method="POST" class="space-y-5 text-xs">
                        <input type="hidden" name="role" value="<?php echo h($selected_role); ?>" />
                        <label class="block font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-[10px]">One-Time Password</label>
                        <input
                            type="text"
                            name="otp_code"
                            maxlength="6"
                            pattern="\d{6}"
                            required
                            placeholder="Enter 6-digit code"
                            class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-slate-900 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                        />
                        <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-slate-400">
                            <span>OTP expires in <?php echo (int) ((($_SESSION['otp_expires'] ?? time()) - time()) / 60) + 1; ?> minutes.</span>
                            <a href="index.php?page=login&step=email&role=<?php echo h($selected_role); ?>" class="text-blue-600 hover:underline">Change email</a>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-extrabold py-3 rounded-2xl">Verify and Enter Portal</button>
                    </form>
                </div>

            <?php elseif ($show_email_step): ?>
                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">Verify your email for <?php echo h($role_label); ?></h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">We will send a temporary login code to your address.</p>
                        </div>
                        <span class="text-[12px] uppercase font-bold text-blue-600 bg-blue-50 dark:bg-blue-950/40 px-3 py-1 rounded-full tracking-wider"><?php echo h($role_label); ?></span>
                    </div>
                    <form action="index.php?action=request_otp" method="POST" class="space-y-5 text-xs">
                        <input type="hidden" name="role" value="<?php echo h($selected_role); ?>" />
                        <label class="block font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-[10px]">Academic Email Address</label>
                        <input
                            type="email"
                            name="email"
                            required
                            value="<?php echo h($otp_email); ?>"
                            placeholder="e.g. you@institution.edu"
                            class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-slate-900 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                        />
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">If SMTP environment variables are configured, your code will be sent automatically.</p>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-extrabold py-3 rounded-2xl">Send Verification Code</button>
                    </form>
                    <div class="pt-4 text-[10px] text-slate-400 border-t border-slate-200 dark:border-slate-800">
                        <p>Set SMTP env values in your environment and restart PHP for real email delivery.</p>
                    </div>
                </div>

            <?php else: ?>
                <div class="space-y-3">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Choose a portal to begin secure authentication. After selecting a role, you will be prompted for an email and a one-time password.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <?php foreach (['admin', 'faculty', 'student', 'parent'] as $role_key): ?>
                        <a
                            href="index.php?page=login&step=email&role=<?php echo h($role_key); ?>"
                            class="block p-4 border border-slate-150 hover:border-blue-500 hover:bg-slate-50/50 dark:border-slate-800 dark:hover:border-blue-600 dark:hover:bg-slate-950/40 rounded-2xl text-left transition-all"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full inline-flex items-center justify-center bg-blue-50 dark:bg-blue-950/40 border border-slate-200 dark:border-slate-800 text-blue-700 dark:text-blue-300">
                                    <span class="material-symbols-outlined text-lg"><?php echo h(role_icon($role_key)); ?></span>
                                </span>
                                <div>
                                    <span class="font-extrabold text-slate-800 dark:text-white block"><?php echo h(role_display_name($role_key)); ?></span>
                                    <span class="text-[10px] text-slate-450 dark:text-slate-400 block mt-0.5"><?php echo h(role_description($role_key)); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
