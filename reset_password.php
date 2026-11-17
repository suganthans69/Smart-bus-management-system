<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Csrf;
use App\Models\User;

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$email = $_GET['email'] ?? $_POST['email'] ?? '';

$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT * FROM users 
    WHERE email = ? AND reset_token = ? AND reset_expires_at > NOW() 
    LIMIT 1
");
$stmt->execute([trim(strtolower($email)), $token]);
$user = $stmt->fetch();

$error = '';

if (!$user) {
    $error = "This password reset token is invalid or has expired. Please request a new one.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    Csrf::validateRequest();

    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirmation'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        User::updatePassword($user['id'], $newPassword);

        // Invalidate reset token
        $db->prepare("UPDATE users SET reset_token = NULL, reset_expires_at = NULL WHERE id = ?")->execute([$user['id']]);

        flash('success', 'Your password has been reset successfully! Please sign in with your new credentials.');
        redirect(url('/login.php'));
    }
}

$title = 'Set New Password — SmartBus';
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container container-narrow" style="padding: 40px 20px 80px 20px;">
    <div class="card" style="max-width: 480px; margin: 0 auto;">
        <div class="card-header" style="text-align: center; display: block;">
            <h2 style="font-size: 1.4rem;">Choose New Password</h2>
            <p class="lead" style="font-size: 0.85rem;">Account: <?= e($email) ?></p>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form action="<?= url('/reset_password.php') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="email" value="<?= e($email) ?>">

                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Minimum 6 characters" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" 
                               placeholder="Re-type new password" required>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary btn-block">
                            Update Password & Sign In
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align:center; margin-top:10px;">
                    <a href="<?= url('/forgot_password.php') ?>" class="btn btn-navy">Request New Link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

