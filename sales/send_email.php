<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/sales/index.php');

$stmt = $db->prepare("SELECT s.*, c.name as customer_name, c.email as customer_email FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) redirect(APP_URL . '/sales/index.php');

$pageTitle = 'Email Invoice - ' . $sale['invoice_number'];
$breadcrumb = ['Sales' => APP_URL . '/sales/index.php', 'Send Email' => ''];

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail  = sanitize($_POST['to_email'] ?? '');
    $subject  = sanitize($_POST['subject'] ?? '');
    $message  = sanitize($_POST['message'] ?? '');

    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if PHPMailer exists
        $phpmailerPath = __DIR__ . '/../libs/phpmailer/PHPMailer.php';
        if (!file_exists($phpmailerPath)) {
            $error = 'PHPMailer not found. Please install PHPMailer in libs/phpmailer/. See README.md for instructions.';
        } else {
            require_once $phpmailerPath;
            require_once __DIR__ . '/../libs/phpmailer/SMTP.php';
            require_once __DIR__ . '/../libs/phpmailer/Exception.php';



            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($toEmail);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = nl2br(htmlspecialchars($message));

                // Attach PDF if FPDF available
                $pdfPath = sys_get_temp_dir() . '/invoice_' . $sale['invoice_number'] . '.pdf';
                // (PDF attachment would require generating to file first)

                $mail->send();
                $sent = true;
                setFlash('success', 'Invoice emailed to ' . $toEmail);
                redirect(APP_URL . '/sales/view.php?id=' . $id);
            } catch (Exception $e) {
                $error = 'Email sending failed: ' . $mail->ErrorInfo;
            }
        }
    }
}

$defaultSubject = 'Invoice ' . $sale['invoice_number'] . ' from ' . COMPANY_NAME;
$defaultMessage = "Dear " . ($sale['customer_name'] ?? 'Customer') . ",\n\nPlease find attached your invoice " . $sale['invoice_number'] . " for " . APP_CURRENCY . number_format($sale['total_amount'], 2) . ".\n\nThank you for your business!\n\nRegards,\n" . COMPANY_NAME . "\n" . COMPANY_PHONE;

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between mb-4">
    <h1 class="page-title"><i class="bi bi-envelope me-2 text-primary"></i>Send Invoice by Email</h1>
    <a href="<?= APP_URL ?>/sales/view.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-envelope-fill me-2"></i>Compose Email</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">To Email <span class="text-danger">*</span></label>
                        <input type="email" name="to_email" class="form-control" value="<?= htmlspecialchars($sale['customer_email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($defaultSubject) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="8"><?= htmlspecialchars($defaultMessage) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send Email</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Invoice Summary</div>
            <div class="card-body">
                <p><strong>Invoice:</strong> <?= sanitize($sale['invoice_number']) ?></p>
                <p><strong>Customer:</strong> <?= sanitize($sale['customer_name'] ?? 'Walk-in') ?></p>
                <p><strong>Total:</strong> <?= formatCurrency($sale['total_amount']) ?></p>
                <hr>
                <p class="text-muted small">Configure SMTP settings in <code>includes/config.php</code>. See README.md for Gmail app password setup.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
