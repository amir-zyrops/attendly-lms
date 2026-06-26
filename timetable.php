<?php
/**
 * Attendly Academic Portal - PHP Timetables & Leave Manager views
 */

require_once __DIR__ . '/config.php';

$active_sub = isset($_GET['sub']) ? $_GET['sub'] : 'timetable'; // timetable vs leave
$leaves = get_table(LEAVES_FILE);
$courses = get_table(COURSES_FILE);
$role = $current_user['role'];
?>
<div class="space-y-6 animate-fade-in">
    
    <!-- Navigation Tabs (Timetable vs Leave requests) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Academic Schedulers & Leaves Desk</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Review class schedules, check section halls, and manage pending session leaves.</p>
            </div>

            <div class="grid grid-cols-2 gap-1 bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200 dark:border-slate-850 select-none">
                <a
                    href="index.php?page=timetable&sub=timetable"
                    class="py-2 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_sub === 'timetable' ? 'bg-slate-900 text-white dark:bg-slate-850' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    Weekly Schedule
                </a>
                <a
                    href="index.php?page=timetable&sub=leave"
                    class="py-2 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_sub === 'leave' ? 'bg-slate-900 text-white dark:bg-slate-850' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    Leave Requests Desk
                </a>
            </div>
        </div>
    </div>

    <?php if ($active_sub === 'timetable'): ?>
        <!-- Render Weekly Schedule block -->
        <div class="space-y-4">
            <h3 class="font-bold text-slate-850 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-indigo-500 text-sm">calendar_view_week</span>
                Target Slot Assignments
            </h3>

            <!-- Grid or list structure of slots -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if (empty($courses)): ?>
                <div class="md:col-span-2 lg:col-span-3">
                    <?php empty_state('No schedule records', 'Course schedules will appear here after courses are added.', 'calendar_month'); ?>
                </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-extrabold text-blue-700 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded font-mono uppercase"><?php echo h(display_field(isset($course['code']) ? $course['code'] : '', 'Course')); ?></span>
                            <span class="text-slate-400 font-mono text-[10px]"><?php echo h(display_field(isset($course['schedule']) ? $course['schedule'] : '', 'Schedule pending')); ?></span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="font-extrabold text-slate-900 dark:text-white text-sm"><?php echo h(display_field(isset($course['title']) ? $course['title'] : '', 'Untitled course')); ?></h4>
                            <p class="text-slate-500 text-[11px] font-medium">Assigned: <?php echo h(display_field(isset($course['rooms']) ? $course['rooms'] : '', 'Not configured')); ?></p>
                        </div>
                        <div class="pt-2 border-t border-slate-50 dark:border-slate-850 flex justify-between items-center text-[10px] text-slate-450">
                            <span><?php echo h(display_field(isset($course['coordinator']) ? $course['coordinator'] : '', 'Coordinator pending')); ?></span>
                            <span class="text-green-600 bg-green-50 dark:bg-green-950/30 px-2 py-0.5 rounded font-bold uppercase tracking-wider">Scheduled</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- Render Leave Requests flow (Forms if Student, Logs of leaves) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Submit Leave Form (Col 5 - Visible to Student role only) -->
            <?php if ($role === 'student'): ?>
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                <div class="pb-3 border-b border-slate-100 dark:border-slate-850">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">File Leave Application</h3>
                    <p class="text-xs text-slate-450">Transmit details directly for faculty coordinator review.</p>
                </div>

                <form action="index.php?action=add_leave" method="POST" class="space-y-4 text-xs">
                    
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-605">General Category Type</label>
                        <select name="type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none font-medium">
                            <option value="Medical Sick Leave">Medical Sick Leave</option>
                            <option value="Bereavement Obligation">Bereavement Obligation</option>
                            <option value="Institutional Attendance Audit">Institutional Duty / Event</option>
                            <option value="General Family Affairs">Personal Family Urgency</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-605">Applicable Target Date</label>
                        <input
                            type="date"
                            name="date"
                            required
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none font-medium"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-605">Detailed Statement Note</label>
                        <textarea
                            name="reason"
                            rows="4"
                            required
                            placeholder="Please provide explicit details of medical advice or bereavement schedules."
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none font-medium resize-none"
                        ></textarea>
                    </div>

                    <div class="pt-2 select-none">
                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-extrabold py-3 px-4 rounded-xl shadow-md cursor-pointer transition-colors"
                        >
                            Transmit Leave Files
                        </button>
                    </div>

                </form>
            </div>
            <?php endif; ?>

            <!-- Leaves List (Col 7 or Col 12 depending on view role) -->
            <div class="<?php echo ($role === 'student') ? 'lg:col-span-7' : 'lg:col-span-12'; ?> space-y-4">
                
                <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="pb-3 border-b border-slate-100 dark:border-slate-850 flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-bold text-slate-850 dark:text-white text-sm">Active Leaves Registry</h3>
                            <p class="text-xs text-slate-500 font-medium font-sans">History of leave filings linked to institutional identity.</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-400">pending_actions</span>
                    </div>

                    <div class="space-y-4">
                        <?php if (empty($leaves)): ?>
                        <div class="p-6 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-100 dark:border-slate-850 text-center">
                            <p class="text-xs text-slate-400 italic">No leave requests have been submitted yet.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($leaves as $lv): 
                            $badge = 'text-amber-700 bg-amber-50 border-amber-200/40 dark:bg-amber-950/40';
                            if ($lv['status'] === 'Approved') {
                                $badge = 'text-green-700 bg-green-50 border-green-200/40 dark:bg-green-950/40';
                            } else if ($lv['status'] === 'Rejected') {
                                $badge = 'text-red-700 bg-red-50 border-red-200/40 dark:bg-red-950/40';
                            }
                        ?>
                        <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-100 dark:border-slate-850 space-y-3">
                            <div class="flex justify-between items-baseline flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] uppercase font-mono font-bold tracking-wider text-blue-700 bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded border border-blue-100/10">
                                        <?php echo htmlspecialchars($lv['type']); ?>
                                    </span>
                                    <span class="text-slate-350 text-[11px]">|</span>
                                    <span class="text-slate-500 font-bold font-mono text-[10px]"><?php echo $lv['date']; ?></span>
                                </div>
                                <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full border <?php echo $badge; ?>">
                                    <?php echo htmlspecialchars($lv['status']); ?>
                                </span>
                            </div>

                            <p class="text-slate-650 dark:text-slate-300 text-xs font-semibold leading-relaxed">
                                "<?php echo htmlspecialchars($lv['reason']); ?>"
                            </p>

                            <div class="pt-2 border-t border-slate-100 dark:border-slate-850 flex items-center gap-2 text-[10px] text-slate-400">
                                <?php echo avatar_markup(['name' => isset($lv['studentName']) ? $lv['studentName'] : 'Student', 'avatar' => isset($lv['studentAvatar']) ? $lv['studentAvatar'] : '', 'role' => 'student'], 'w-5 h-5 rounded-full shrink-0'); ?>
                                <span>Filed By: <strong class="text-slate-705 dark:text-slate-300"><?php echo h(display_field(isset($lv['studentName']) ? $lv['studentName'] : '', 'Student')); ?> (<?php echo h(display_field(isset($lv['rollNo']) ? $lv['rollNo'] : '', 'No roll ID')); ?>)</strong></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    <?php endif; ?>

</div>
