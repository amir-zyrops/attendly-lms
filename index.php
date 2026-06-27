<?php
/**
 * Attendly Academic Portal - PHP Unified Router and Core Controller
 * Serves role-based views dynamically with zero dependencies and real-time state persistence.
 */

require_once __DIR__ . '/config.php';

// Determine requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Handle core action dispatches
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'login') {
        $role = isset($_POST['role']) ? $_POST['role'] : 'student';
        $users = get_table(USERS_FILE);
        $login_user = isset($users[$role]) ? $users[$role] : null;

        if ($login_user && !empty($login_user['email'])) {
            $_SESSION['user'] = $login_user;
            $_SESSION['user']['role'] = $role;
            $label = display_name($_SESSION['user']);
            add_log("{$label} signed in to the " . role_display_name($role) . " portal.");
            trigger_toast("Sign-in successful. Active role: " . role_display_name($role));
            header('Location: index.php?page=dashboard');
            exit;
        }
    }

    if ($action === 'request_otp') {
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $users = get_table(USERS_FILE);

        if ($role === 'parent') {
            $student_roll = isset($_POST['student_roll']) ? trim($_POST['student_roll']) : '';
            $student = find_student_by_roll_no($student_roll);
            if (!$student || !isset($student['email']) || !filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
                trigger_toast('Please select a linked student with a valid email to receive the OTP.');
                header('Location: index.php?page=login&step=email&role=' . urlencode($role));
                exit;
            }
            $email = trim($student['email']);
            $_SESSION['otp_student_roll'] = $student_roll;
        } elseif ($role === 'student') {
            if ($email === '') {
                trigger_toast('Please provide a registered student email.');
                header('Location: index.php?page=login&step=email&role=' . urlencode($role));
                exit;
            }
            $student = find_student_by_email($email);
            if (!$student) {
                trigger_toast('No student record found for that email. Please use your registered student email.');
                header('Location: index.php?page=login&step=email&role=' . urlencode($role));
                exit;
            }
            $role = 'student';
        } else {
            if ($email === '') {
                trigger_toast('Please provide a registered email address.');
                header('Location: index.php?page=login&step=email&role=' . urlencode($role));
                exit;
            }
            $logged_in = find_user_by_email($email);
            if (!$logged_in || $logged_in['role'] !== $role) {
                trigger_toast('Email does not match the selected portal. Choose the correct portal or email.');
                header('Location: index.php?page=login&step=email&role=' . urlencode($role));
                exit;
            }
        }

        $_SESSION['otp_role'] = $role;
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_code'] = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['otp_expires'] = time() + OTP_TTL_SECONDS;

        $sent = send_otp_email($email, $_SESSION['otp_code']);
        add_log("OTP generated for {$role} and dispatched to {$email}.");

        trigger_toast('Check your email for the verification code.');

        header('Location: index.php?page=login&step=verify&role=' . urlencode($role));
        exit;
    }

    if ($action === 'verify_otp') {
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        $code = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
        $email = isset($_SESSION['otp_email']) ? trim($_SESSION['otp_email']) : '';

        if (!isset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_role']) || time() > $_SESSION['otp_expires']) {
            trigger_toast('OTP is missing or has expired. Please request a new code.');
            header('Location: index.php?page=login&step=email&role=' . urlencode($role));
            exit;
        }

        if ($role !== $_SESSION['otp_role'] || $code !== $_SESSION['otp_code']) {
            trigger_toast('The code you entered is incorrect. Please try again.');
            header('Location: index.php?page=login&step=verify&role=' . urlencode($role));
            exit;
        }

        $users = get_table(USERS_FILE);
        if ($role !== 'student' && !isset($users[$role])) {
            trigger_toast('The selected portal is not available.');
            header('Location: index.php?page=login');
            exit;
        }

        if ($role === 'student') {
            $student = find_student_by_email($email);
            if (!$student) {
                trigger_toast('Student record could not be located after verification. Please request a new code.');
                header('Location: index.php?page=login&step=email&role=' . urlencode($role));
                exit;
            }
            $_SESSION['user'] = array_merge(isset($users['student']) ? $users['student'] : [], $student);
            $_SESSION['user']['role'] = 'student';
        } else {
            $_SESSION['user'] = $users[$role];
            $_SESSION['user']['role'] = $role;
            if ($role === 'parent' && isset($_SESSION['otp_student_roll'])) {
                $_SESSION['user']['linked_student_roll'] = $_SESSION['otp_student_roll'];
                $_SESSION['user']['linked_student_email'] = $email;
            }
        }

        unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_role'], $_SESSION['otp_email'], $_SESSION['otp_student_roll']);

        add_log(display_name($_SESSION['user']) . ' authenticated via OTP for ' . role_display_name($role));
        trigger_toast('OTP verified. Welcome to your portal.');
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($action === 'resend_otp') {
        $role = isset($_POST['role']) ? $_POST['role'] : '';

        if (!isset($_SESSION['otp_email'], $_SESSION['otp_role']) || $role !== $_SESSION['otp_role']) {
            trigger_toast('Unable to resend OTP. Please start over.');
            header('Location: index.php?page=login');
            exit;
        }

        $email = $_SESSION['otp_email'];
        $_SESSION['otp_code'] = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['otp_expires'] = time() + OTP_TTL_SECONDS;

        $sent = send_otp_email($email, $_SESSION['otp_code']);
        add_log("OTP resent for {$role} to {$email}.");

        trigger_toast('Verification code resent to your email.');
        header('Location: index.php?page=login&step=verify&role=' . urlencode($role));
        exit;
    }

    if ($action === 'logout') {
        $cur = get_current_user_profile();
        if ($cur) {
            add_log(display_name($cur) . " signed out from the current portal session.");
        }
        unset($_SESSION['user']);
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        trigger_toast("Signed out securely. PHP Session connection closed.");
        header('Location: index.php?page=login');
        exit;
    }

    // Submit Attendance (Faculty Role)
    if ($action === 'submit_attendance') {
        require_login();
        $course_code = isset($_POST['course_code']) ? $_POST['course_code'] : '';
        $attendance_states = isset($_POST['attendance']) ? $_POST['attendance'] : []; // [rollNo => state]
        
        $students = get_table(STUDENTS_FILE);
        $courses = get_table(COURSES_FILE);
        
        // Mock update attendance calculation
        foreach ($students as $key => $st) {
            if (in_array($course_code, $st['enrolledSections'])) {
                $roll = $st['rollNo'];
                $state = isset($attendance_states[$roll]) ? $attendance_states[$roll] : 'Present';
                
                // Adjust roll percentage slightly as indicator
                if ($state === 'Present') {
                    $students[$key]['attendance'] = min(100.0, $st['attendance'] + 0.2);
                } else if ($state === 'Absent') {
                    $students[$key]['attendance'] = max(0.0, $st['attendance'] - 1.5);
                } else {
                    $students[$key]['attendance'] = max(0.0, $st['attendance'] - 0.4); // Late
                }
            }
        }
        save_table(STUDENTS_FILE, $students);

        // Update course compliance
        foreach ($courses as $key => $co) {
            if ($co['code'] === $course_code) {
                $courses[$key]['absenteeCount'] = count(array_filter($attendance_states, function($v) { return $v === 'Absent'; }));
                $courses[$key]['compliance'] = min(100, max(40, $co['compliance'] + rand(-2, 3)));
            }
        }
        save_table(COURSES_FILE, $courses);

        $faculty_user = get_current_user_profile();
        add_log(display_name($faculty_user) . " submitted an attendance sheet for {$course_code}.");
        trigger_toast("Attendance sheet compiled and locked into registrar log successfully!");
        header('Location: index.php?page=dashboard');
        exit;
    }

    // Course management actions for admin / faculty
    if ($action === 'create_course' || $action === 'update_course' || $action === 'delete_course') {
        require_login();
        $role = $_SESSION['user']['role'];
        if (!in_array($role, ['admin', 'faculty'], true)) {
            trigger_toast('Course management is restricted to administrative and faculty portals.');
            header('Location: index.php?page=timetable&sub=courses');
            exit;
        }

        $courses = get_table(COURSES_FILE);
        $students = get_table(STUDENTS_FILE);

        if ($action === 'delete_course') {
            $course_code = isset($_POST['course_code']) ? trim($_POST['course_code']) : '';
            if ($course_code === '') {
                trigger_toast('Course code is required for deletion.');
                header('Location: index.php?page=timetable&sub=courses');
                exit;
            }

            if ($role !== 'admin') {
                trigger_toast('Only administrators can remove course records.');
                header('Location: index.php?page=timetable&sub=courses');
                exit;
            }

            $deleted = false;
            foreach ($courses as $index => $course) {
                if (isset($course['code']) && $course['code'] === $course_code) {
                    unset($courses[$index]);
                    $deleted = true;
                    break;
                }
            }
            if ($deleted) {
                $courses = array_values($courses);
                save_table(COURSES_FILE, $courses);
                add_log(display_name(get_current_user_profile()) . " deleted course {$course_code}.");
                trigger_toast("Course {$course_code} was removed successfully.");
            } else {
                trigger_toast('Unable to delete course. The requested course was not found.');
            }

            header('Location: index.php?page=timetable&sub=courses');
            exit;
        }

        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $coordinator = isset($_POST['coordinator']) ? trim($_POST['coordinator']) : '';
        $schedule = isset($_POST['schedule']) ? trim($_POST['schedule']) : '';
        $rooms = isset($_POST['rooms']) ? trim($_POST['rooms']) : '';
        $total_hours = isset($_POST['total_hours']) ? intval($_POST['total_hours']) : 0;
        $enrolled_students = isset($_POST['enrolled_students']) ? (array) $_POST['enrolled_students'] : [];
        $original_code = isset($_POST['original_code']) ? strtoupper(trim($_POST['original_code'])) : '';

        if ($code === '' || $title === '') {
            trigger_toast('Course code and title are required.');
            header('Location: index.php?page=timetable&sub=courses');
            exit;
        }

        if ($action === 'create_course') {
            foreach ($courses as $course) {
                if (isset($course['code']) && strtoupper($course['code']) === $code) {
                    trigger_toast('Course code already exists. Please choose a unique identifier.');
                    header('Location: index.php?page=timetable&sub=courses');
                    exit;
                }
            }
            if ($role === 'faculty') {
                $coordinator = display_name(get_current_user_profile());
            }
            $course_status = $role === 'admin' ? 'Approved' : 'Pending Approval';
            $courses[] = [
                'code' => $code,
                'title' => $title,
                'coordinator' => $coordinator,
                'schedule' => $schedule,
                'rooms' => $rooms,
                'totalHours' => $total_hours,
                'compliance' => 100,
                'absenteeCount' => 0,
                'enrolledStudents' => array_values($enrolled_students),
                'status' => $course_status,
                'submittedBy' => display_name(get_current_user_profile()),
                'submittedRole' => $role,
            ];
            add_log(display_name(get_current_user_profile()) . " created course {$code} with status {$course_status}.");
            trigger_toast("Course {$code} was added successfully.");
        } else {
            $updated = false;
            foreach ($courses as $index => $course) {
                if (isset($course['code']) && strtoupper($course['code']) === ($original_code ?: $code)) {
                    if ($role === 'faculty' && isset($course['coordinator']) && $course['coordinator'] !== display_name(get_current_user_profile())) {
                        trigger_toast('You can only update courses you are coordinating.');
                        header('Location: index.php?page=timetable&sub=courses');
                        exit;
                    }
                    $new_status = $course['status'] ?? 'Pending Approval';
                    if ($role === 'faculty') {
                        $new_status = 'Pending Approval';
                        $coordinator = display_name(get_current_user_profile());
                    }
                    if ($role === 'admin') {
                        $new_status = $course['status'] ?? 'Approved';
                    }
                    $courses[$index] = [
                        'code' => $code,
                        'title' => $title,
                        'coordinator' => $coordinator,
                        'schedule' => $schedule,
                        'rooms' => $rooms,
                        'totalHours' => $total_hours,
                        'compliance' => isset($course['compliance']) ? $course['compliance'] : 100,
                        'absenteeCount' => isset($course['absenteeCount']) ? $course['absenteeCount'] : 0,
                        'enrolledStudents' => array_values($enrolled_students),
                        'status' => $new_status,
                        'submittedBy' => display_name(get_current_user_profile()),
                        'submittedRole' => $role,
                    ];
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                trigger_toast('Unable to find the course to update.');
                header('Location: index.php?page=timetable&sub=courses');
                exit;
            }
            add_log(display_name(get_current_user_profile()) . " updated course {$code}.");
            trigger_toast("Course {$code} has been updated.");
        }

        save_table(COURSES_FILE, $courses);

        foreach ($students as $index => $student) {
            $students[$index]['enrolledSections'] = isset($student['enrolledSections']) && is_array($student['enrolledSections']) ? $student['enrolledSections'] : [];
            if (in_array($student['rollNo'], $enrolled_students, true)) {
                if (!in_array($code, $students[$index]['enrolledSections'], true)) {
                    $students[$index]['enrolledSections'][] = $code;
                }
            } else {
                $students[$index]['enrolledSections'] = array_values(array_filter($students[$index]['enrolledSections'], function ($course_code) use ($code) {
                    return $course_code !== $code;
                }));
            }

            if ($original_code && $original_code !== $code) {
                $students[$index]['enrolledSections'] = array_values(array_map(function ($course_code) use ($original_code, $code) {
                    return $course_code === $original_code ? $code : $course_code;
                }, $students[$index]['enrolledSections']));
            }
        }
        save_table(STUDENTS_FILE, $students);

        header('Location: index.php?page=timetable&sub=courses');
        exit;
    }

    if ($action === 'approve_course') {
        require_login();
        $role = $_SESSION['user']['role'];
        if ($role !== 'admin') {
            trigger_toast('Only administrators can approve course proposals.');
            header('Location: index.php?page=timetable&sub=courses');
            exit;
        }

        $course_code = isset($_POST['course_code']) ? trim($_POST['course_code']) : '';
        foreach ($courses as $index => $course) {
            if (isset($course['code']) && $course['code'] === $course_code) {
                $courses[$index]['status'] = 'Approved';
                $courses[$index]['approvedBy'] = display_name(get_current_user_profile());
                $courses[$index]['approvedAt'] = date('Y-m-d H:i:s');
                save_table(COURSES_FILE, $courses);
                add_log(display_name(get_current_user_profile()) . " approved course proposal {$course_code}.");
                trigger_toast("Course {$course_code} has been approved.");
                break;
            }
        }
        header('Location: index.php?page=timetable&sub=courses');
        exit;
    }

    // Process Leave Request (Faculty Role)
    if ($action === 'update_leave') {
        require_login();
        $leave_id = isset($_GET['id']) ? $_GET['id'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : 'Pending'; // Approved, Rejected
        
        $leaves = get_table(LEAVES_FILE);
        $affected_name = 'Student';
        foreach ($leaves as $key => $lv) {
            if ($lv['id'] === $leave_id) {
                $leaves[$key]['status'] = $status;
                $affected_name = $lv['studentName'];
                break;
            }
        }
        save_table(LEAVES_FILE, $leaves);

        $faculty_user = get_current_user_profile();
        add_log(display_name($faculty_user) . " marked leave request of {$affected_name} as {$status}.");
        trigger_toast("Leave request set to '{$status}' successfully!");
        header('Location: index.php?page=dashboard');
        exit;
    }

    // Submit Leave Request (Student Role)
    if ($action === 'add_leave') {
        require_login();
        $type = isset($_POST['type']) ? $_POST['type'] : 'General Sick Leave';
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        
        $student = get_current_user_profile();
        $leaves = get_table(LEAVES_FILE);
        
        $new_leave = [
            'id' => 'lv-' . time(),
            'studentName' => display_name($student),
            'studentAvatar' => isset($student['avatar']) ? $student['avatar'] : '',
            'rollNo' => isset($student['rollNo']) ? $student['rollNo'] : '',
            'type' => $type,
            'date' => $date,
            'reason' => $reason,
            'status' => 'Pending'
        ];
        array_unshift($leaves, $new_leave);
        save_table(LEAVES_FILE, $leaves);

        add_log(display_name($student) . " filed a {$type} request scheduled for {$date}.");
        trigger_toast("Leave request recorded. System is processing designated faculty approval queues.");
        header('Location: index.php?page=timetable&sub=leave');
        exit;
    }

    // Generate Report File
    if ($action === 'generate_report') {
        require_login();
        $title = isset($_POST['title']) ? $_POST['title'] : 'Custom Generated Report';
        $type = isset($_POST['format']) ? $_POST['format'] : 'PDF Documents';
        
        $reports = get_table(REPORTS_FILE);
        $user = get_current_user_profile();
        
        $new_report = [
            'id' => 'rep-' . time(),
            'title' => $title,
            'type' => $type,
            'generatedBy' => display_name($user),
            'generatedAt' => date('Y-m-d H:i:s'),
            'status' => 'Completed',
            'fileSize' => number_format(rand(10, 50)/10, 1) . ' MB'
        ];
        array_unshift($reports, $new_report);
        save_table(REPORTS_FILE, $reports);

        add_log(display_name($user) . " generated report: {$title}");
        trigger_toast("Academic report compiled and added to the archive.");
        header('Location: index.php?page=reports');
        exit;
    }

    // Save Profile Configuration
    if ($action === 'update_profile') {
        require_login();
        $users = get_table(USERS_FILE);
        $role = $_SESSION['user']['role'];

        $profile_name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $profile_email = isset($_POST['email']) ? trim($_POST['email']) : '';
        if ($profile_name === '' || $profile_email === '') {
            trigger_toast('Please complete your name and email before continuing.');
            header('Location: index.php?page=settings&sub=profile');
            exit;
        }
        
        if (isset($users[$role])) {
            $users[$role]['name'] = $profile_name;
            $users[$role]['email'] = $profile_email;
            $users[$role]['designation'] = isset($_POST['designation']) ? trim($_POST['designation']) : $users[$role]['designation'];
            $users[$role]['department'] = isset($_POST['department']) ? trim($_POST['department']) : $users[$role]['department'];
            $users[$role]['bio'] = isset($_POST['bio']) ? trim($_POST['bio']) : $users[$role]['bio'];
            
            save_table(USERS_FILE, $users);
            // Refresh session
            $_SESSION['user'] = $users[$role];
            $_SESSION['user']['role'] = $role;

            add_log(display_name($users[$role]) . " updated profile details.");
            trigger_toast("Academic session registration records modified successfully!");
        }

        if ($role === 'student' && isset($_SESSION['user']['rollNo'])) {
            save_student_profile([ 
                'rollNo' => $_SESSION['user']['rollNo'],
                'name' => $profile_name,
                'email' => $profile_email,
                'department' => isset($_POST['department']) ? trim($_POST['department']) : '',
                'designation' => isset($_POST['designation']) ? trim($_POST['designation']) : '',
                'bio' => isset($_POST['bio']) ? trim($_POST['bio']) : '',
            ]);
        }
    }

    // Toggle Preferences / Dark Mode
    if ($action === 'toggle_pref') {
        require_login();
        $pref = isset($_GET['pref']) ? $_GET['pref'] : '';
        if ($pref === 'dark') {
            $_SESSION['dark_theme'] = !isset($_SESSION['dark_theme']) || $_SESSION['dark_theme'] == false;
            $theme_label = $_SESSION['dark_theme'] ? "Comfort Dark" : "Pristine Light";
            trigger_toast("Visual skin toggled to {$theme_label}");
        } else if ($pref === 'push') {
            $_SESSION['push_alerts'] = !isset($_SESSION['push_alerts']) || $_SESSION['push_alerts'] == false;
            $push_label = $_SESSION['push_alerts'] ? "ON" : "OFF";
            trigger_toast("Push notifications: {$push_label}");
        } else if ($pref === 'email') {
            $_SESSION['email_alerts'] = !isset($_SESSION['email_alerts']) || $_SESSION['email_alerts'] == false;
            $email_label = $_SESSION['email_alerts'] ? "ON" : "OFF";
            trigger_toast("Daily rollups: {$email_label}");
        }
        header('Location: index.php?page=settings');
        exit;
    }
}

// Redirect to login if user session not registered (and page is not 'login')
if ($page !== 'login') {
    require_login();
}

$current_user = get_current_user_profile();
if ($current_user && !is_profile_complete($current_user)) {
    $needs_profile_page = $page === 'settings' && isset($_GET['sub']) && $_GET['sub'] === 'profile';
    if (!$needs_profile_page) {
        trigger_toast('Complete your profile before continuing.');
        header('Location: index.php?page=settings&sub=profile');
        exit;
    }
}
$is_dark = isset($_SESSION['dark_theme']) && $_SESSION['dark_theme'] == true;
$toast_msg = pull_toast();

// Include Layout Views
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $is_dark ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendly Academic Portal (PHP)</title>
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            850: '#1e293b',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Web Fonts & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @keyframes scaleUp {
            0% { transform: translate(-50%, -20px) scale(0.95); opacity: 0; }
            100% { transform: translate(-50%, 0) scale(1); opacity: 1; }
        }
        .toast-animate {
            animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100 min-h-screen">

    <!-- Toast Notification Overlay -->
    <?php if ($toast_msg): ?>
    <div id="toast" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 toast-animate">
        <div class="bg-slate-900 border border-slate-800 text-white rounded-2xl px-6 py-3.5 shadow-2xl flex items-center gap-3.5 max-w-md backdrop-blur-sm">
            <span class="material-symbols-outlined text-blue-500 text-xl">info</span>
            <span class="text-xs font-semibold leading-relaxed"><?php echo htmlspecialchars($toast_msg); ?></span>
            <button onclick="document.getElementById('toast').style.display='none';" class="text-slate-400 hover:text-white ml-2 text-base material-symbols-outlined select-none cursor-pointer">close</button>
        </div>
    </div>
    <script>
        setTimeout(function() {
            var el = document.getElementById('toast');
            if(el) el.style.display = 'none';
        }, 5000);
    </script>
    <?php endif; ?>

    <?php if ($page === 'login'): ?>
        <!-- Render login layout -->
        <?php include_once __DIR__ . '/login.php'; ?>
    <?php else: ?>
        
        <!-- Standard Responsive Role-Based Layout Structure -->
        <div class="flex relative min-h-screen">
            
            <!-- Side Navigation Rail (Desktop) -->
            <aside class="hidden md:flex w-64 bg-white dark:bg-slate-900 border-r border-slate-150 dark:border-slate-800 flex-col justify-between fixed top-0 bottom-0 left-0 z-30">
                <div class="p-5 flex flex-col gap-6">
                    <!-- Brand Title logo -->
                    <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center overflow-hidden shrink-0 shadow-lg shadow-blue-500/15">
                            <span class="material-symbols-outlined text-white text-xl">fingerprint</span>
                        </div>
                        <div>
                            <span class="text-slate-900 dark:text-white font-extrabold tracking-tight text-lg leading-none block">Attendly</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block mt-0.5">PHP Enterprise</span>
                        </div>
                    </div>

                    <!-- Current Profile Details -->
                    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <?php echo avatar_markup($current_user, 'w-10 h-10 rounded-full border border-slate-200 dark:border-slate-800'); ?>
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-slate-900 dark:text-white block truncate leading-tight"><?php echo h(display_name($current_user)); ?></span>
                            <span class="text-[9px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest block mt-0.5"><?php echo h(strtoupper(role_display_name($current_user['role']))); ?></span>
                        </div>
                    </div>

                    <!-- Navigation Sidebar Lists -->
                    <nav class="flex flex-col gap-1.5 pt-2 select-none">
                        <?php 
                        $nav_items = [];
                        $nav_items[] = ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'];
                        
                        if ($current_user['role'] === 'faculty' || $current_user['role'] === 'admin') {
                            $nav_items[] = ['id' => 'attendance', 'label' => $current_user['role'] === 'faculty' ? 'Class Sheets' : 'Audits Team', 'icon' => 'fact_check'];
                        }
                        
                        $nav_items[] = ['id' => 'timetable', 'label' => $current_user['role'] === 'faculty' ? 'Faculty Schedule' : ($current_user['role'] === 'admin' ? 'Registrar Schedule' : 'Weekly Schedule'), 'icon' => 'calendar_month'];
                        
                        // Timetable leaf has a sub-tab in routing
                        $nav_items[] = ['id' => 'leave', 'label' => 'Leave Requests', 'icon' => 'pending_actions'];
                        
                        if ($current_user['role'] === 'admin') {
                            $nav_items[] = ['id' => 'reports', 'label' => 'PDF Reports', 'icon' => 'summarize'];
                        }
                        
                        $nav_items[] = ['id' => 'settings', 'label' => 'Profile Setup', 'icon' => 'manage_accounts'];

                        foreach ($nav_items as $item):
                            // Highlight check depending on requested page
                            $is_active = ($page === $item['id']) || ($item['id'] === 'leave' && $page === 'timetable' && isset($_GET['sub']) && $_GET['sub'] === 'leave');
                            if($item['id'] === 'timetable' && $page === 'timetable' && isset($_GET['sub']) && $_GET['sub'] === 'leave') {
                                $is_active = false;
                            }
                            // Form href target depending on tabs
                            $href = "index.php?page={$item['id']}";
                            if($item['id'] === 'leave') {
                                $href = "index.php?page=timetable&sub=leave";
                            }
                        ?>
                        <a
                            href="<?php echo $href; ?>"
                            class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl text-xs font-bold transition-all relative <?php echo $is_active ? 'bg-blue-600 text-white shadow-md shadow-blue-600/10' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'; ?>"
                        >
                            <?php if ($is_active): ?>
                                <span class="absolute left-1 w-1 h-4 bg-white rounded-full"></span>
                            <?php endif; ?>
                            <span class="material-symbols-outlined text-base"><?php echo $item['icon']; ?></span>
                            <?php echo $item['label']; ?>
                        </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Footer Logouts -->
                <div class="p-5 border-t border-slate-100 dark:border-slate-800 select-none">
                    <a
                        href="index.php?action=logout"
                        class="w-full flex items-center justify-center bg-slate-50 hover:bg-red-50 hover:text-red-700 dark:bg-slate-950/30 dark:hover:bg-red-950/20 text-slate-500 dark:text-slate-400 font-semibold py-2.5 px-4 rounded-xl text-xs border border-slate-150 dark:border-slate-800 transition-colors gap-2"
                    >
                        <span class="material-symbols-outlined text-base">logout</span>
                        Sign Out Securely
                    </a>
                </div>
            </aside>

            <!-- Mobile Navbar Drawer Header (Visible on <md Only) -->
            <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white dark:bg-slate-900 border-b border-slate-150 dark:border-slate-800 z-40 px-4 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center overflow-hidden shadow">
                        <span class="material-symbols-outlined text-white text-base">fingerprint</span>
                    </div>
                    <span class="font-extrabold text-slate-900 dark:text-white text-base tracking-tight">Attendly</span>
                </div>

                <div class="flex items-center gap-3">
                    <?php echo avatar_markup($current_user, 'w-7 h-7 rounded-full border border-slate-200'); ?>
                    <!-- Drawer Mobile Toggle Button -->
                    <button
                        onclick="document.getElementById('mobile-drawer').classList.toggle('hidden');"
                        class="w-9 h-9 rounded-lg bg-slate-50 text-slate-700 border border-slate-150 flex items-center justify-center cursor-pointer select-none"
                    >
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                </div>
            </div>

            <!-- Slide down Mobile Drawer Overlay View -->
            <div id="mobile-drawer" class="hidden md:hidden fixed inset-0 bg-slate-950/60 z-30 pt-16 flex select-none">
                <div class="w-64 bg-white dark:bg-slate-900 border-r border-slate-150 dark:border-slate-800 h-full p-4 flex flex-col justify-between">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2.5 p-2 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-100">
                            <?php echo avatar_markup($current_user, 'w-8 h-8 rounded-full'); ?>
                            <div>
                                <span class="text-xs font-bold text-slate-800 dark:text-white block truncate leading-none mb-0.5"><?php echo h(display_name($current_user)); ?></span>
                                <span class="text-[9px] font-bold text-indigo-650 uppercase tracking-wider block"><?php echo h(strtoupper(role_display_name($current_user['role']))); ?></span>
                            </div>
                        </div>

                        <nav class="flex flex-col gap-1">
                            <?php foreach ($nav_items as $item): 
                                $is_active = ($page === $item['id']) || ($item['id'] === 'leave' && $page === 'timetable' && isset($_GET['sub']) && $_GET['sub'] === 'leave');
                                if($item['id'] === 'timetable' && $page === 'timetable' && isset($_GET['sub']) && $_GET['sub'] === 'leave') {
                                    $is_active = false;
                                }
                                $href = "index.php?page={$item['id']}";
                                if($item['id'] === 'leave') {
                                    $href = "index.php?page=timetable&sub=leave";
                                }
                            ?>
                            <a
                                href="<?php echo $href; ?>"
                                class="flex items-center gap-3 py-2 px-3 rounded-lg text-xs font-bold <?php echo $is_active ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-50 dark:text-slate-400'; ?>"
                            >
                                <span class="material-symbols-outlined text-base"><?php echo $item['icon']; ?></span>
                                <?php echo $item['label']; ?>
                            </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>

                    <a
                        href="index.php?action=logout"
                        class="w-full flex items-center justify-center gap-2 bg-slate-50 hover:bg-red-50 hover:text-red-700 font-bold py-2.5 px-3 rounded-lg text-xs border border-slate-150"
                    >
                        <span class="material-symbols-outlined text-sm">logout</span>
                        Sign Out
                    </a>
                </div>
                <!-- Backdrop handler -->
                <div class="flex-1" onclick="document.getElementById('mobile-drawer').classList.add('hidden');"></div>
            </div>

            <!-- Main Portal Workspace Area -->
            <main class="flex-1 md:pl-64 pt-16 md:pt-0 overflow-y-auto min-h-screen">
                <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6">
                    
                    <?php 
                    // Dynamic Page Controller Routing
                    switch ($page) {
                        case 'dashboard':
                            include_once __DIR__ . '/dashboard.php';
                            break;
                            
                        case 'attendance':
                            include_once __DIR__ . '/attendance.php';
                            break;
                            
                        case 'timetable':
                            include_once __DIR__ . '/timetable.php';
                            break;
                            
                        case 'reports':
                            if ($current_user['role'] === 'admin') {
                                include_once __DIR__ . '/reports.php';
                            } else {
                                echo '<div class="bg-white border border-slate-100 p-8 rounded-2xl text-center italic text-slate-400">View strictly restricted to Administrative teams and Course Registrars.</div>';
                            }
                            break;
                            
                        case 'settings':
                            include_once __DIR__ . '/settings.php';
                            break;

                        default:
                            include_once __DIR__ . '/dashboard.php';
                            break;
                    }
                    ?>

                </div>
            </main>

        </div>

    <?php endif; ?>

</body>
</html>
