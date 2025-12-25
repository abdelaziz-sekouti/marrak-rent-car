<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        die('Security validation failed.');
    }
    
    $reportType = $_POST['report_type'] ?? '';
    $startDate = $_POST['start_date'] ?? date('Y-m-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-t');
    $format = $_POST['format'] ?? 'csv';
    
    // Validate parameters
    if (!in_array($reportType, ['revenue', 'rentals', 'cars', 'customers'])) {
        die('Invalid report type.');
    }
    
    if (!in_array($format, ['csv', 'excel', 'pdf'])) {
        $format = 'csv';
    }
    
    // Generate and export report
    try {
        switch ($format) {
            case 'csv':
                exportToCSV($reportType, $startDate, $endDate);
                break;
            case 'excel':
                exportToExcel($reportType, $startDate, $endDate);
                break;
            case 'pdf':
                exportToPDF($reportType, $startDate, $endDate);
                break;
            default:
                exportToCSV($reportType, $startDate, $endDate);
        }
    } catch (Exception $e) {
        die('Export failed: ' . $e->getMessage());
    }
} else {
    // Redirect back to reports if not POST request
    header('Location: reports.php');
    exit;
}

/**
 * Export to CSV format
 */
function exportToCSV($reportType, $startDate, $endDate) {
    $data = getReportData($reportType, $startDate, $endDate);
    $filename = getReportFileName($reportType, $startDate, $endDate, 'csv');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    if (!empty($data)) {
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Export to Excel format (using CSV as fallback)
 */
function exportToExcel($reportType, $startDate, $endDate) {
    // For now, use CSV format with Excel content type
    $data = getReportData($reportType, $startDate, $endDate);
    $filename = getReportFileName($reportType, $startDate, $endDate, 'xlsx');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Create simple Excel-compatible CSV
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
    
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Export to PDF format
 */
function exportToPDF($reportType, $startDate, $endDate) {
    $data = getReportData($reportType, $startDate, $endDate);
    $filename = getReportFileName($reportType, $startDate, $endDate, 'pdf');
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Simple PDF generation using HTML/CSS
    $html = generatePDFHTML($reportType, $data, $startDate, $endDate);
    
    // Use a simple PDF library approach - for now, generate HTML
    // In production, you'd use TCPDF, DomPDF, or similar
    echo $html;
    exit;
}

/**
 * Get report data based on type
 */
function getReportData($reportType, $startDate, $endDate) {
    $db = new Database();
    
    switch ($reportType) {
        case 'revenue':
            return getRevenueData($db, $startDate, $endDate);
        case 'rentals':
            return getRentalsData($db, $startDate, $endDate);
        case 'cars':
            return getCarsData($db, $startDate, $endDate);
        case 'customers':
            return getCustomersData($db, $startDate, $endDate);
        default:
            return [];
    }
}

/**
 * Get revenue report data
 */
function getRevenueData($db, $startDate, $endDate) {
    $sql = "SELECT 
                DATE(r.created_at) as date,
                COUNT(r.id) as total_bookings,
                SUM(r.total_cost) as total_revenue,
                AVG(r.total_cost) as average_booking,
                c.category as car_category,
                COUNT(DISTINCT r.user_id) as unique_customers
            FROM rentals r
            JOIN cars c ON r.car_id = c.id
            WHERE DATE(r.created_at) BETWEEN :start_date AND :end_date
            GROUP BY DATE(r.created_at), c.category
            ORDER BY DATE(r.created_at) DESC";
    
    $db->query($sql);
    $db->bind(':start_date', $startDate);
    $db->bind(':end_date', $endDate);
    
    return $db->resultSet();
}

/**
 * Get rentals report data
 */
function getRentalsData($db, $startDate, $endDate) {
    $sql = "SELECT 
                r.id as booking_id,
                r.start_date,
                r.end_date,
                r.total_cost,
                r.status,
                r.pickup_location,
                r.dropoff_location,
                u.name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                c.make as car_make,
                c.model as car_model,
                c.license_plate,
                r.created_at as booking_date
            FROM rentals r
            JOIN users u ON r.user_id = u.id
            JOIN cars c ON r.car_id = c.id
            WHERE DATE(r.created_at) BETWEEN :start_date AND :end_date
            ORDER BY r.id DESC";
    
    $db->query($sql);
    $db->bind(':start_date', $startDate);
    $db->bind(':end_date', $endDate);
    
    return $db->resultSet();
}

/**
 * Get cars performance data
 */
function getCarsData($db, $startDate, $endDate) {
    $sql = "SELECT 
                c.id,
                c.make,
                c.model,
                c.year,
                c.license_plate,
                c.category,
                c.daily_rate,
                c.status,
                COUNT(r.id) as total_rentals,
                COALESCE(SUM(r.total_cost), 0) as total_revenue,
                COALESCE(AVG(DATEDIFF(r.end_date, r.start_date) + 1), 0) as avg_rental_days,
                COALESCE(MAX(r.created_at), '1970-01-01') as last_rental_date
            FROM cars c
            LEFT JOIN rentals r ON c.id = r.car_id 
                AND DATE(r.created_at) BETWEEN :start_date AND :end_date
            GROUP BY c.id, c.make, c.model, c.year, c.license_plate, c.category, c.daily_rate, c.status
            ORDER BY total_rentals DESC, total_revenue DESC";
    
    $db->query($sql);
    $db->bind(':start_date', $startDate);
    $db->bind(':end_date', $endDate);
    
    return $db->resultSet();
}

/**
 * Get customers report data
 */
function getCustomersData($db, $startDate, $endDate) {
    $sql = "SELECT 
                u.id,
                u.name,
                u.email,
                u.phone,
                u.role,
                u.created_at as registration_date,
                COUNT(r.id) as total_rentals,
                COALESCE(SUM(r.total_cost), 0) as total_spent,
                COALESCE(AVG(r.total_cost), 0) as avg_booking_value,
                COALESCE(MAX(r.created_at), '1970-01-01') as last_booking_date
            FROM users u
            LEFT JOIN rentals r ON u.id = r.user_id 
                AND DATE(r.created_at) BETWEEN :start_date AND :end_date
            GROUP BY u.id, u.name, u.email, u.phone, u.role, u.created_at
            ORDER BY total_spent DESC, total_rentals DESC";
    
    $db->query($sql);
    $db->bind(':start_date', $startDate);
    $db->bind(':end_date', $endDate);
    
    return $db->resultSet();
}

/**
 * Generate filename for export
 */
function getReportFileName($reportType, $startDate, $endDate, $format) {
    $reportName = ucfirst($reportType);
    $dateRange = date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate));
    return "{$reportName}_Report_{$dateRange}.{$format}";
}

/**
 * Generate PDF HTML content
 */
function generatePDFHTML($reportType, $data, $startDate, $endDate) {
    $title = ucfirst($reportType) . ' Report';
    $dateRange = date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #3B82F6; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>' . $title . '</h1>
    <div class="summary">
        <p><strong>Period:</strong> ' . $dateRange . '</p>
        <p><strong>Generated:</strong> ' . date('M j, Y H:i') . '</p>
        <p><strong>Total Records:</strong> ' . count($data) . '</p>
    </div>';
    
    if (!empty($data)) {
        $html .= '<table>
            <thead>
                <tr>';
        
        foreach (array_keys($data[0]) as $header) {
            $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
        }
        
        $html .= '</tr>
            </thead>
            <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody>
        </table>';
    } else {
        $html .= '<p>No data found for the selected period.</p>';
    }
    
    $html .= '</body>
</html>';
    
    return $html;
}
?>