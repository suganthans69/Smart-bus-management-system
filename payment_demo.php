<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Booking;
use App\Services\BookingService;

Auth::requireLogin();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = Booking::findById($bookingId);

if (!$booking) {
    flash('error', 'Booking record not found.');
    redirect(url('/passenger/bookings.php'));
}

// Ownership check
if ((int)$booking['user_id'] !== Auth::id() && !Auth::isAdmin()) {
    flash('error', 'Unauthorized access to this booking.');
    redirect(url('/passenger/dashboard.php'));
}

// If already confirmed, redirect to confirmation
if ($booking['booking_status'] === 'CONFIRMED') {
    redirect(url('/booking_confirmation.php?booking_id=' . $bookingId));
}

$error = '';

// Handle Payment Simulation Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $action = $_POST['action'] ?? '';
    $method = $_POST['payment_method'] ?? 'UPI';

    if ($action === 'simulate_success') {
        try {
            $notes = "Demo test payment via {$method} simulator. Reference generated for academic demonstration.";
            $result = BookingService::confirmPayment($bookingId, $method, $notes);

            flash('success', 'Demo Payment Approved! Your ticket has been confirmed.');
            redirect(url('/booking_confirmation.php?booking_id=' . $bookingId));
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'simulate_failure') {
        try {
            BookingService::failPayment($bookingId, "Simulated transaction declined by user / bank gateway");
            flash('error', 'Simulated Payment Failed. The reserved seats have been released back to inventory.');
            redirect(url('/passenger/bookings.php'));
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'cancel') {
        BookingService::failPayment($bookingId, "User cancelled checkout session");
        flash('info', 'Checkout cancelled. You may initiate a new search when ready.');
        redirect(url('/'));
    }
}

$title = "Demo Payment Simulator — Booking " . e($booking['booking_reference']);
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container container-narrow" style="padding: 30px 20px 80px 20px;">
    <!-- Booking Steps Indicator -->
    <div class="steps-indicator">
        <div class="step-item completed">
            <div class="step-circle">✓</div>
            <div class="step-title">1. Search Bus</div>
        </div>
        <div class="step-item completed">
            <div class="step-circle">✓</div>
            <div class="step-title">2. Select Seats & Details</div>
        </div>
        <div class="step-item active">
            <div class="step-circle">3</div>
            <div class="step-title">3. Demo Payment</div>
        </div>
        <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-title">4. Ticket Confirmation</div>
        </div>
    </div>

    <!-- Prominent Demo Disclaimer Banner -->
    <div class="alert alert-warning" style="border: 2px dashed #f59e0b; background: #fffbeb;">
        <span style="font-size: 1.5rem;">⚠️</span>
        <div>
            <b>ACADEMIC DEMONSTRATION PAYMENT ENVIRONMENT</b><br>
            This portal does not connect to a real banking network and will <b>never charge real money</b> or ask for confidential banking PINs/passwords. Use the interactive buttons below to test payment outcomes.
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card" style="box-shadow: var(--shadow-xl); overflow: hidden;">
        <div class="card-header" style="background: var(--primary); color: #fff;">
            <div>
                <h3 style="color:#fff; font-size: 1.25rem;">Complete Demo Payment</h3>
                <span style="font-size: 0.8rem; color: #94a3b8;">Booking Reference (PNR): <b><?= e($booking['booking_reference']) ?></b></span>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.8rem; color: #cbd5e1;">Amount Due</div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #38bdf8;"><?= format_currency($booking['total_amount']) ?></div>
            </div>
        </div>

        <div class="card-body">
            <!-- Journey Quick Summary -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px; margin-bottom: 24px; font-size: 0.9rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <span class="text-muted">Route:</span> <b><?= e($booking['source_city']) ?> → <?= e($booking['destination_city']) ?></b><br>
                        <span class="text-muted">Coach:</span> <?= e($booking['operator_name']) ?> (<?= e($booking['bus_number']) ?>)
                    </div>
                    <div>
                        <span class="text-muted">Departure:</span> <?= format_date($booking['departure_datetime'], 'd M Y, h:i A') ?><br>
                        <span class="text-muted">Seats:</span> 
                        <?php foreach ($booking['seats'] as $s): ?>
                            <span class="badge badge-primary"><?= e($s['seat_number']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Tabbed Payment Options -->
            <form action="<?= url('/payment_demo.php?booking_id=' . $bookingId) ?>" method="POST" id="demo-pay-form">
                <?= csrf_field() ?>

                <div style="margin-bottom: 20px;">
                    <label class="form-label" style="margin-bottom: 12px;">Choose Simulated Payment Mode:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <label style="border: 2px solid #3b82f6; background: #eff6ff; padding: 14px; border-radius: 8px; cursor: pointer; text-align: center;">
                            <input type="radio" name="payment_method" value="UPI" checked style="margin-bottom: 6px;"><br>
                            <b>📱 Demo UPI / QR</b>
                        </label>

                        <label style="border: 1px solid var(--border); background: #ffffff; padding: 14px; border-radius: 8px; cursor: pointer; text-align: center;">
                            <input type="radio" name="payment_method" value="CARD" style="margin-bottom: 6px;"><br>
                            <b>💳 Demo Card</b>
                        </label>

                        <label style="border: 1px solid var(--border); background: #ffffff; padding: 14px; border-radius: 8px; cursor: pointer; text-align: center;">
                            <input type="radio" name="payment_method" value="NETBANKING" style="margin-bottom: 6px;"><br>
                            <b>🏦 NetBanking</b>
                        </label>
                    </div>
                </div>

                <!-- Simulated UPI Details -->
                <div style="background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 24px;">
                    <div style="display: inline-block; background: #fff; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 12px;">
                        <!-- Mock QR Code visual -->
                        <div style="width: 140px; height: 140px; background: repeating-conic-gradient(#0f172a 0% 25%, #ffffff 0% 50%) 50% / 20px 20px; border-radius: 4px; display:flex; align-items:center; justify-content:center;">
                            <span style="background:#fff; padding:4px 8px; font-size:0.75rem; font-weight:800; border:1px solid #000;">DEMO QR</span>
                        </div>
                    </div>
                    <div style="font-weight: 700; color: #1e293b;">VPA: smartbus.test@upi</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Simulated dynamic UPI merchant handle</div>
                </div>

                <!-- Simulation Action Controls -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <button type="submit" name="action" value="simulate_success" class="btn btn-primary btn-lg btn-block" style="background: #10b981; border-color: #059669;">
                        ✅ Simulate Payment Success (Approve & Confirm)
                    </button>

                    <button type="submit" name="action" value="simulate_failure" class="btn btn-outline btn-block" style="color: #ef4444; border-color: #fca5a5;">
                        ❌ Simulate Payment Failure (Card Declined)
                    </button>

                    <button type="submit" name="action" value="cancel" class="btn btn-outline btn-sm btn-block" style="color: #64748b;">
                        Cancel and Exit Checkout
                    </button>
                </div>
            </form>
        </div>

        <!-- Architectural Note on Real Payment Gateways -->
        <div class="card-footer" style="background: #f8fafc; font-size: 0.8rem; color: #64748b; line-height: 1.5;">
            <b>ℹ️ Real Production Gateway Integration Architecture:</b><br>
            To deploy with a live payment gateway (e.g., Razorpay, Stripe, or PayU), replace this simulation endpoint with a server-side order creation API (`POST /v1/orders`), render their official JS checkout modal, and verify webhook signatures using HMAC-SHA256 in a secure callback handler (`/api/payment_webhook.php`).
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

