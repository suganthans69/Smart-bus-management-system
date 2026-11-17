<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\User;

if (Auth::check()) {
    redirect(url('/passenger/dashboard.php'));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();

    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim(strtolower($_POST['email'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirmation' => $_POST['password_confirmation'] ?? ''
    ];

    $validator = Validator::make($data, [
        'full_name' => 'required|min:3',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|min:10|max:15',
        'password' => 'required|min:6',
        'password_confirmation' => 'required|match:password'
    ]);

    if ($validator->passes()) {
        try {
            // Strictly enforce passenger role
            $userId = User::create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'passenger',
                'is_active' => 1
            ]);

            $user = User::findById($userId);
            Auth::login($user);

            create_notification(
                $userId,
                "Welcome to SmartBus!",
                "Your passenger account has been created. Start searching for routes and enjoy special welcome promo codes!",
                "system"
            );

            flash('success', 'Registration successful! Welcome to SmartBus.');
            redirect(url('/passenger/dashboard.php'));
        } catch (\Exception $e) {
            $errors['general'] = $e->getMessage();
        }
    } else {
        $errors = $validator->errors();
    }
}

$title = 'Create an Account — SmartBus';
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container container-narrow" style="padding: 40px 20px 80px 20px;">
    <div class="card" style="max-width: 520px; margin: 0 auto; box-shadow: var(--shadow-xl);">
        <div class="card-header" style="text-align: center; display: block; background: #fafafa;">
            <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Create Passenger Account</h2>
            <p class="lead" style="font-size: 0.9rem;">Join thousands of satisfied intercity travelers</p>
        </div>

        <div class="card-body">
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <span>✕</span>
                    <div><?= e($errors['general']) ?></div>
                </div>
            <?php endif; ?>

            <form action="<?= url('/register.php') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="e.g. Rahul Sharma" required autofocus>
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="form-error"><?= e($errors['full_name'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= e($_POST['email'] ?? '') ?>" placeholder="rahul@example.com" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="form-error"><?= e($errors['email'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Mobile Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?= e($_POST['phone'] ?? '') ?>" placeholder="9876543210" required>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="form-error"><?= e($errors['phone'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="At least 6 characters" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="form-error"><?= e($errors['password'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" 
                           placeholder="Re-type your password" required>
                    <?php if (isset($errors['password_confirmation'])): ?>
                        <div class="form-error"><?= e($errors['password_confirmation'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Register & Continue
                    </button>
                </div>
            </form>
        </div>

        <div class="card-footer" style="text-align: center; font-size: 0.9rem;">
            Already have an account? <a href="<?= url('/login.php') ?>" style="font-weight: 600;">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

