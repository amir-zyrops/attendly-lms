<?php
/**
 * Attendly Academic Portal - PHP Interactive Attendance Sheets view
 */

require_once __DIR__ . '/config.php';

if ($current_user['role'] !== 'faculty' && $current_user['role'] !== 'admin') {
    echo '<div class="bg-white border p-8 text-center italic text-slate-400 rounded-2xl">Access restricted to Institutional Instructors.</div>';
    return;
}

$courses = get_table(COURSES_FILE);
$selected_course_code = isset($_GET['course']) ? $_GET['course'] : '';

// Resolve active course
$active_course = null;
foreach ($courses as $co) {
    if ($co['code'] === $selected_course_code) {
        $active_course = $co;
        break;
    }
}

if (!$active_course && !empty($courses)) {
    $active_course = reset($courses);
}

if (!$active_course) {
    empty_state('No courses available', 'Add a course record before marking attendance.', 'fact_check');
    return;
}

$students = get_table(STUDENTS_FILE);
// filter down students enrolled in this course
$enrolled_students = array_filter($students, function($st) use ($active_course) {
    return isset($st['enrolledSections']) && in_array($active_course['code'], $st['enrolledSections']);
});
?>
<div class="space-y-6 animate-fade-in">
    
    <!-- Top Back Navigation Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <a 
            href="index.php?page=dashboard" 
            class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 px-3 py-2 rounded-xl hover:shadow-xs transition-colors"
        >
            <span class="material-symbols-outlined text-xs">arrow_back</span>
            Back to Faculty Desk
        </a>
        <span class="text-[10px] font-mono text-slate-400 font-bold uppercase">Lecture Sheets Controller</span>
    </div>

    <!-- Active Class Card Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-3xl p-6 shadow-xl space-y-4">
        <div class="flex justify-between items-start">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold text-blue-200 bg-white/10 border border-white/15 px-3 py-0.5 rounded font-mono uppercase tracking-wider">
                    Course: <?php echo htmlspecialchars($active_course['code']); ?>
                </span>
                <h1 class="text-xl font-black mt-2 tracking-tight"><?php echo htmlspecialchars($active_course['title']); ?></h1>
            </div>
            <span class="material-symbols-outlined text-white/30 text-2xl select-none">edit_calendar</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-medium border-t border-white/10 pt-4">
            <div>
                <span class="text-blue-200 uppercase text-[9px] font-semibold tracking-wider block">Assigned Rooms</span>
                <span class="font-bold text-white mt-1 block"><?php echo htmlspecialchars($active_course['rooms']); ?></span>
            </div>
            <div>
                <span class="text-blue-200 uppercase text-[9px] font-semibold tracking-wider block">Lecturers</span>
                <span class="font-bold text-white mt-1 block"><?php echo htmlspecialchars($active_course['coordinator']); ?></span>
            </div>
            <div>
                <span class="text-blue-200 uppercase text-[9px] font-semibold tracking-wider block">Academic Lecture Hours</span>
                <span class="font-bold text-white mt-1 block"><?php echo htmlspecialchars($active_course['totalHours']); ?> Sched Hrs</span>
            </div>
            <div>
                <span class="text-blue-200 uppercase text-[9px] font-semibold tracking-wider block">Verified Compliance Limit</span>
                <span class="font-bold text-white mt-1 block"><?php echo htmlspecialchars($active_course['compliance']); ?>% Attendance</span>
            </div>
        </div>
    </div>

    <!-- Interactive Interactive Attendance List -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-5">
        
        <div class="pb-3 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white text-sm">Attendance List Roll Sheet</h3>
                <p class="text-[11px] text-slate-500 font-medium">Verify credentials and check state for today's session roster.</p>
            </div>
            <div class="flex gap-2 text-[10px] font-mono">
                <span class="px-2 py-0.5 rounded bg-green-50 dark:bg-green-950/40 text-green-700 font-bold border border-green-200/30">PRESENT</span>
                <span class="px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-700 font-bold border border-amber-200/30">LATE / DELAY</span>
                <span class="px-2 py-0.5 rounded bg-red-50 dark:bg-red-950/40 text-red-700 font-bold border border-red-200/30">ABSENT</span>
            </div>
        </div>

        <form action="index.php?action=submit_attendance" method="POST" class="space-y-6">
            <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($active_course['code']); ?>" />

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-950 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-800">
                            <th class="py-3 px-3">Student Name</th>
                            <th class="py-3 px-3">Roll Number ID</th>
                            <th class="py-3 px-3">Sec Attendance Avg</th>
                            <th class="py-3 px-3 text-center">Set Session State</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($enrolled_students)): ?>
                        <tr>
                            <td colspan="4" class="py-8 px-3 text-center text-slate-400 italic">No students are enrolled in this course yet.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($enrolled_students as $st): ?>
                        <tr class="border-b border-slate-50 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-colors">
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-2.5">
                                    <?php echo avatar_markup($st, 'w-8 h-8 rounded-full border'); ?>
                                    <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo h(display_name($st)); ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-500 dark:text-slate-400">
                                <?php echo h(isset($st['rollNo']) ? $st['rollNo'] : ''); ?>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-400"><?php echo h(isset($st['attendance']) ? $st['attendance'] : 0); ?>%</span>
                            </td>
                            <td class="py-3 px-3">
                                <!-- Interactive custom selectors -->
                                <div class="flex justify-center gap-1 select-none">
                                    
                                    <!-- Present -->
                                    <label class="cursor-pointer">
                                        <input type="radio" name="attendance[<?php echo h(isset($st['rollNo']) ? $st['rollNo'] : ''); ?>]" value="Present" checked class="sr-only peer" />
                                        <span class="px-3.5 py-1.5 rounded-xl border border-slate-150 hover:border-green-500 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-950 text-[11px] font-bold peer-checked:bg-green-650 peer-checked:text-white peer-checked:border-green-650 block transition-colors">
                                            Present
                                        </span>
                                    </label>

                                    <!-- Late -->
                                    <label class="cursor-pointer">
                                        <input type="radio" name="attendance[<?php echo h(isset($st['rollNo']) ? $st['rollNo'] : ''); ?>]" value="Late" class="sr-only peer" />
                                        <span class="px-3.5 py-1.5 rounded-xl border border-slate-150 hover:border-amber-500 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-950 text-[11px] font-bold peer-checked:bg-amber-550 peer-checked:text-white peer-checked:border-amber-550 block transition-colors">
                                            Late
                                        </span>
                                    </label>

                                    <!-- Absent -->
                                    <label class="cursor-pointer">
                                        <input type="radio" name="attendance[<?php echo h(isset($st['rollNo']) ? $st['rollNo'] : ''); ?>]" value="Absent" class="sr-only peer" />
                                        <span class="px-3.5 py-1.5 rounded-xl border border-slate-150 hover:border-red-500 bg-slate-50 text-slate-500 dark:border-slate-800 dark:bg-slate-950 text-[11px] font-bold peer-checked:bg-red-550 peer-checked:text-white peer-checked:border-red-550 block transition-colors">
                                            Absent
                                        </span>
                                    </label>

                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Verification Action trigger -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <span class="text-[11px] text-slate-450 font-medium">Clicking button locks the session roll ledger. Reports cannot be modified dynamically without Admin override credentials.</span>
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs py-2.5 px-6 rounded-xl cursor-pointer shadow-md shadow-blue-550/15 flex items-center gap-1.5"
                >
                    <span class="material-symbols-outlined text-xs">how_to_reg</span>
                    Submit & Lock sheet
                </button>
            </div>
        </form>

    </div>

</div>
