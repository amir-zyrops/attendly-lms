<?php
/**
 * Attendly Academic Portal - PHP Settings & Profile management views
 */

require_once __DIR__ . '/config.php';

$active_settings = isset($_GET['sub']) ? $_GET['sub'] : 'profile'; // profile vs preferences

$push_alerts = isset($_SESSION['push_alerts']) ? $_SESSION['push_alerts'] : true;
$email_alerts = isset($_SESSION['email_alerts']) ? $_SESSION['email_alerts'] : true;
$dark_theme = isset($_SESSION['dark_theme']) ? $_SESSION['dark_theme'] : false;
?>
<div class="space-y-6 animate-fade-in">
    
    <!-- Header with switches between Profile Configuration vs Preferences -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Portal Dashboard Settings</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Maintain session authorizations profile information and browser styles layout settings.</p>
            </div>

            <!-- Tabs Switcher -->
            <div class="grid grid-cols-2 gap-1 bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200 dark:border-slate-850 select-none">
                <a
                    href="index.php?page=settings&sub=profile"
                    class="py-1.5 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_settings === 'profile' ? 'bg-slate-905 text-white dark:bg-slate-800' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    Profile Settings
                </a>
                <a
                    href="index.php?page=settings&sub=preferences"
                    class="py-1.5 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_settings === 'preferences' ? 'bg-slate-905 text-white dark:bg-slate-800' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    System Preferences
                </a>
            </div>
        </div>
    </div>

    <?php if ($active_settings === 'profile'): ?>
        <!-- Render Profile Form Fields details -->
        <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
            <form action="index.php?action=update_profile" method="POST" class="space-y-6">
                
                <!-- Avatar block info -->
                <div class="pb-3 border-b border-slate-100 dark:border-slate-850 flex items-center gap-4">
                    <?php echo avatar_markup($current_user, 'w-16 h-16 rounded-full border-2 border-indigo-550 shadow-md'); ?>
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-base leading-tight"><?php echo h(display_name($current_user)); ?></h3>
                        <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded-md mt-1 block w-max">
                            <?php echo h(strtoupper(role_display_name($current_user['role']))); ?>
                        </span>
                    </div>
                </div>

                <!-- Fields grid layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-slate-700 dark:text-slate-200">
                    
                    <div class="space-y-1.5">
                        <label class="block text-slate-550 font-bold uppercase tracking-wide">Full Display Name</label>
                        <input
                            type="text"
                            name="name"
                            value="<?php echo h(isset($current_user['name']) ? $current_user['name'] : ''); ?>"
                            placeholder="Enter display name"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xl px-4 py-2.5 font-medium text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-slate-550 font-bold uppercase tracking-wide">Primary Academic Contact</label>
                        <input
                            type="email"
                            name="email"
                            value="<?php echo h(isset($current_user['email']) ? $current_user['email'] : ''); ?>"
                            placeholder="Enter institutional email"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-255 dark:border-slate-800 rounded-xl px-4 py-2.5 font-medium text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-slate-550 font-bold uppercase tracking-wide">Institutional Designation</label>
                        <input
                            type="text"
                            name="designation"
                            value="<?php echo h(isset($current_user['designation']) ? $current_user['designation'] : ''); ?>"
                            placeholder="Enter designation"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xl px-4 py-2.5 font-medium text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-slate-550 font-bold uppercase tracking-wide">Parent Department</label>
                        <input
                            type="text"
                            name="department"
                            value="<?php echo h(isset($current_user['department']) ? $current_user['department'] : ''); ?>"
                            placeholder="Enter department"
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xl px-4 py-2.5 font-medium text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:outline-none"
                        />
                    </div>

                </div>

                <!-- Textarea statement -->
                <div class="space-y-1.5 text-xs text-slate-700 dark:text-slate-100">
                    <label class="block text-slate-550 font-bold uppercase tracking-wide">Biography Statement Note</label>
                    <textarea
                        name="bio"
                        rows="3"
                        placeholder="Enter profile bio"
                        class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xl px-4 py-2.5 font-medium text-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:outline-none resize-none"
                    ><?php echo h(isset($current_user['bio']) ? $current_user['bio'] : ''); ?></textarea>
                </div>

                <!-- Submit trigger -->
                <div class="pt-2 flex justify-end select-none">
                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs py-2.5 px-6 rounded-xl cursor-pointer shadow-md shadow-blue-550/10 transition-colors"
                    >
                        Save Profile Details
                    </button>
                </div>

            </form>
        </div>
    <?php else: ?>
        <!-- Render Toggles view inside System Preferences -->
        <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
            <div class="pb-2 border-b border-slate-50 dark:border-slate-850">
                <h3 class="font-bold text-slate-855 dark:text-white text-sm">System Operations Preferences</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure how notifications and style components load inside this browser canvas workspace.</p>
            </div>

            <div class="space-y-4 font-sans text-xs">
                
                <!-- Toggle Push Alerts -->
                <div class="flex justify-between items-center py-2.5 border-b border-slate-50 dark:border-slate-850">
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100">Portal Push Notifications</h4>
                        <p class="text-[11px] text-slate-450 mt-0.5">Toggle live visual alerts when leave applications or marksheets shift state.</p>
                    </div>
                    <a
                        href="index.php?action=toggle_pref&pref=push"
                        class="w-12 h-6.5 rounded-full p-1 transition-colors relative flex items-center <?php echo $push_alerts ? 'bg-blue-600 justify-end' : 'bg-slate-200 dark:bg-slate-800 justify-start'; ?>"
                    >
                        <span class="block w-4.5 h-4.5 bg-white rounded-full shadow"></span>
                    </a>
                </div>

                <!-- Toggle Email alerts -->
                <div class="flex justify-between items-center py-2.5 border-b border-slate-50 dark:border-slate-850">
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100">Analytical Daily Email Rollup</h4>
                        <p class="text-[11px] text-slate-450 mt-0.5">Deliver daily summaries and compliance statistics reports directly to the linked coordinator mailboxes.</p>
                    </div>
                    <a
                        href="index.php?action=toggle_pref&pref=email"
                        class="w-12 h-6.5 rounded-full p-1 transition-colors relative flex items-center <?php echo $email_alerts ? 'bg-blue-600 justify-end' : 'bg-slate-200 dark:bg-slate-800 justify-start'; ?>"
                    >
                        <span class="block w-4.5 h-4.5 bg-white rounded-full shadow"></span>
                    </a>
                </div>

                <!-- High contrast active dark skins -->
                <div class="flex justify-between items-center py-2.5">
                    <div>
                        <h4 class="font-extrabold text-slate-800 dark:text-slate-100">High Contrast Dark Mode skin</h4>
                        <p class="text-[11px] text-slate-450 mt-0.5">Alters default canvases themes to Comfort Slate dark sheets.</p>
                    </div>
                    <a
                        href="index.php?action=toggle_pref&pref=dark"
                        class="w-12 h-6.5 rounded-full p-1 transition-colors relative flex items-center <?php echo $dark_theme ? 'bg-blue-600 justify-end' : 'bg-slate-200 dark:bg-slate-800 justify-start'; ?>"
                    >
                        <span class="block w-4.5 h-4.5 bg-white rounded-full shadow"></span>
                    </a>
                </div>

            </div>
        </div>
    <?php endif; ?>

</div>
