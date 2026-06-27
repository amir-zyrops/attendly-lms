<?php
/**
 * Attendly Academic Portal - PHP Timetables & Leave Manager views
 */

require_once __DIR__ . '/config.php';

$active_sub = isset($_GET['sub']) ? $_GET['sub'] : 'timetable'; // timetable, courses, leave
$edit_course_code = isset($_GET['edit']) ? $_GET['edit'] : '';
$leaves = get_table(LEAVES_FILE);
$courses = get_table(COURSES_FILE);
$students = get_table(STUDENTS_FILE);
$role = $current_user['role'];

$student_index = [];
foreach ($students as $student) {
    if (isset($student['rollNo'])) {
        $student_index[$student['rollNo']] = $student;
    }
}

$editing_course = null;
if ($edit_course_code !== '') {
    foreach ($courses as $course) {
        if (isset($course['code']) && $course['code'] === $edit_course_code) {
            $editing_course = $course;
            break;
        }
    }
}

function get_enrolled_count($course) {
    if (!isset($course['enrolledStudents']) || !is_array($course['enrolledStudents'])) {
        return 0;
    }
    return count($course['enrolledStudents']);
}
?>
<div class="space-y-6 animate-fade-in">
    
    <!-- Navigation Tabs (Timetable vs Leave requests) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Academic Schedulers & Leaves Desk</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Review class schedules, check section halls, and manage pending session leaves.</p>
            </div>

            <?php if ($role !== 'student'): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-1 bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200 dark:border-slate-800 select-none">
                <a
                    href="index.php?page=timetable&sub=timetable"
                    class="py-2 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_sub === 'timetable' ? 'bg-slate-900 text-white dark:bg-slate-850' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    Weekly Schedule
                </a>
                <?php if ($role === 'admin' || $role === 'faculty'): ?>
                <a
                    href="index.php?page=timetable&sub=courses"
                    class="py-2 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_sub === 'courses' ? 'bg-slate-900 text-white dark:bg-slate-850' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    Course Registry
                </a>
                <?php endif; ?>
                <a
                    href="index.php?page=timetable&sub=leave"
                    class="py-2 px-4 rounded-lg text-xs font-bold text-center transition-all <?php echo $active_sub === 'leave' ? 'bg-slate-900 text-white dark:bg-slate-850' : 'text-slate-550 dark:text-slate-400 hover:text-slate-800'; ?>"
                >
                    Leave Requests Desk
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($active_sub === 'timetable'): ?>
        <!-- Render Weekly Schedule block -->
        <div class="space-y-4">
            <h3 class="font-bold text-slate-800 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
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
                            <span class="text-slate-400 font-mono text-[10px]"><?php echo h(display_field(summarize_schedule_slots($course), 'Schedule pending')); ?></span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="font-extrabold text-slate-900 dark:text-white text-sm"><?php echo h(display_field(isset($course['title']) ? $course['title'] : '', 'Untitled course')); ?></h4>
                            <p class="text-slate-500 text-[11px] font-medium">Assigned: <?php echo h(display_field(summarize_room_slots($course), 'Not configured')); ?></p>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-50 dark:border-slate-800 text-[10px] text-slate-450">
                            <span><?php echo h(display_field(isset($course['coordinator']) ? $course['coordinator'] : '', 'Coordinator pending')); ?></span>
                            <span class="text-right">Enrolled: <strong class="text-slate-700 dark:text-slate-200"><?php echo get_enrolled_count($course); ?></strong></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($active_sub === 'courses'): ?>
        <?php if ($role !== 'admin' && $role !== 'faculty'): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-8 text-center shadow-sm text-slate-500 dark:text-slate-400">
                Course registry management is only available to administrative and faculty users.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-7 space-y-4">
                    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-slate-300 text-sm">Course Catalog Management</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Create or edit sections, schedule assignments, and coordinate student enrollment.</p>
                            </div>
                            <?php if ($editing_course): ?>
                                <a href="index.php?page=timetable&sub=courses" class="text-xs font-bold text-indigo-700 hover:underline">Start new course</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <?php if (empty($courses)): ?>
                            <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-8 text-center shadow-sm">
                                <?php empty_state('No courses yet', 'Add a new course record to populate the timetable and faculty schedules.', 'school'); ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
                                    <div class="flex justify-between items-start gap-3">
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-1 rounded-lg"><?php echo h($course['code']); ?></span>
                                            <h4 class="text-slate-900 dark:text-white font-bold mt-3"><?php echo h($course['title']); ?></h4>
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500"><?php echo h(summarize_schedule_slots($course)); ?></span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 text-[11px] text-slate-500">
                                        <div>Coordinator: <strong class="text-slate-700 dark:text-slate-200"><?php echo h($course['coordinator']); ?></strong></div>
                                        <div>Room: <strong class="text-slate-700 dark:text-slate-200"><?php echo h(summarize_room_slots($course)); ?></strong></div>
                                        <div>Hours: <strong class="text-slate-700 dark:text-slate-200"><?php echo h($course['totalHours']); ?></strong></div>
                                        <div>Enrolled: <strong class="text-slate-700 dark:text-slate-200"><?php echo get_enrolled_count($course); ?></strong></div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 pt-3">
                                        <a href="index.php?page=timetable&sub=courses&edit=<?php echo urlencode($course['code']); ?>" class="text-[10px] uppercase font-bold text-blue-700 bg-blue-50 dark:bg-blue-950/40 px-3 py-2 rounded-xl hover:bg-blue-100 transition">View</a>
                                        <?php if ($role === 'admin' || ($role === 'faculty' && isset($course['coordinator']) && $course['coordinator'] === display_name($current_user))): ?>
                                            <a href="index.php?page=timetable&sub=courses&edit=<?php echo urlencode($course['code']); ?>" class="text-[10px] uppercase font-bold text-blue-700 bg-blue-50 dark:bg-blue-950/40 px-3 py-2 rounded-xl hover:bg-blue-100 transition">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($role === 'admin'): ?>
                                            <form action="index.php?action=delete_course" method="POST" class="inline-block">
                                                <input type="hidden" name="course_code" value="<?php echo h($course['code']); ?>" />
                                                <button type="submit" class="text-[10px] uppercase font-bold text-red-700 bg-red-50 dark:bg-red-950/40 px-3 py-2 rounded-xl hover:bg-red-100 transition">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($role === 'admin' && isset($course['status']) && $course['status'] === 'Pending Approval'): ?>
                                            <form action="index.php?action=approve_course" method="POST" class="inline-block">
                                                <input type="hidden" name="course_code" value="<?php echo h($course['code']); ?>" />
                                                <button type="submit" class="text-[10px] uppercase font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 rounded-xl hover:bg-emerald-100 transition">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pt-3 text-[10px] uppercase tracking-wider font-bold <?php echo isset($course['status']) && $course['status'] === 'Approved' ? 'text-emerald-700' : 'text-amber-700'; ?>">
                                        Status: <?php echo h($course['status'] ?? 'Pending Approval'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="font-bold text-slate-800 dark:text-slate-300 text-sm"><?php echo $editing_course ? 'Edit Course' : 'Create Course'; ?></h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Keep schedules and student sections aligned for accurate attendance reporting.</p>

                    <form action="index.php?action=<?php echo $editing_course ? 'update_course' : 'create_course'; ?>" method="POST" class="space-y-4 text-xs">
                        <?php if ($editing_course): ?>
                            <input type="hidden" name="original_code" value="<?php echo h($editing_course['code']); ?>" />
                        <?php endif; ?>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-605">Course Code</label>
                            <input type="text" name="code" value="<?php echo h($editing_course['code'] ?? ''); ?>" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-605">Course Title</label>
                            <input type="text" name="title" value="<?php echo h($editing_course['title'] ?? ''); ?>" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-605">Coordinator</label>
                                <input type="text" name="coordinator" value="<?php echo h($editing_course['coordinator'] ?? ($role === 'faculty' ? display_name($current_user) : '')); ?>" <?php echo $role === 'faculty' ? 'readonly' : ''; ?> placeholder="Enter coordinator" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none" />
                                <?php if ($role === 'faculty'): ?>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Faculty proposals are automatically assigned to your account.</p>
                                <?php endif; ?>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-605">Total Lecture Hours</label>
                                <input type="number" min="0" name="total_hours" value="<?php echo h($editing_course['totalHours'] ?? ''); ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <label class="block font-bold text-slate-605">Class Schedule Slots</label>
                                <button type="button" id="add-schedule-slot" class="text-[10px] font-bold text-indigo-700 hover:underline">+ Add slot</button>
                            </div>
                            <div id="schedule-slot-list" class="space-y-2">
                                <?php $schedule_slots = get_schedule_slot_rows($editing_course); ?>
                                <?php if (empty($schedule_slots)): ?>
                                    <?php $schedule_slots = [['day' => '', 'start' => '', 'end' => '', 'room' => '']]; ?>
                                <?php endif; ?>
                                <?php foreach ($schedule_slots as $slot): ?>
                                    <div class="schedule-slot-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_1fr_1fr_auto] gap-2">
                                        <select name="schedule_slots[][day]" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                            <option value="" <?php echo (($slot['day'] ?? '') === '' ? 'selected' : ''); ?>>Day</option>
                                            <option value="Mon" <?php echo (($slot['day'] ?? '') === 'Mon' ? 'selected' : ''); ?>>Monday</option>
                                            <option value="Tue" <?php echo (($slot['day'] ?? '') === 'Tue' ? 'selected' : ''); ?>>Tuesday</option>
                                            <option value="Wed" <?php echo (($slot['day'] ?? '') === 'Wed' ? 'selected' : ''); ?>>Wednesday</option>
                                            <option value="Thu" <?php echo (($slot['day'] ?? '') === 'Thu' ? 'selected' : ''); ?>>Thursday</option>
                                            <option value="Fri" <?php echo (($slot['day'] ?? '') === 'Fri' ? 'selected' : ''); ?>>Friday</option>
                                            <option value="Sat" <?php echo (($slot['day'] ?? '') === 'Sat' ? 'selected' : ''); ?>>Saturday</option>
                                            <option value="Sun" <?php echo (($slot['day'] ?? '') === 'Sun' ? 'selected' : ''); ?>>Sunday</option>
                                        </select>
                                        <select name="schedule_slots[][start]" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                            <option value="" <?php echo (($slot['start'] ?? '') === '' ? 'selected' : ''); ?>>Start</option>
                                            <?php for ($hour = 7; $hour <= 20; $hour++): ?>
                                                <?php foreach (["00", "30"] as $min): ?>
                                                    <?php $value = sprintf('%02d:%s', $hour, $min); ?>
                                                    <option value="<?php echo h($value); ?>" <?php echo (($slot['start'] ?? '') === $value ? 'selected' : ''); ?>><?php echo h($value); ?></option>
                                                <?php endforeach; ?>
                                            <?php endfor; ?>
                                        </select>
                                        <select name="schedule_slots[][end]" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                            <option value="" <?php echo (($slot['end'] ?? '') === '' ? 'selected' : ''); ?>>End</option>
                                            <?php for ($hour = 7; $hour <= 20; $hour++): ?>
                                                <?php foreach (["00", "30"] as $min): ?>
                                                    <?php $value = sprintf('%02d:%s', $hour, $min); ?>
                                                    <option value="<?php echo h($value); ?>" <?php echo (($slot['end'] ?? '') === $value ? 'selected' : ''); ?>><?php echo h($value); ?></option>
                                                <?php endforeach; ?>
                                            <?php endfor; ?>
                                        </select>
                                        <input type="text" name="schedule_slots[][room]" value="<?php echo h($slot['room'] ?? ''); ?>" placeholder="Hall / Room" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none" />
                                        <button type="button" class="remove-schedule-slot text-[10px] font-bold text-red-700 hover:underline">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <template id="schedule-slot-template">
                                <div class="schedule-slot-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_1fr_1fr_auto] gap-2">
                                    <select name="schedule_slots[][day]" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                        <option value="">Day</option>
                                        <option value="Mon">Monday</option>
                                        <option value="Tue">Tuesday</option>
                                        <option value="Wed">Wednesday</option>
                                        <option value="Thu">Thursday</option>
                                        <option value="Fri">Friday</option>
                                        <option value="Sat">Saturday</option>
                                        <option value="Sun">Sunday</option>
                                    </select>
                                    <select name="schedule_slots[][start]" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                        <option value="">Start</option>
                                        <?php for ($hour = 7; $hour <= 20; $hour++): ?>
                                            <?php foreach (["00", "30"] as $min): ?>
                                                <?php $value = sprintf('%02d:%s', $hour, $min); ?>
                                                <option value="<?php echo h($value); ?>"><?php echo h($value); ?></option>
                                            <?php endforeach; ?>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="schedule_slots[][end]" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                        <option value="">End</option>
                                        <?php for ($hour = 7; $hour <= 20; $hour++): ?>
                                            <?php foreach (["00", "30"] as $min): ?>
                                                <?php $value = sprintf('%02d:%s', $hour, $min); ?>
                                                <option value="<?php echo h($value); ?>"><?php echo h($value); ?></option>
                                            <?php endforeach; ?>
                                        <?php endfor; ?>
                                    </select>
                                    <input type="text" name="schedule_slots[][room]" placeholder="Hall / Room" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none" />
                                    <button type="button" class="remove-schedule-slot text-[10px] font-bold text-red-700 hover:underline">Remove</button>
                                </div>
                            </template>
                            <script>
                                const scheduleSlotList = document.getElementById('schedule-slot-list');
                                const addScheduleSlotButton = document.getElementById('add-schedule-slot');
                                const scheduleSlotTemplate = document.getElementById('schedule-slot-template');
                                if (scheduleSlotList && addScheduleSlotButton && scheduleSlotTemplate) {
                                    addScheduleSlotButton.addEventListener('click', function () {
                                        const clone = scheduleSlotTemplate.content.firstElementChild.cloneNode(true);
                                        scheduleSlotList.appendChild(clone);
                                    });
                                    scheduleSlotList.addEventListener('click', function (event) {
                                        const removeButton = event.target.closest('.remove-schedule-slot');
                                        if (removeButton) {
                                            const row = removeButton.closest('.schedule-slot-row');
                                            if (row) {
                                                row.remove();
                                            }
                                        }
                                    });
                                }
                            </script>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-605">Enroll Students</label>
                            <select name="enrolled_students[]" multiple size="6" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-slate-800 dark:text-slate-200 focus:border-indigo-500 focus:outline-none">
                                <?php foreach ($students as $student): ?>
                                    <?php $roll = isset($student['rollNo']) ? $student['rollNo'] : ''; ?>
                                    <option value="<?php echo h($roll); ?>" <?php echo ($editing_course && isset($editing_course['enrolledStudents']) && in_array($roll, $editing_course['enrolledStudents'], true)) ? 'selected' : ''; ?>>
                                        <?php echo h(display_name($student)); ?> (<?php echo h($roll); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="pt-2 select-none">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-extrabold py-3 px-4 rounded-xl shadow-md transition-colors">
                                <?php echo $editing_course ? 'Update Course' : 'Create Course'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Render Leave Requests flow (Forms if Student, Logs of leaves) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Submit Leave Form (Col 5 - Visible to Student role only) -->
            <?php if ($role === 'student'): ?>
            <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
                <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
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
                    <div class="pb-3 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-white text-sm">Active Leaves Registry</h3>
                            <p class="text-xs text-slate-500 font-medium font-sans">History of leave filings linked to institutional identity.</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-400">pending_actions</span>
                    </div>

                    <div class="space-y-4">
                        <?php if (empty($leaves)): ?>
                        <div class="p-6 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
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
                        <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-3">
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

                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2 text-[10px] text-slate-400">
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
