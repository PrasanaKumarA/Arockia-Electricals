<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/sales/index.php');

$stmt = $db->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address, c.gstin as customer_gstin
                      FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ? AND s.status = 1");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) redirect(APP_URL . '/sales/index.php');

$items = $db->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Check if FPDF exists
$fpdfPath = __DIR__ . '/../libs/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    // Fallback: generate HTML invoice for printing
    header('Content-Type: text/html');
    include_once __DIR__ . '/view.php';
    exit();
}

require_once $fpdfPath;

class InvoicePDF extends FPDF {
    function Header() {}
    function Footer() {
        $this->SetY(-18);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, COMPANY_NAME . ' | ' . COMPANY_PHONE . ' | ' . COMPANY_WEBSITE, 0, 1, 'C');
        $this->Cell(0, 5, 'Thank you for your business!', 0, 0, 'C');
    }
}

$pdf = new InvoicePDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// --- Company Header ---
$logoPath = __DIR__ . '/../assets/images/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 12, 8, 22, 22);
}
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(30, 58, 95);
$pdf->SetXY(36, 8);
$pdf->Cell(0, 7, COMPANY_NAME, 0, 1);
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetX(36);
$pdf->Cell(0, 5, COMPANY_ADDRESS, 0, 1);
$pdf->SetX(36);
$pdf->Cell(0, 5, 'Ph: ' . COMPANY_PHONE . '  |  ' . COMPANY_EMAIL, 0, 1);
$pdf->SetX(36);
$pdf->Cell(0, 5, 'GSTIN: ' . COMPANY_GSTIN, 0, 1);

// --- Invoice Label ---
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(30, 58, 95);
$pdf->SetXY(130, 8);
$pdf->Cell(68, 10, 'TAX INVOICE', 0, 1, 'R');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetX(130);
$pdf->Cell(68, 5, 'Invoice No: ' . $sale['invoice_number'], 0, 1, 'R');
$pdf->SetX(130);
$pdf->Cell(68, 5, 'Date: ' . date('d M Y', strtotime($sale['sale_date'])), 0, 1, 'R');
$pdf->SetX(130);
$status = strtoupper($sale['payment_status']);
$pdf->Cell(68, 5, 'Status: ' . $status, 0, 1, 'R');

$pdf->Ln(4);
$pdf->Line(12, $pdf->GetY(), 198, $pdf->GetY());
$pdf->Ln(4);

// --- Customer / Bill To ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(90, 5, 'BILLED TO', 0, 0);
$pdf->Cell(90, 5, 'PAYMENT DETAILS', 0, 1);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell(90, 6, $sale['customer_name'] ?? 'Walk-in Customer', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(90, 6, 'Method: ' . ucfirst($sale['payment_method'] ?? 'Cash'), 0, 1);
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(80, 80, 80);
if ($sale['customer_phone']) { $pdf->Cell(90, 5, 'Ph: ' . $sale['customer_phone'], 0, 0); } else $pdf->Cell(90,5,'',0,0);
$pdf->Cell(90, 5, 'Amount Paid: Rs.' . number_format($sale['paid_amount'], 2), 0, 1);
if ($sale['customer_address']) { $pdf->Cell(90, 5, $sale['customer_address'], 0, 0); } else $pdf->Cell(90,5,'',0,0);
$due = $sale['total_amount'] - $sale['paid_amount'];
$pdf->SetTextColor($due > 0 ? 200 : 80, $due > 0 ? 0 : 80, 80);
$pdf->Cell(90, 5, 'Balance Due: Rs.' . number_format($due, 2), 0, 1);
$pdf->SetTextColor(30, 30, 30);

$pdf->Ln(4);

// --- Items Table Header ---
$pdf->SetFillColor(30, 58, 95);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(10, 8, '#', 1, 0, 'C', true);
$pdf->Cell(92, 8, 'Product', 1, 0, 'L', true);
$pdf->Cell(18, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(36, 8, 'Total', 1, 1, 'R', true);

// --- Items ---
$pdf->SetTextColor(30, 30, 30);
$pdf->SetFont('Arial', '', 9);
$fill = false;
foreach ($items as $i => $item) {
    $pdf->SetFillColor(245, 247, 250);
    $pdf->Cell(10, 7, $i + 1, 1, 0, 'C', $fill);
    $pdf->Cell(92, 7, $item['product_name'], 1, 0, 'L', $fill);
    $pdf->Cell(18, 7, $item['quantity'], 1, 0, 'C', $fill);
    $pdf->Cell(30, 7, 'Rs.' . number_format($item['selling_price'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(36, 7, 'Rs.' . number_format($item['total_price'], 2), 1, 1, 'R', $fill);
    $fill = !$fill;
}

// --- Totals ---
$pdf->Ln(2);
$x = 130;
$pdf->SetFont('Arial', '', 9);
$pdf->SetX($x);
$pdf->Cell(34, 6, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(34, 6, 'Rs.' . number_format($sale['subtotal'], 2), 0, 1, 'R');

if ($sale['discount'] > 0) {
    $pdf->SetX($x);
    $pdf->Cell(34, 6, 'Discount:', 0, 0, 'R');
    $pdf->Cell(34, 6, '- Rs.' . number_format($sale['discount'], 2), 0, 1, 'R');
}

if ($sale['gst_rate'] > 0) {
    $pdf->SetX($x);
    $pdf->Cell(34, 6, 'GST (' . $sale['gst_rate'] . '%):', 0, 0, 'R');
    $pdf->Cell(34, 6, 'Rs.' . number_format($sale['gst_amount'], 2), 0, 1, 'R');
}

$pdf->SetX($x);
$pdf->Line($x, $pdf->GetY(), 198, $pdf->GetY());
$pdf->Ln(1);
$pdf->SetX($x);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(30, 58, 95);
$pdf->Cell(34, 7, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(34, 7, 'Rs.' . number_format($sale['total_amount'], 2), 0, 1, 'R');

// --- Notes ---
if ($sale['notes']) {
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(0, 5, 'Notes:', 0, 1);
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, $sale['notes']);
}

// Output
$pdf->Output('D', 'Invoice-' . $sale['invoice_number'] . '.pdf');
exit();
