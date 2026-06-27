<?php
/**
 * Attendly Academic Portal - PHP Login Screen with OTP verification flow
 */
require_once __DIR__ . '/config.php';

$users = get_table(USERS_FILE);
$students = get_table(STUDENTS_FILE);
$step = isset($_GET['step']) ? $_GET['step'] : 'select';
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';
$selected_role = in_array($selected_role, ['admin', 'faculty', 'student', 'parent'], true) ? $selected_role : '';
$default_email = '';
if ($selected_role === 'student') {
    foreach ($students as $student) {
        if (!empty($student['email'])) {
            $default_email = $student['email'];
            break;
        }
    }
}
if ($default_email === '' && $selected_role && isset($users[$selected_role]) && !empty($users[$selected_role]['email'])) {
    $default_email = $users[$selected_role]['email'];
}
$otp_email = isset($_SESSION['otp_email']) ? $_SESSION['otp_email'] : $default_email;
$selected_student_roll = isset($_SESSION['otp_student_roll']) ? $_SESSION['otp_student_roll'] : '';
$selected_student = $selected_role === 'parent' && $selected_student_roll ? find_student_by_roll_no($selected_student_roll) : null;
$role_label = $selected_role ? role_display_name($selected_role) : '';
$show_email_step = $step === 'email' && $selected_role;
$show_verify_step = $step === 'verify' && $selected_role && isset($_SESSION['otp_code']);
$parent_has_students = $selected_role === 'parent' && !empty($students);
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
                    "Secure portal sign-in verifies access with a one-time password sent to your registered email."
                </blockquote>
                <div class="pt-4 border-t border-white/10 text-[10px] text-indigo-200 font-mono">
                    <span>Verification Code Expires: <?php echo (OTP_TTL_SECONDS / 60); ?> minutes</span>
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
                        <?php if ($selected_role === 'parent' && $selected_student): ?>
                            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 text-[11px] text-slate-600 dark:text-slate-300">
                                <p class="font-semibold text-slate-900 dark:text-white">Parent portal verification for:</p>
                                <p><?php echo h(display_name($selected_student)); ?> <span class="font-mono text-slate-500">(<?php echo h($selected_student['rollNo']); ?>)</span></p>
                                <p class="mt-2 text-[10px] text-slate-500">OTP will be sent to <strong><?php echo h($otp_email); ?></strong>.</p>
                            </div>
                        <?php endif; ?>
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
                            <span>OTP expires in <span id="otp-timer"><?php echo (int) ((($_SESSION['otp_expires'] ?? time()) - time()) / 60) + 1; ?></span> minutes.</span>
                            <a href="index.php?page=login&step=email&role=<?php echo h($selected_role); ?>" class="text-blue-600 hover:underline">Change email</a>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-extrabold py-3 rounded-2xl">Verify and Enter Portal</button>
                    </form>
                    <form action="index.php?action=resend_otp" method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800">
                        <input type="hidden" name="role" value="<?php echo h($selected_role); ?>" />
                        <button
                            type="submit"
                            id="resend-btn"
                            class="w-full text-slate-500 dark:text-slate-500 hover:text-slate-500 dark:hover:text-slate-500 font-semibold py-2 px-3 text-xs uppercase tracking-wider transition-colors border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-100 dark:bg-slate-800 cursor-not-allowed opacity-50"
                        >
                            Resend Code (expires in <span id="resend-timer"><?php echo (int) ((($_SESSION['otp_expires'] ?? time()) - time()) / 60) + 1; ?></span>m)
                        </button>
                    </form>
                    <script>
                        const expiresAt = <?php echo ($_SESSION['otp_expires'] ?? time()); ?>;
                        const resendBtn = document.getElementById('resend-btn');
                        const resendTimer = document.getElementById('resend-timer');
                        const timerSpan = document.getElementById('otp-timer');

                        function updateTimer() {
                            const now = Math.floor(Date.now() / 1000);
                            const remaining = expiresAt - now;

                            if (remaining <= 0) {
                                resendBtn.disabled = false;
                                resendBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-slate-100', 'dark:bg-slate-800', 'text-slate-500', 'dark:text-slate-500', 'hover:text-slate-500', 'dark:hover:text-slate-500');
                                resendBtn.classList.add('opacity-100', 'cursor-pointer', 'bg-blue-50', 'dark:bg-blue-950/40', 'text-blue-600', 'dark:text-blue-300', 'hover:text-blue-700', 'dark:hover:text-blue-200', 'hover:bg-blue-100', 'dark:hover:bg-blue-950', 'border-blue-300', 'dark:border-blue-700');
                                resendBtn.innerHTML = 'Resend Code Now';
                                resendTimer.textContent = '0';
                                timerSpan.textContent = '0';
                            } else {
                                const minutes = Math.floor(remaining / 60);
                                const seconds = remaining % 60;
                                resendTimer.textContent = minutes > 0 ? minutes : '0';
                                timerSpan.textContent = minutes + (seconds > 0 ? '.' + Math.floor(seconds / 10) : '');
                            }
                        }

                        updateTimer();
                        setInterval(updateTimer, 1000);
                    </script>
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
                        <?php if ($selected_role === 'parent'): ?>
                            <?php if ($parent_has_students): ?>
                                <label class="block font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-[10px]">Select Student Account</label>
                                <select name="student_roll" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-slate-900 dark:text-slate-100 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Choose linked student</option>
                                    <?php foreach ($students as $student): ?>
                                        <?php $roll = isset($student['rollNo']) ? $student['rollNo'] : ''; ?>
                                        <?php if ($roll === '') continue; ?>
                                        <option value="<?php echo h($roll); ?>" <?php echo $selected_student_roll === $roll ? 'selected' : ''; ?>>
                                            <?php echo h(display_name($student)); ?> (<?php echo h($roll); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">The chosen student’s registered email will receive the verification code.</p>
                            <?php else: ?>
                                <div class="p-5 rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-sm text-slate-500">
                                    No student records are available yet. Add a student record first before using the parent portal.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-[10px]">Academic Email Address</label>
                            <input
                                type="email"
                                name="email"
                                required
                                value="<?php echo h($otp_email); ?>"
                                placeholder="e.g. you@institution.edu"
                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-slate-900 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                            />
                            <p class="text-[10px] text-slate-500 dark:text-slate-400">Your verification code will be sent to your registered email address.</p>
                        <?php endif; ?>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-extrabold py-3 rounded-2xl">Send Verification Code</button>
                    </form>
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
