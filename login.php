<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;

if (Auth::check()) {
    redirect(Auth::isAdmin() ? url('/admin/dashboard.php') : url('/passenger/dashboard.php'));
}

$redirectUrl = $_GET['redirect'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirectUrl = $_POST['redirect'] ?? '';

    $validator = Validator::make([
        'email' => $email,
        'password' => $password
    ], [
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($validator->passes()) {
        try {
            if (Auth::attempt($email, $password)) {
                flash('success', 'Welcome back! You have successfully signed in.');
                if (!empty($redirectUrl)) {
                    redirect($redirectUrl);
                } else {
                    redirect(Auth::isAdmin() ? url('/admin/dashboard.php') : url('/passenger/dashboard.php'));
                }
            } else {
                $error = 'Invalid email address or password. Please verify and try again.';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = $validator->first('email') ?: $validator->first('password');
    }
}

$title = 'Sign In — SmartBus';
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container container-narrow" style="padding: 40px 20px 80px 20px;">
    <div class="card" style="max-width: 480px; margin: 0 auto; box-shadow: var(--shadow-xl);">
        <div class="card-header" style="text-align: center; display: block; background: #fafafa;">
            <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Sign In to SmartBus</h2>
            <p class="lead" style="font-size: 0.9rem;">Access your bus bookings, digital tickets, and rewards</p>
        </div>

        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>✕</span>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/login.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= e($redirectUrl) ?>">

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@example.com" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
                        <a href="<?= url('/forgot_password.php') ?>" style="font-size:0.8rem;">Forgot Password?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Quick Demo Accounts Helper -->
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                <div style="font-weight:700; color:#0f172a; margin-bottom:8px;">💡 Demo Accounts for Evaluation:</div>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span><b>Admin:</b> admin@smartbus.com</span>
                    <button type="button" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;" 
                            onclick="document.getElementById('email').value='admin@smartbus.com'; document.getElementById('password').value='Admin@123';">
                        Fill Admin
                    </button>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span><b>Passenger:</b> passenger@smartbus.com</span>
                    <button type="button" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;" 
                            onclick="document.getElementById('email').value='passenger@smartbus.com'; document.getElementById('password').value='Passenger@123';">
                        Fill Passenger
                    </button>
                </div>
            </div>
        </div>

        <div class="card-footer" style="text-align: center; font-size: 0.9rem;">
            Don't have an account? <a href="<?= url('/register.php') ?>" style="font-weight: 600;">Create Account</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

