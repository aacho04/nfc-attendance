<?php
include '../includes/db.php';
include '../includes/functions.php';

if (!is_teacher()) redirect('../dashboard.php');

$teacher_id = get_teacher_id($pdo, $_SESSION['user_id']);

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Get filter parameters
    $filter_type = $_GET['filter_type'] ?? 'all';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $month_year = $_GET['month_year'] ?? '';
    $selected_subjects = $_GET['subjects'] ?? [];
    $attendance_range = $_GET['attendance_range'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    // Build date condition for lectures
    $date_condition = "";
    $date_params = [];
    
    if ($filter_type === 'date_range' && $date_from && $date_to) {
        $date_condition = " AND l.date BETWEEN ? AND ?";
        $date_params = [$date_from, $date_to];
    } elseif ($filter_type === 'monthly' && $month_year) {
        $date_condition = " AND DATE_FORMAT(l.date, '%Y-%m') = ?";
        $date_params = [$month_year];
    }
    
    // Build subject condition
    $subject_condition = "";
    $subject_params = [];
    if (!empty($selected_subjects)) {
        $placeholders = str_repeat('?,', count($selected_subjects) - 1) . '?';
        $subject_condition = " AND s.id IN ($placeholders)";
        $subject_params = $selected_subjects;
    }
    
    // Get students and their attendance
    $students = $pdo->prepare("SELECT DISTINCT st.id, st.name, c.name as class FROM students st JOIN classes c ON st.class_id = c.id JOIN assignments a ON a.class_id = c.id WHERE a.teacher_id = ?");
    $students->execute([$teacher_id]);
    $students = $students->fetchAll();
    
    $reports = [];
    foreach ($students as $st) {
        $query = "SELECT a.id, s.name as subject, COUNT(l.id) as total_lectures 
                  FROM assignments a 
                  JOIN subjects s ON a.subject_id = s.id 
                  JOIN lectures l ON l.assignment_id = a.id 
                  WHERE a.class_id = (SELECT class_id FROM students WHERE id = ?) 
                  AND a.teacher_id = ?" . $date_condition . $subject_condition . "
                  GROUP BY a.id, s.id";
        
        $params = array_merge([$st['id'], $teacher_id], $date_params, $subject_params);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $subs = $stmt->fetchAll();
        
        foreach ($subs as $sub) {
            $att_query = "SELECT COUNT(*) FROM attendances att 
                          JOIN lectures l ON att.lecture_id = l.id 
                          WHERE l.assignment_id = ? AND att.student_id = ?" . $date_condition;
            
            $att_params = array_merge([$sub['id'], $st['id']], $date_params);
            $att_count = $pdo->prepare($att_query);
            $att_count->execute($att_params);
            $attended = $att_count->fetchColumn();
            
            $percent = ($sub['total_lectures'] > 0) ? ($attended / $sub['total_lectures']) * 100 : 0;
            
            // Apply attendance range filter
            $include = true;
            switch ($attendance_range) {
                case 'excellent': // Above 75%
                    $include = $percent >= 75;
                    break;
                case 'average': // Between 50-75%
                    $include = $percent >= 50 && $percent < 75;
                    break;
                case 'defaulters': // Under 50%
                    $include = $percent < 50;
                    break;
                case 'all':
                default:
                    $include = true;
                    break;
            }
            
            // Apply search filter
            if ($search && stripos($st['name'], $search) === false) {
                $include = false;
            }
            
            if ($include) {
                $reports[] = [
                    'student' => $st['name'],
                    'class' => $st['class'],
                    'subject' => $sub['subject'],
                    'attendance' => round($percent, 2),
                    'attended' => $attended,
                    'total' => $sub['total_lectures']
                ];
            }
        }
    }
    
    // Calculate summary statistics
    $excellent = array_filter($reports, fn($r) => $r['attendance'] >= 75);
    $average = array_filter($reports, fn($r) => $r['attendance'] >= 50 && $r['attendance'] < 75);
    $defaulters = array_filter($reports, fn($r) => $r['attendance'] < 50);
    
    echo json_encode([
        'reports' => $reports,
        'summary' => [
            'total' => count($reports),
            'excellent' => count($excellent),
            'average' => count($average),
            'defaulters' => count($defaulters)
        ]
    ]);
    exit;
}

// Handle CSV download
if (isset($_GET['download'])) {
    $download_type = $_GET['download_type'] ?? 'all';
    
    // Re-fetch data based on current filters
    $filter_type = $_GET['filter_type'] ?? 'all';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $month_year = $_GET['month_year'] ?? '';
    $selected_subjects = $_GET['subjects'] ?? [];
    $attendance_range = $_GET['attendance_range'] ?? 'all';
    
    // Build date condition for lectures
    $date_condition = "";
    $date_params = [];
    
    if ($filter_type === 'date_range' && $date_from && $date_to) {
        $date_condition = " AND l.date BETWEEN ? AND ?";
        $date_params = [$date_from, $date_to];
    } elseif ($filter_type === 'monthly' && $month_year) {
        $date_condition = " AND DATE_FORMAT(l.date, '%Y-%m') = ?";
        $date_params = [$month_year];
    }
    
    // Build subject condition
    $subject_condition = "";
    $subject_params = [];
    if (!empty($selected_subjects)) {
        $placeholders = str_repeat('?,', count($selected_subjects) - 1) . '?';
        $subject_condition = " AND s.id IN ($placeholders)";
        $subject_params = $selected_subjects;
    }
    
    // Get students and their attendance
    $students = $pdo->prepare("SELECT DISTINCT st.id, st.name, c.name as class FROM students st JOIN classes c ON st.class_id = c.id JOIN assignments a ON a.class_id = c.id WHERE a.teacher_id = ?");
    $students->execute([$teacher_id]);
    $students = $students->fetchAll();
    
    $reports = [];
    foreach ($students as $st) {
        $query = "SELECT a.id, s.name as subject, COUNT(l.id) as total_lectures 
                  FROM assignments a 
                  JOIN subjects s ON a.subject_id = s.id 
                  JOIN lectures l ON l.assignment_id = a.id 
                  WHERE a.class_id = (SELECT class_id FROM students WHERE id = ?) 
                  AND a.teacher_id = ?" . $date_condition . $subject_condition . "
                  GROUP BY a.id, s.id";
        
        $params = array_merge([$st['id'], $teacher_id], $date_params, $subject_params);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $subs = $stmt->fetchAll();
        
        foreach ($subs as $sub) {
            $att_query = "SELECT COUNT(*) FROM attendances att 
                          JOIN lectures l ON att.lecture_id = l.id 
                          WHERE l.assignment_id = ? AND att.student_id = ?" . $date_condition;
            
            $att_params = array_merge([$sub['id'], $st['id']], $date_params);
            $att_count = $pdo->prepare($att_query);
            $att_count->execute($att_params);
            $attended = $att_count->fetchColumn();
            
            $percent = ($sub['total_lectures'] > 0) ? ($attended / $sub['total_lectures']) * 100 : 0;
            
            $reports[] = [
                'student' => $st['name'],
                'class' => $st['class'],
                'subject' => $sub['subject'],
                'attendance' => round($percent, 2),
                'attended' => $attended,
                'total' => $sub['total_lectures']
            ];
        }
    }
    
    $csv_data = [['Student', 'Class', 'Subject', 'Attendance %', 'Attended', 'Total Lectures']];
    
    $data_to_export = $reports;
    if ($download_type === 'defaulters') {
        $data_to_export = array_filter($reports, function($r) {
            return $r['attendance'] < 50;
        });
    } elseif ($download_type === 'average') {
        $data_to_export = array_filter($reports, function($r) {
            return $r['attendance'] >= 50 && $r['attendance'] < 75;
        });
    } elseif ($download_type === 'excellent') {
        $data_to_export = array_filter($reports, function($r) {
            return $r['attendance'] >= 75;
        });
    }
    
    foreach ($data_to_export as $r) {
        $csv_data[] = [$r['student'], $r['class'], $r['subject'], $r['attendance'] . '%', $r['attended'], $r['total']];
    }
    
    $filename = $download_type . '_attendance_report.csv';
    export_csv($csv_data, $filename);
}

// Get all subjects for filter dropdown
$subjects_stmt = $pdo->prepare("SELECT DISTINCT s.id, s.name FROM subjects s JOIN assignments a ON s.id = a.subject_id WHERE a.teacher_id = ?");
$subjects_stmt->execute([$teacher_id]);
$all_subjects = $subjects_stmt->fetchAll();

include '../includes/header.php';
?>

<style>
.fade-in {
    opacity: 0;
    animation: fadeIn 0.5s ease-in forwards;
}

@keyframes fadeIn {
    to { opacity: 1; }
}

.slide-up {
    transform: translateY(20px);
    opacity: 0;
    animation: slideUp 0.4s ease-out forwards;
}

@keyframes slideUp {
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    border-top-color: #3b82f6;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.hover-scale {
    transition: transform 0.2s;
}

.hover-scale:hover {
    transform: scale(1.02);
}

.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
</style>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <h1 class="text-3xl font-bold text-gray-900">Attendance Reports</h1>
            <p class="mt-1 text-gray-600">Track and analyze student attendance data</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Students</label>
                    <input type="text" id="searchInput" placeholder="Type student name..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Filter Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Time Period</label>
                    <select id="filter_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">All Time</option>
                        <option value="date_range">Date Range</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>

                <!-- Attendance Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Performance</label>
                    <select id="attendance_range" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">All Students</option>
                        <option value="excellent">Excellent (≥75%)</option>
                        <option value="average">Average (50-74%)</option>
                        <option value="defaulters">Poor (<50%)</option>
                    </select>
                </div>
            </div>

            <!-- Date Inputs -->
            <div id="date_range_inputs" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" style="display: none;">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" id="date_from" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" id="date_to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div id="monthly_input" class="mb-4" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Month</label>
                <input type="month" id="month_year" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Subjects -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subjects (Hold Ctrl to select multiple)</label>
                <select id="subjects" multiple class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-20">
                    <?php foreach ($all_subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>">
                            <?= htmlspecialchars($subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
               <button id="resetFilters"
    class="px-5 py-3 rounded-xl font-semibold shadow-md
           bg-gradient-to-r from-violet-600 to-violet-500
           text-white hover:from-violet-700 hover:to-violet-600
           transition-all duration-300">
    Reset Filters
</button>

                <div id="loadingIndicator" class="hidden flex items-center text-blue-600">
                    <div class="loading mr-2"></div>
                    Loading...
                </div>
            </div>
        </div>

        <!-- Download Buttons -->
       <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 mb-8">

    <h3 class="text-2xl font-semibold text-gray-800 mb-6">
        Download Attendance Reports
    </h3>

    <div class="flex flex-wrap gap-4">

        <!-- All Records -->
        <button data-type="all"
            class="download-btn px-6 py-3 rounded-xl font-semibold shadow-md
                   bg-violet-600 text-white 
                   hover:bg-violet-700 transition-all">
            Download All Records
        </button>

        <!-- Excellent -->
        <button data-type="excellent"
            class="download-btn px-6 py-3 rounded-xl font-semibold shadow-md
                   bg-violet-500 text-white 
                   hover:bg-violet-600 transition-all">
            Download Excellent
        </button>

        <!-- Average -->
        <button data-type="average"
            class="download-btn px-6 py-3 rounded-xl font-semibold shadow-md
                   bg-violet-400 text-white 
                   hover:bg-violet-500 transition-all">
            Download Average
        </button>

        <!-- Defaulters -->
        <button data-type="defaulters"
            class="download-btn px-6 py-3 rounded-xl font-semibold shadow-md
                   bg-violet-300 text-white
                   hover:bg-violet-400 transition-all">
            Download Defaulters
        </button>

    </div>
</div>

        <!-- Statistics -->
        <div id="statsContainer" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Will be populated by AJAX -->
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Attendance Data</h3>
            </div>
            <div id="tableContainer">
                <!-- Will be populated by AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
let debounceTimer = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    setupEventListeners();
});

function setupEventListeners() {
    // Search input
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadData, 300);
    });

    // Filter changes
    document.getElementById('filter_type').addEventListener('change', function() {
        handleFilterTypeChange();
        loadData();
    });

    document.getElementById('attendance_range').addEventListener('change', loadData);
    document.getElementById('date_from').addEventListener('change', loadData);
    document.getElementById('date_to').addEventListener('change', loadData);
    document.getElementById('month_year').addEventListener('change', loadData);
    document.getElementById('subjects').addEventListener('change', loadData);

    // Reset button
    document.getElementById('resetFilters').addEventListener('click', resetFilters);

    // Download buttons
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            downloadCSV(this.dataset.type);
        });
    });
}

function handleFilterTypeChange() {
    const filterType = document.getElementById('filter_type').value;
    const dateRangeInputs = document.getElementById('date_range_inputs');
    const monthlyInput = document.getElementById('monthly_input');

    dateRangeInputs.style.display = filterType === 'date_range' ? 'grid' : 'none';
    monthlyInput.style.display = filterType === 'monthly' ? 'block' : 'none';
}

function showLoading() {
    document.getElementById('loadingIndicator').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingIndicator').classList.add('hidden');
}

function getFilters() {
    return {
        filter_type: document.getElementById('filter_type').value,
        date_from: document.getElementById('date_from').value,
        date_to: document.getElementById('date_to').value,
        month_year: document.getElementById('month_year').value,
        subjects: Array.from(document.getElementById('subjects').selectedOptions).map(o => o.value),
        attendance_range: document.getElementById('attendance_range').value,
        search: document.getElementById('searchInput').value
    };
}

async function loadData() {
    showLoading();

    try {
        const filters = getFilters();
        const params = new URLSearchParams({...filters, ajax: '1'});
        
        const response = await fetch(`${window.location.pathname}?${params}`);
        const data = await response.json();

        updateStats(data.summary);
        updateTable(data.reports);
    } catch (error) {
        console.error('Error loading data:', error);
        showError('Failed to load data. Please try again.');
    } finally {
        hideLoading();
    }
}

function updateStats(summary) {
    const statsContainer = document.getElementById('statsContainer');

    const stats = [
    { label: 'Total Records', value: summary.total, color: 'from-violet-700 to-violet-600' },
    { label: 'Excellent (75%+)', value: summary.excellent, color: 'from-violet-600 to-violet-500' },
    { label: 'Average (50-74%)', value: summary.average, color: 'from-violet-500 to-violet-400' },
    { label: 'Defaulters (<50%)', value: summary.defaulters, color: 'from-violet-700 to-violet-500' }
];


    statsContainer.innerHTML = stats.map(stat => `
        <div class="stat-card bg-gradient-to-br ${stat.color} 
                    rounded-2xl p-6 text-white shadow-md 
                    hover:shadow-xl hover:-translate-y-1 
                    transition-all duration-300">

            <div class="text-3xl font-bold">${stat.value}</div>
            <div class="text-sm opacity-90">${stat.label}</div>

        </div>
    `).join('');
}


function updateTable(reports) {
    const tableContainer = document.getElementById('tableContainer');

    if (reports.length === 0) {
        tableContainer.innerHTML = `
            <div class="p-12 text-center text-gray-500">
                <div class="text-4xl mb-4">📋</div>
                <div class="text-lg">No records found</div>
                <div class="text-sm">Try adjusting your filters</div>
            </div>
        `;
        return;
    }

    const tableHTML = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Attended</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Attendance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    ${reports.map(report => {
                        const attendanceClass = report.attendance >= 75 ? 'bg-green-100 text-green-800' :
                                              report.attendance >= 50 ? 'bg-yellow-100 text-yellow-800' :
                                              'bg-red-100 text-red-800';
                        
                        return `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">${escapeHtml(report.student)}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">${escapeHtml(report.class)}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">${escapeHtml(report.subject)}</td>
                                <td class="px-6 py-4 text-sm text-center font-medium text-blue-600">${report.attended}</td>
                                <td class="px-6 py-4 text-sm text-center text-gray-600">${report.total}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${attendanceClass}">
                                        ${report.attendance}%
                                    </span>
                                </td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;

    tableContainer.innerHTML = tableHTML;
}

function showError(message) {
    const tableContainer = document.getElementById('tableContainer');
    tableContainer.innerHTML = `
        <div class="p-12 text-center text-red-500">
            <div class="text-4xl mb-4">❌</div>
            <div class="text-lg">${message}</div>
        </div>
    `;
}

function resetFilters() {
    document.getElementById('filter_type').value = 'all';
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    document.getElementById('month_year').value = '';
    document.getElementById('attendance_range').value = 'all';
    document.getElementById('searchInput').value = '';
    
    Array.from(document.getElementById('subjects').options).forEach(option => {
        option.selected = false;
    });

    handleFilterTypeChange();
    loadData();
}

function downloadCSV(type) {
    const filters = getFilters();
    const params = new URLSearchParams({
        ...filters,
        download: '1',
        download_type: type
    });

    window.location.href = `${window.location.pathname}?${params}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../includes/footer.php'; ?>