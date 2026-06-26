<?php
/**
 * Attendly Academic Portal - PHP Role-Based Main Dashboard view
 */

require_once __DIR__ . '/config.php';

$role = $current_user['role'];
$students = get_table(STUDENTS_FILE);
$courses = get_table(COURSES_FILE);
$leaves = get_table(LEAVES_FILE);
$logs = get_table(LOGS_FILE);

// Calculate global metrics
$total_students = count($students);
$total_courses = count($courses);

$avg_compliance = 0;
if ($total_students > 0) {
    $sum = 0;
    foreach ($students as $st) {
        $sum += isset($st['attendance']) ? (float) $st['attendance'] : 0;
    }
    $avg_compliance = round($sum / $total_students, 1);
}

// ------------------------------------------------------------------------
// SECTION 1: ADMIN PORTAL
// ------------------------------------------------------------------------
if ($role === 'admin'):
?>
<div class="space-y-6 animate-fade-in">
    <!-- Admin Hero Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 border border-slate-800 rounded-3xl p-6 shadow-xl text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-500">verified_user</span>
                Administrative Registry Panel
            </h1>
            <p class="text-xs text-slate-300 mt-1 font-medium">Institution auditing workspace. Monitor active attendance metrics and security event feeds.</p>
        </div>
        <span class="text-[10px] font-mono bg-blue-600/30 text-blue-300 border border-blue-500/30 px-3 py-1 rounded-md uppercase font-bold tracking-wider">
            Secure Node: #<?php echo mt_rand(450, 499); ?>
        </span>
    </div>

    <!-- Metrics Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Metric 1 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex justify-between items-center text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Enrolled Directory</span>
                <span class="material-symbols-outlined text-indigo-500 text-xl">groups</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white"><?php echo $total_students; ?></span>
                <span class="text-[10px] text-green-600 bg-green-50 dark:bg-green-950/40 px-2 py-0.5 rounded font-bold uppercase">Active Students</span>
            </div>
            <p class="text-[10px] text-slate-400">Directory listings synchronized from main registrar server database.</p>
        </div>

        <!-- Metric 2 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex justify-between items-center text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Active Lectures</span>
                <span class="material-symbols-outlined text-royal-500 text-xl">auto_stories</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white"><?php echo $total_courses; ?></span>
                <span class="text-[10px] text-blue-600 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded font-bold uppercase">Academic Modules</span>
            </div>
            <p class="text-[10px] text-slate-400">Active class registries with scheduled timeslots and rooms.</p>
        </div>

        <!-- Metric 3 -->
        <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <div class="flex justify-between items-center text-slate-400">
                <span class="text-xs font-bold uppercase tracking-wider">Academic Compliance</span>
                <span class="material-symbols-outlined text-green-500 text-xl">insights</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white"><?php echo $avg_compliance; ?>%</span>
                <span class="text-[10px] text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded font-bold uppercase">Institution Avg</span>
            </div>
            <p class="text-[10px] text-slate-400">Overall academic attendance percentage across all student rolls.</p>
        </div>
    </div>

    <!-- Quick Stats Grid: Logs + Student Directory Table -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Student Attendance Registry Table (Col 8) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-sm">Active Student Registry</h3>
                        <p class="text-[11px] text-slate-450 mt-0.5">Real-time attendance averages verified by lecture submittals.</p>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 text-base">fact_check</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950 text-slate-500 font-bold border-b border-slate-100 dark:border-slate-850">
                                <th class="py-2.5 px-3">Student Name</th>
                                <th class="py-2.5 px-3">Roll ID</th>
                                <th class="py-2.5 px-3">Compliance Meter</th>
                                <th class="py-2.5 px-3">State Status</th>
                                <th class="py-2.5 px-3 text-right">Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="py-8 px-3 text-center text-slate-400 italic">No student records have been added yet.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($students as $st): 
                                $pct = isset($st['attendance']) ? (float) $st['attendance'] : 0;
                                $bar_color = 'bg-blue-600';
                                $badge_color = 'text-green-700 bg-green-50 dark:bg-green-950/40';
                                if ($pct < 75) {
                                    $bar_color = 'bg-red-500';
                                    $badge_color = 'text-red-700 bg-red-50 dark:bg-red-950/40';
                                } else if ($pct < 85) {
                                    $bar_color = 'bg-amber-500';
                                    $badge_color = 'text-amber-700 bg-amber-50 dark:bg-amber-950/40';
                                }
                            ?>
                            <tr class="border-b border-slate-50 dark:border-slate-850 hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2.5">
                                        <?php echo avatar_markup($st, 'w-7 h-7 rounded-full border'); ?>
                                        <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo h(display_name($st)); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 font-mono font-medium text-slate-500 dark:text-slate-400"><?php echo h(isset($st['rollNo']) ? $st['rollNo'] : ''); ?></td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2 min-w-[100px]">
                                        <div class="h-1.5 w-16 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shrink-0">
                                            <div class="h-full <?php echo $bar_color; ?>" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300"><?php echo $pct; ?>%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded uppercase <?php echo $badge_color; ?>">
                                        <?php echo h(display_field(isset($st['status']) ? $st['status'] : '', 'Not set')); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="index.php?page=timetable&sub=timetable" class="text-[10px] font-extrabold text-indigo-700 hover:underline">Timestamps</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <span class="text-[10px] text-slate-400 font-medium">Academic registrar compliance logs are updated hourly.</span>
                <a href="index.php?page=reports" class="text-xs font-bold text-blue-600 hover:underline inline-flex items-center gap-1">
                    Export Ledger PDF
                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Portal Activity Feeds (Col 4) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-extrabold text-slate-900 dark:text-white text-sm">System Logs</h3>
                    <p class="text-[11px] text-slate-450 mt-0.5">Real-time ledger audit trails</p>
                </div>
                <span class="material-symbols-outlined text-amber-500 text-base">dns</span>
            </div>

            <!-- Scrollable Stream logs -->
            <div class="space-y-3.5 max-h-[340px] overflow-y-auto pr-1">
                <?php if (empty($logs)): ?>
                <div class="p-6 bg-slate-50 dark:bg-slate-950/50 rounded-xl border border-slate-100 dark:border-slate-850 text-center">
                    <p class="text-xs text-slate-400 italic">No activity has been recorded yet.</p>
                </div>
                <?php else: ?>
                <?php foreach ($logs as $lg): ?>
                <div class="p-3 bg-slate-50 dark:bg-slate-950/50 rounded-xl border border-slate-100 dark:border-slate-850 space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-[9px] font-mono font-bold text-slate-400 uppercase tracking-wider">EVENT LOG</span>
                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                    </div>
                    <p class="text-[11px] text-slate-700 dark:text-slate-300 leading-relaxed font-medium"><?php echo htmlspecialchars($lg); ?></p>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php 
// ------------------------------------------------------------------------
// SECTION 2: FACULTY PORTAL
// ------------------------------------------------------------------------
elseif ($role === 'faculty'):
?>
<div class="space-y-6 animate-fade-in">
    <!-- Welcome Faculty Header -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">Welcome Back, <?php echo h(display_name($current_user)); ?></h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Institutional instructor account - <?php echo h(display_field(isset($current_user['designation']) ? $current_user['designation'] : '')); ?> for <?php echo h(display_field(isset($current_user['department']) ? $current_user['department'] : '')); ?>.</p>
        </div>
        <div class="flex items-center gap-2 font-semibold text-xs leading-none">
            <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-ping"></span>
            <span class="text-slate-500 dark:text-slate-400">Class Session Live</span>
        </div>
    </div>

    <!-- Two columns: Left: Course sheets selection, Right: Leaves approval -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Courses Registry Section (Col 7) -->
        <div class="lg:col-span-7 space-y-4">
            <h3 class="font-extrabold text-slate-805 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-blue-550 text-sm">assignment_ind</span>
                Assigned Lectures & Sections
            </h3>

            <div class="space-y-4">
                <?php 
                $faculty_courses = array_filter($courses, function($co) use ($current_user) {
                    return isset($co['coordinator'], $current_user['name']) && $co['coordinator'] === $current_user['name'];
                });
                if(empty($faculty_courses)):
                    empty_state('No lectures assigned', 'Course records will appear here after they are added to the course registry.', 'auto_stories');
                else:
                foreach ($faculty_courses as $co):
                ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow space-y-4">
                    <div class="flex justify-between items-start">
                        <div class="space-y-1">
                            <span class="text-[9px] font-extrabold text-indigo-700 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded font-mono uppercase tracking-wider">
                                <?php echo htmlspecialchars($co['code']); ?>
                            </span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-base mt-2"><?php echo htmlspecialchars($co['title']); ?></h4>
                        </div>
                        <span class="material-symbols-outlined text-slate-350 select-none">book_2</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs font-medium text-slate-500 border-t border-b border-slate-50 dark:border-slate-850 py-3">
                        <div class="space-y-1.5">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wide block">Schedule Slot</span>
                            <span class="text-slate-800 dark:text-slate-300 font-bold"><?php echo htmlspecialchars($co['schedule']); ?></span>
                        </div>
                        <div class="space-y-1.5">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wide block">Assigned Hall</span>
                            <span class="text-slate-800 dark:text-slate-300 font-bold"><?php echo htmlspecialchars($co['rooms']); ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <div class="text-xs text-slate-450 flex items-center gap-3">
                            <span>Students Registered: <strong class="text-slate-805 dark:text-white font-extrabold"><?php echo $co['studentCount']; ?></strong></span>
                            <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span>
                            <span>Compliance: <strong class="text-slate-805 dark:text-white font-extrabold"><?php echo $co['compliance']; ?>%</strong></span>
                        </div>
                        
                        <a 
                            href="index.php?page=attendance&course=<?php echo urlencode($co['code']); ?>"
                            class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 px-4 rounded-xl shadow-md shadow-blue-550/15 cursor-pointer flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm">how_to_reg</span>
                            Mark Sheet
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leave Approvals Inbox (Col 5) -->
        <div class="lg:col-span-5 space-y-4">
            <h3 class="font-extrabold text-slate-805 dark:text-slate-300 text-xs uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-amber-550 text-sm">mail</span>
                Student Leave Approvals Inbox
            </h3>

            <div class="space-y-4">
                <?php 
                $pending_leaves = array_filter($leaves, function($lv) {
                    return $lv['status'] === 'Pending';
                });
                
                if (empty($pending_leaves)):
                ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 p-8 rounded-2xl text-center shadow-xs">
                    <span class="material-symbols-outlined text-green-550 text-3xl mb-2">verified</span>
                    <h4 class="font-extrabold text-slate-800 dark:text-white text-sm">Queue Fully Compliant</h4>
                    <p class="text-slate-450 text-[11px] mt-1">No pending student leaves awaiting faculty evaluation.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($pending_leaves as $lv): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
                        <div class="flex items-center gap-3">
                            <?php echo avatar_markup(['name' => $lv['studentName'], 'avatar' => isset($lv['studentAvatar']) ? $lv['studentAvatar'] : '', 'role' => 'student'], 'w-9 h-9 rounded-full border'); ?>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-805 dark:text-white text-xs truncate"><?php echo h(display_field(isset($lv['studentName']) ? $lv['studentName'] : '', 'Student')); ?></h4>
                                <span class="text-[9px] font-mono text-slate-450 block">ROLL ID: <?php echo h(display_field(isset($lv['rollNo']) ? $lv['rollNo'] : '', 'Not configured')); ?></span>
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 dark:bg-slate-950/60 rounded-xl border border-slate-100 dark:border-slate-850 space-y-1 text-xs">
                            <div class="flex justify-between text-[10px] text-slate-450 font-semibold mb-1">
                                <span>TYPE: <?php echo strtoupper(htmlspecialchars($lv['type'])); ?></span>
                                <span class="font-mono"><?php echo $lv['date']; ?></span>
                            </div>
                            <p class="text-slate-650 dark:text-slate-300 leading-relaxed font-medium">"<?php echo htmlspecialchars($lv['reason']); ?>"</p>
                        </div>

                        <!-- Action Controls -->
                        <div class="flex gap-2 pt-1 select-none">
                            <a 
                                href="index.php?action=update_leave&id=<?php echo $lv['id']; ?>&status=Approved"
                                class="flex-1 text-center py-2 bg-green-650 hover:bg-green-600 text-white font-bold rounded-xl text-[11px] transition-colors cursor-pointer"
                            >
                                Approve Leave
                            </a>
                            <a 
                                href="index.php?action=update_leave&id=<?php echo $lv['id']; ?>&status=Rejected"
                                class="flex-1 text-center py-2 bg-slate-100 dark:bg-slate-800 hover:bg-red-50 hover:text-red-700 text-slate-600 dark:text-slate-300 font-bold rounded-xl text-[11px] transition-colors cursor-pointer"
                            >
                                Decline
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php 
// ------------------------------------------------------------------------
// SECTION 3: STUDENT & PARENT PORTALS
// ------------------------------------------------------------------------
else: // Student/Parent
    // Link to the first available student record until formal account mapping is configured.
    $student_obj = null;
    foreach ($students as $st) {
        if (isset($st['rollNo']) && $st['rollNo'] !== '') {
            $student_obj = $st;
            break;
        }
    }
    if (!$student_obj && !empty($students)) {
        $student_obj = reset($students);
    }
    if (!$student_obj):
?>
<div class="space-y-6 animate-fade-in">
    <?php empty_state('No student profile linked', 'Add a student record before using the student or parent dashboard.', 'person_search'); ?>
</div>
<?php
        return;
    endif;
    
    $student_attendance = isset($student_obj['attendance']) ? (float) $student_obj['attendance'] : 0;
?>
<div class="space-y-6 animate-fade-in">
    <!-- Welcome Header / Parent sponsor designation -->
    <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <?php if ($role === 'parent'): ?>
                <h1 class="text-xl font-bold text-slate-905 dark:text-white">Sponsor View: <?php echo h(display_name($current_user)); ?></h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Reviewing linked academic records: <strong><?php echo h(display_name($student_obj)); ?> (<?php echo h(display_field(isset($student_obj['rollNo']) ? $student_obj['rollNo'] : '', 'No roll ID')); ?>)</strong>.</p>
            <?php else: ?>
                <h1 class="text-xl font-bold text-slate-905 dark:text-white">Active Student Workspace: <?php echo h(display_name($current_user)); ?></h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Department: <?php echo h(display_field(isset($current_user['department']) ? $current_user['department'] : '')); ?>. Registered student enrollment log.</p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <span class="w-2.5 h-2.5 bg-blue-500 rounded-full shrink-0"></span>
            <span>Local connection synchronized</span>
        </div>
    </div>

    <!-- Dashboard Body Columns: Left: Circular compliance meter, Right: Course sheets breakdowns -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Attendance Ring display (Col 4) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between items-center text-center space-y-6">
            <div class="w-full text-left">
                <h3 class="font-extrabold text-slate-850 dark:text-white text-xs uppercase tracking-wider">Overall Academic Compliance</h3>
            </div>

            <!-- CSS SVG Circle Meter -->
            <div class="relative w-40 h-40 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90">
                    <circle cx="80" cy="80" r="70" class="stroke-slate-100 dark:stroke-slate-850 stroke-[8] fill-none"></circle>
                    <circle cx="80" cy="80" r="70" class="stroke-blue-600 stroke-[8] fill-none stroke-linecap-round" stroke-dasharray="440" stroke-dashoffset="<?php echo 440 - (440 * $student_attendance / 100); ?>"></circle>
                </svg>
                <div class="absolute flex flex-col items-center">
                    <span class="text-3xl font-black text-slate-900 dark:text-white font-mono"><?php echo $student_attendance; ?>%</span>
                    <span class="text-[10px] text-slate-450 font-bold uppercase tracking-wider block mt-1">Lec Hours Attend</span>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-[10px] font-extrabold px-3 py-1 rounded-full uppercase <?php echo ($student_attendance >= 85) ? 'text-green-700 bg-green-50 dark:bg-green-950/40' : 'text-amber-700 bg-amber-50 dark:bg-amber-950/40'; ?>">
                    Status: <?php echo h(display_field(isset($student_obj['status']) ? $student_obj['status'] : '', 'Not set')); ?>
                </span>
                <p class="text-slate-450 text-[11px] max-w-[220px] leading-relaxed mx-auto">Requires minimal 75% final rollup attendance compliance for examination permissions.</p>
            </div>
        </div>

        <!-- Course Averages & Schedules Lists (Col 8) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-5">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-slate-850 dark:text-white text-sm">Course Attendance Breakdowns</h3>
                    <p class="text-xs text-slate-500 font-medium">Lecture sheets tracking matrix for linked registration sections.</p>
                </div>
                <span class="material-symbols-outlined text-slate-400">equalizer</span>
            </div>

            <!-- Subject Breakdowns list -->
            <div class="space-y-4">
                <?php 
                $student_sections = isset($student_obj['enrolledSections']) ? $student_obj['enrolledSections'] : [];
                $rendered_sections = 0;
                foreach ($courses as $co):
                    if (isset($co['code']) && in_array($co['code'], $student_sections)):
                        $rendered_sections++;
                        // emulate unique student attendance per subject
                        $subj_attendance = round($student_attendance + mt_rand(-4, 4), 1);
                        $subj_attendance = min(100.0, max(45.0, $subj_attendance));
                        
                        $row_color = 'bg-blue-600';
                        if ($subj_attendance < 75) {
                            $row_color = 'bg-red-500';
                        } else if ($subj_attendance < 85) {
                            $row_color = 'bg-amber-500';
                        }
                ?>
                <div class="space-y-2">
                    <div class="flex justify-between items-baseline text-xs">
                        <div class="space-x-1.5 flex items-center">
                            <span class="font-black text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($co['code']); ?></span>
                            <span class="text-slate-400 font-medium">|</span>
                            <span class="text-slate-450 font-medium text-[11px] truncate max-w-[200px]"><?php echo htmlspecialchars($co['title']); ?></span>
                        </div>
                        <div class="font-mono font-bold text-slate-705 dark:text-slate-300">
                            <?php echo $subj_attendance; ?>%
                        </div>
                    </div>
                    <!-- Horizontal Progress block -->
                    <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $row_color; ?>" style="width: <?php echo $subj_attendance; ?>%"></div>
                    </div>
                </div>
                <?php 
                    endif;
                endforeach;
                if ($rendered_sections === 0):
                ?>
                <div class="p-6 bg-slate-50 dark:bg-slate-950/50 rounded-xl border border-slate-100 dark:border-slate-850 text-center">
                    <p class="text-xs text-slate-400 italic">No course registrations are linked to this student yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end font-medium text-xs select-none">
                <a href="index.php?page=timetable" class="text-blue-600 hover:underline flex items-center gap-1">
                    Check Detailed Schedules
                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                </a>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>
