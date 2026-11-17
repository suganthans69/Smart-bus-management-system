<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Csrf;
use App\Models\User;

$simulatedResetLink = null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (empty($email)) {
        $error = 'Please enter your registered email address.';
    } else {
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(24));
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE users 
                SET reset_token = ?, reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                WHERE id = ?
            ");
            $stmt->execute([$token, $user['id']]);

            $simulatedResetLink = url('/reset_password.php?token=' . $token . '&email=' . urlencode($email));
            $success = "Password reset instructions generated.";
        } else {
            // Friendly message
            $error = "No account found with this email address.";
        }
    }
}

$title = 'Forgot Password — SmartBus';
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container container-narrow" style="padding: 40px 20px 80px 20px;">
    <div class="card" style="max-width: 480px; margin: 0 auto;">
        <div class="card-header" style="text-align: center; display: block;">
            <h2 style="font-size: 1.4rem;">Reset Your Password</h2>
            <p class="lead" style="font-size: 0.85rem;">Enter your email to receive a password reset link</p>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= e($success) ?>
                </div>

                <!-- Explicit Local Development Email Strategy -->
                <div style="background:#f0fdf4; border:1.5px dashed #22c55e; border-radius:8px; padding:16px; margin-bottom:20px;">
                    <div style="font-weight:700; color:#166534; margin-bottom:6px;">📬 Local Development Email Simulation:</div>
                    <p style="font-size:0.85rem; color:#14532d; margin-bottom:10px;">
                        In local XAMPP environments without an active SMTP server, the generated reset link is displayed directly below:
                    </p>
                    <a href="<?= e($simulatedResetLink) ?>" class="btn btn-primary btn-sm btn-block" style="word-break:break-all;">
                        👉 Click to Reset Password
                    </a>
                </div>
            <?php endif; ?>

            <form action="<?= url('/forgot_password.php') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="email">Registered Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= e($_POST['email'] ?? '') ?>" placeholder="passenger@smartbus.com" required autofocus>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-navy btn-block">
                        Send Reset Link
                    </button>
                </div>
            </form>
        </div>

        <div class="card-footer" style="text-align: center; font-size: 0.85rem;">
            Remember your password? <a href="<?= url('/login.php') ?>">Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

