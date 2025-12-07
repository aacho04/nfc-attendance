<?php
include '../includes/db.php';
include '../includes/functions.php';
if (!is_teacher()) redirect('../login.php');
$rightSidebar=true;

// Fetch username from database using user_id
$username = 'Unknown Teacher'; // Default fallback
try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $username = htmlspecialchars($user['username']);
    }
} catch (PDOException $e) {
    error_log("Error fetching username: " . $e->getMessage());
}

// Fetch attendance data for graphs with class filter
$lecture_data = [];
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';
$teacher_id = $_SESSION['user_id'];
try {
    $query = "SELECT l.id AS lecture_id, l.start_time, c.name AS class_name, COUNT(att.id) AS attended, 
              (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) AS total_students
              FROM teachers t
              JOIN assignments a ON t.id = a.teacher_id
              JOIN classes c ON a.class_id = c.id
              JOIN lectures l ON a.id = l.assignment_id
              LEFT JOIN attendances att ON l.id = att.lecture_id
              WHERE t.user_id = ? AND l.end_time IS NOT NULL";
    $params = [$teacher_id];
    if ($selected_class) {
        $query .= " AND c.id = ?";
        $params[] = $selected_class;
    }
    $query .= " GROUP BY l.id, l.start_time, c.name, c.id
                ORDER BY l.start_time DESC
                LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $lectures = $stmt->fetchAll();
    foreach ($lectures as $lecture) {
        $attendance_rate = $lecture['total_students'] > 0 ? round(($lecture['attended'] / $lecture['total_students']) * 100, 2) : 0;
        $lecture_data[] = [
            'label' => "Lecture " . $lecture['lecture_id'] . " (" . date('Y-m-d', strtotime($lecture['start_time'])) . ")",
            'class' => $lecture['class_name'],
            'attendance' => $attendance_rate
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching lecture data: " . $e->getMessage());
    $lecture_data = []; // Ensure it's an array even on error
}
$all_lectures = [];
try {
    $query_all = "SELECT 
                    l.id AS lecture_id,
                    l.start_time,
                    c.name AS class_name,
                    COUNT(att.id) AS attended,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) AS total_students
                  FROM teachers t
                  JOIN assignments a ON t.id = a.teacher_id
                  JOIN classes c ON a.class_id = c.id
                  JOIN lectures l ON a.id = l.assignment_id
                  LEFT JOIN attendances att ON l.id = att.lecture_id
                  WHERE t.user_id = ? AND l.end_time IS NOT NULL
                  GROUP BY l.id, l.start_time, c.name, c.id
                  ORDER BY l.start_time ASC";   // oldest → newest for line chart

    $stmt_all = $pdo->prepare($query_all);
    $stmt_all->execute([$teacher_id]);

    $rows_all = $stmt_all->fetchAll();

    foreach ($rows_all as $lecture) {
        $attendance_rate = $lecture['total_students'] > 0
            ? round(($lecture['attended'] / $lecture['total_students']) * 100, 2)
            : 0;

        $all_lectures[] = [
            'label' => "Lecture {$lecture['lecture_id']} (" . date('Y-m-d', strtotime($lecture['start_time'])) . ")",
            'class' => $lecture['class_name'],
            'attendance' => $attendance_rate
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching all lecture data: " . $e->getMessage());
    $all_lectures = [];
}

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* -----------------------------
   RESPONSIVE FIXES (MOBILE/TABLET)
--------------------------------- */

/* 1) FULL CONTAINER SHOULD NOT OVERFLOW */
.container {
    max-width: 100%;
    padding: 0 15px;
}

/* 2) RIGHT SLIDE SIDEBAR BEHAVES LIKE PANEL ON MOBILE */
@media (max-width: 1024px) {
    .rightslide aside {
        position: relative;
        width: 100%;
        height: auto;
        border-radius: 20px;
        margin-top: 20px;
    }
}

/* 3) ON SMALL SCREENS — SIDEBAR GOES BELOW HEADER */
@media (max-width: 768px) {
    .rightslide {
        position: relative;
        width: 100%;
    }

    .rightslide aside {
        position: relative;
        top: 0;
        right: 0;
        width: 100% !important;
        height: auto !important;
        border-radius: 20px;
        margin-top: 20px;
    }
}

/* 4) DASHBOARD GRID = 1 COLUMN ON SMALL DEVICES */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr !important;
    }
}

/* 5) QUICK ACTIONS → STACK CLEANLY */
@media (max-width: 768px) {
    .quick-actions {
        grid-template-columns: 1fr !important;
        gap: 15px;
    }
}

/* 6) STATS CARDS → 2 PER ROW ON SMALL DEVICES */
@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr !important;
    }
}

/* 7) CHARTS AUTO SCALE */
.chart-container {
    height: auto;
    min-height: 250px;
}

/* 8) HEADER RESPONSIVE */
@media (max-width: 768px) {
    header {
        flex-direction: column;
        height: auto !important;
        padding: 25px 15px !important;
        text-align: center;
    }
}

        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
              padding-top: 0 ;
            padding-right: 20px;
            padding-left:20px;
            padding-bottom:20px;
        }

        /* Header Styles */
        /* header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 0;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }  */


        .welcome-section h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
        }

        .welcome-section p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            
        }

        .action-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .action-card p {
            color: var(--gray);
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn:hover {
            background: var(--secondary);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: var(--dark);
        }

        .card-header a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Chart Container */
        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Recent Activity */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .activity-content p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .activity-time {
            color: var(--gray);
            font-size: 0.8rem;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        /* ================================
   MOBILE + TABLET RESPONSIVE FIX
   (SAFE — DOES NOT BREAK LAYOUT)
=================================== */

/* 1) HEADER: shrink instead of vanish */
@media (max-width: 640px) {
    header {
        height: auto !important;
        padding: 15px !important;
        flex-direction: column !important;
        gap: 6px !important;
        text-align: center !important;
    }

    header h1 {
        font-size: 1.4rem !important;
    }

    header p {
        font-size: 0.9rem !important;
    }

    header button {
        padding: 6px 14px !important;
        font-size: 0.75rem !important;
    }
}

/* 2) QUICK ACTION CARDS move upward (reduce space) */
@media (max-width: 768px) {
    .quick-actions {
        margin-top: 10px !important;
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }

    .action-card {
        padding: 16px !important;
    }

    .action-card h3 {
        font-size: 1rem !important;
    }

    .action-card p {
        font-size: 0.85rem !important;
    }
}

/* 3) FIX GAP between header & quick actions */
@media (max-width: 768px) {
    .container {
        padding-top: 10px !important;
    }
}

/* 4) RIGHT SIDEBAR — shrink & move BELOW dashboard */
@media (max-width: 1024px) {
    .rightslide aside {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        margin-top: 15px !important;
        border-radius: 20px !important;
    }

    .rightslide {
        width: 100% !important;
        position: relative !important;
        margin-top: 5px !important;
        padding: 0 !important;
    }
}

/* On mobile, sidebar content becomes compact */
@media (max-width: 640px) {
    .rightslide aside {
        padding: 12px !important;
    }

    .rightslide h2 {
        font-size: 1rem !important;
    }
}

/* 5) DASHBOARD GRID: stacked layout but no huge gaps */
@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr !important;
        gap: 15px !important;
    }
}

/* 6) STATS — smaller cards and tighter spacing */
@media (max-width: 768px) {
    .stats-container {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }

    .stat-card {
        padding: 12px !important;
    }

    .stat-card h3 {
        font-size: 1.2rem !important;
    }

    .stat-card p {
        font-size: 0.75rem !important;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr !important;
    }
}

/* 7) CHARTS: auto shrink — no overflow */
.chart-container {
    height: auto !important;
    min-height: 200px !important;
}
/* ------- RIGHT SIDEBAR RESPONSIVE FIX ------- */

/* Desktop (keep fixed) */
.rightslide aside {
    position: fixed;
    right: 0;
    top: 0;
    width: 18rem; /* 72 tailwind */
    height: 100vh;
}

/* Tablet & Mobile — disable fixed sidebar */
@media (max-width: 1024px) {
    .rightslide aside {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        border-radius: 20px !important;
        margin-top: 15px !important;
        right: unset !important;
        top: unset !important;
    }

    /* ensure rightslide wrapper stacks normally */
    .rightslide {
        width: 100% !important;
        position: relative !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}

/* Make the inside elements shrink nicely */
/* -------------------------
   RIGHT SIDEBAR MOBILE FIX
   ------------------------- */

/* Tablet and Mobile */
@media (max-width: 1024px) {
    .rightslide {
        display: flex;
        justify-content: center;     /* centers the sidebar */
        width: 100% !important;
        padding: 0 20px;             /* spacing on left/right */
        box-sizing: border-box;
        margin-top: 20px !important;
    }

    .rightslide aside {
        position: relative !important;
        width: 90% !important;       /* shrink the width */
        max-width: 420px !important; /* card size */
        height: auto !important;
        border-radius: 20px !important;
        padding: 16px !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08); /* soft shadow */
    }
}

/* Small screens */
@media (max-width: 640px) {
    .rightslide aside {
        width: 100% !important;     /* fill width but still centered */
        max-width: 380px !important;
        padding: 12px !important;
    }
}









    </style>
</head>
<body>
   <div class="container  ">

    <header class="bg-gradient-to-r from-violet-600 to-violet-500 rounded-3xl shadow-lg p-8 flex justify-between items-center relative overflow-hidden h-48">

        <!-- Decorative Glow (reduced size) -->
        <div class="absolute inset-0 opacity-20 pointer-events-none">
            <div class="absolute top-6 left-10 w-16 h-16 bg-white/20 rounded-full blur-2xl"></div>
            <div class="absolute bottom-6 right-6 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
        </div>

        <!-- Left Section -->
        <div class="relative z-10">
           

            <h1 class="text-3xl font-bold text-white leading-snug">
                Teacher Dashboard
            </h1>

            <p class="text-white/90 text-base mt-1">
                Welcome back, 
                <span id="username" class="font-semibold">
                    <?php echo isset($username) ? $username : 'Teacher'; ?>
                </span>!
            </p>

            <button class="mt-4 bg-black text-white px-5 py-2 rounded-full flex items-center space-x-2 hover:bg-gray-900 transition text-sm">
                <span>Join Now</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
        </header>
<div class="rightslide">
    <aside class="fixed right-0 top-0 h-full w-72 bg-white shadow-xl rounded-l-3xl p-6 flex flex-col space-y-6 overflow-y-auto z-50">

        <!-- SUBJECT INFO -->
        <div class="relative bg-white/30 backdrop-blur-lg px-5 py-3 rounded-2xl shadow flex items-center space-x-3 border border-white/20">
            <div class="avatar bg-white/40 p-2 rounded-full text-purple-700 text-xl shadow-sm">
                <i class="fas fa-book"></i>
            </div>

            <div class="leading-tight text-left w-full">
                <div class="font-bold text-gray-900 text-base">
                    Subject:
                    <span class="font-medium text-gray-700 text-sm ml-1">Subject Info</span>
                </div>
            </div>
        </div>


        <!-- CIRCLE AVATAR WITH ATTENDANCE RING -->
        <div class="flex flex-col items-center justify-center">

            <?php 
                if (!empty($lecture_data)) {
                    $total_attendance = 0;
                    foreach ($lecture_data as $lecture) {
                        $total_attendance += $lecture['attendance'];
                    }
                    $avg = round($total_attendance / count($lecture_data), 1);
                } else {
                    $avg = 0;
                }
                $dash = $avg * 2.64;
            ?>

            <!-- Reduced distance by shrinking wrapper slightly -->
   <div class="relative flex items-center justify-center" style="width:112px; height:112px;">
    <svg class="absolute inset-0 transform -rotate-90" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="42" stroke="#e5e7eb" stroke-width="5" fill="none"></circle>
        <circle 
            cx="50" cy="50" r="42"
            stroke="#7c3aed"
            stroke-width="5"
            fill="none"
            stroke-dasharray="264"
            stroke-dashoffset="<?php echo 264 - $dash; ?>"
            stroke-linecap="round"
        ></circle>
    </svg>

    <!-- White background image, tighter gap -->
    <div class="w-16 h-16 rounded-full bg-white p-[1.5px] shadow z-10 flex items-center justify-center">
        <div class="w-full h-full rounded-full overflow-hidden">
            <img src="../image/teacher1.png" alt="Teacher" class="w-full h-full object-cover">
        </div>
    </div>
</div>






            <div class="text-center mt-2">
                <h3 class="text-2xl font-semibold text-gray-900"><?php echo $avg; ?>%</h3>
                <p class="text-gray-500 text-sm">Average Attendance</p>
            </div>
        </div>


        <!-- TEACHER INFO -->
        <div class="relative bg-white/30 backdrop-blur-lg px-5 py-3 rounded-2xl shadow-sm flex items-center space-x-3 border border-white/20">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-white/40 shadow flex items-center justify-center">
                <img src="../image/teacher1.png" alt="Teacher" class="w-full h-full object-cover">
            </div>

            <div class="leading-tight">
                <div id="username-display" class="font-bold text-gray-900 text-base">
                    <?php echo isset($username) ? $username : 'Teacher'; ?>
                </div>
                <div class="text-gray-600 text-xs">Teacher</div>
            </div>
        </div>


        <!-- ATTENDANCE CHART -->
        <div class="bg-white rounded-2xl shadow p-4">
            <h2 class="font-bold text-gray-800 mb-3">Attendance Overview</h2>

            <div class="h-44 p-1">
                <canvas id="attendanceChart" class="w-full h-full"></canvas>
            </div>
        </div>

    </aside>
</div>

       
        <!-- Right Side -->
         
                

    
         <!-- Quick Actions -->
     <!-- <div class="quick-actions">
            <div class="action-card" onclick="location.href='../teacher/start_lecture.php'">
                <i class="fas fa-play-circle"></i>
                <h3>Start Lecture</h3>
                <p>Begin a new lecture session for your class</p>
                <span class="btn">Start Now</span>
            </div>
            <div class="action-card" onclick="location.href='../teacher/end_lecture.php'">
                <i class="fas fa-stop-circle"></i>
                <h3>End Lecture</h3>
                <p>Conclude the current lecture session</p>
                <span class="btn">End Now</span>
            </div>
            <div class="action-card" onclick="location.href='../student/mark_attendance.php'">
                <i class="fas fa-clipboard-check"></i>
                <h3>Mark Attendance</h3>
                <p>Take attendance for your current lecture</p>
                <span class="btn">Mark Now</span>
            </div>
        </div>

</div> -->
<div class="quick-actions grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">

    <!-- ACTION CARD -->
    <div onclick="location.href='../teacher/start_lecture.php'"
         class="action-card cursor-pointer bg-white rounded-2xl shadow-md p-6 text-center 
                hover:shadow-xl transition-all duration-300 border border-gray-100 group">

        <div class="text-purple-600 text-4xl mb-3 group-hover:scale-110 transition">
    <i class="fas fa-play-circle"></i>
</div>

        <h3 class="text-lg font-semibold text-gray-900">Start Lecture</h3>
        <p class="text-gray-500 text-sm mt-1">Begin a new lecture session for your class</p>

        <span class="inline-block mt-4 bg-purple-600 text-white px-4 py-1.5 rounded-xl text-sm font-medium 
                     group-hover:bg-purple-700 transition">
            Start Now
        </span>
    </div>


    <!-- ACTION CARD -->
    <div onclick="location.href='../teacher/end_lecture.php'"
         class="action-card cursor-pointer bg-white rounded-2xl shadow-md p-6 text-center
                hover:shadow-xl transition-all duration-300 border border-gray-100 group">

        <div class="text-red-500 text-4xl mb-3 group-hover:scale-110 transition">
            <i class="fas fa-stop-circle"></i>
        </div>

        <h3 class="text-lg font-semibold text-gray-900">End Lecture</h3>
        <p class="text-gray-500 text-sm mt-1">Conclude the current lecture session<br><br></p>

        <span class="inline-block mt-4 bg-red-500 text-white px-4 py-1.5 rounded-xl text-sm font-medium 
                     group-hover:bg-red-600 transition">
            End Now
        </span>
    </div>


    <!-- ACTION CARD -->
    <div onclick="location.href='../student/mark_attendance.php'"
         class="action-card cursor-pointer bg-white rounded-2xl shadow-md p-6 text-center
                hover:shadow-xl transition-all duration-300 border border-gray-100 group">

        <div class="text-green-600 text-4xl mb-3 group-hover:scale-110 transition">
            <i class="fas fa-clipboard-check"></i>
        </div>

        <h3 class="text-lg font-semibold text-gray-900">Mark Attendance</h3>
        <p class="text-gray-500 text-sm mt-1">Take attendance for your current lecture</p>

        <span class="inline-block mt-4 bg-green-600 text-white px-4 py-1.5 rounded-xl text-sm font-medium 
                     group-hover:bg-green-700 transition">
            Mark Now
        </span>
    </div>

</div>





       
       

        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary);">
                    <i class="fas fa-chalkboard"></i>
                </div>
                <div class="stat-info">
                    <h3 id="total-lectures"><?php echo count($lecture_data); ?></h3>
                    <p>Total Lectures</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(76, 201, 240, 0.1); color: var(--success);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3 id="avg-attendance">
                        <?php 
                        if (!empty($lecture_data)) {
                            $total_attendance = 0;
                            foreach ($lecture_data as $lecture) {
                                $total_attendance += $lecture['attendance'];
                            }
                            echo round($total_attendance / count($lecture_data), 1) . '%';
                        } else {
                            echo '0%';
                        }
                        ?>
                    </h3>
                    <p>Average Attendance</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(248, 150, 30, 0.1); color: var(--warning);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3 id="total-students"><?php echo !empty($lecture_data) ? '142' : '0'; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(247, 37, 133, 0.1); color: var(--danger);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3 id="active-classes"><?php echo !empty($lecture_data) ? '4' : '0'; ?></h3>
                    <p>Active Classes</p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Attendance Chart -->
                <div class="card">
                    <div class="card-header">
                        <h2>Attendance Overview</h2>
                        <a href="../teacher/reports.php">View Full Report</a>
                    </div>
                    <div class="chart-container">
                        <?php if (empty($lecture_data)): ?>
                            <div class="no-data">
                                <i class="fas fa-chart-bar"></i>
                                <h3>No Data Available</h3>
                                <p>Start a lecture and mark attendance to see reports.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="attendanceDataAll"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Lectures -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Lectures</h2>
                        <a href="../teacher/lectures.php">View All</a>
                    </div>
                    <div class="chart-container">
                        <?php if (empty($lecture_data)): ?>
                            <div class="no-data">
                                <i class="fas fa-chalkboard"></i>
                                <h3>No Lectures Found</h3>
                                <p>Start your first lecture to see data here.</p>
                            </div>
                        <?php else: ?>
                            <canvas id="lecturesChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- Upcoming Schedule -->
                <div class="card">
                    <div class="card-header">
                        <h2>Today's Schedule</h2>
                        <a href="../teacher/schedule.php">Full Schedule</a>
                    </div>
                    <ul class="activity-list">
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Mathematics - Algebra</h4>
                                <p>10:00 AM - 11:30 AM</p>
                            </div>
                            <div class="activity-time">Room 204</div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: rgba(76, 201, 240, 0.1); color: var(--success);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Physics - Mechanics</h4>
                                <p>1:00 PM - 2:30 PM</p>
                            </div>
                            <div class="activity-time">Lab 3</div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: rgba(248, 150, 30, 0.1); color: var(--warning);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Office Hours</h4>
                                <p>3:00 PM - 4:30 PM</p>
                            </div>
                            <div class="activity-time">Office 302</div>
                        </li>
                    </ul>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Activity</h2>
                        <a href="../teacher/activity.php">See All</a>
                    </div>
                    <ul class="activity-list">
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: rgba(76, 201, 240, 0.1); color: var(--success);">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Attendance Marked</h4>
                                <p>Physics Lecture - 32 students present</p>
                            </div>
                            <div class="activity-time">2 hours ago</div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary);">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Lecture Started</h4>
                                <p>Mathematics - Algebra class</p>
                            </div>
                            <div class="activity-time">Yesterday</div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: rgba(247, 37, 133, 0.1); color: var(--danger);">
                                <i class="fas fa-stop-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Lecture Ended</h4>
                                <p>Physics - Mechanics class</p>
                            </div>
                            <div class="activity-time">Yesterday</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <footer>
            <p>Teacher Dashboard &copy; <?php echo date('Y'); ?> Attendance System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Attendance Chart
        const attendanceData = <?php echo json_encode($lecture_data); ?>;
        if (attendanceData.length > 0) {
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'bar',
                data: {
                    labels: attendanceData.map(item => {
                        const parts = item.label.split(' ');
                        return parts[1] + ' (' + parts[2].replace(/[()]/g, '') + ')';
                    }),
                    datasets: [{
                        label: 'Attendance Rate (%)',
                        data: attendanceData.map(item => item.attendance),
                      backgroundColor: [
    "rgba(124, 58, 237, 0.9)",   // deep purple
    "rgba(147, 51, 234, 0.9)",   // vivid purple
    "rgba(168, 85, 247, 0.9)",   // bright violet
    "rgba(192, 132, 252, 0.9)",  // softer violet
    "rgba(216, 180, 254, 0.9)",  // light lavender
],

borderColor: [
    "rgba(124, 58, 237, 1)",
    "rgba(147, 51, 234, 1)",
    "rgba(168, 85, 247, 1)",
    "rgba(192, 132, 252, 1)",
    "rgba(216, 180, 254, 1)"
],

    borderWidth: 1
                            
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Attendance Rate (%)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    return `${attendanceData[index].class}: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Lectures Chart (removed as it was not tied to real data)

        // LINE CHART — ALL LECTURES (LEFT SIDE)
const attendanceDataAll = <?php echo json_encode($all_lectures); ?>;

if (attendanceDataAll.length > 0) {
    const ctxAll = document.getElementById('attendanceDataAll').getContext('2d');

    new Chart(ctxAll, {
        type: 'line',
        data: {
            labels: attendanceDataAll.map(item => item.label),
            datasets: [{
                label: "Attendance (%)",
                data: attendanceDataAll.map(item => item.attendance),
                borderColor: "rgba(124, 58, 237, 1)",
                backgroundColor: "rgba(124, 58, 237, 0.2)",
                tension: 0.4,
                fill: true,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: "rgba(124, 58, 237, 1)",
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { 
                y: { 
                    beginAtZero: true, 
                    max: 100,
                    title: {
                        display: true,
                        text: 'Attendance (%)'
                    }
                },
                x: {
                    ticks: {
                        autoSkip: true,
                        maxTicksLimit: 8
                    }
                }
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
}

    </script>
</body>
</html>
<?php include '../includes/footer.php'; ?>