<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Models\Booking;

Auth::requireLogin();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = Booking::findById($bookingId);

if (!$booking) {
    flash('error', 'Booking record not found.');
    redirect(url('/passenger/bookings.php'));
}

if ((int)$booking['user_id'] !== Auth::id() && !Auth::isAdmin()) {
    flash('error', 'Unauthorized access.');
    redirect(url('/passenger/dashboard.php'));
}

$title = "Booking Confirmed — PNR " . e($booking['booking_reference']);
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
        <div class="step-item completed">
            <div class="step-circle">✓</div>
            <div class="step-title">3. Demo Payment</div>
        </div>
        <div class="step-item active">
            <div class="step-circle">✓</div>
            <div class="step-title">4. Ticket Confirmed</div>
        </div>
    </div>

    <!-- Success Card -->
    <div class="card" style="box-shadow: var(--shadow-xl); overflow: hidden; text-align: center; padding: 40px 30px;">
        <div style="width: 72px; height: 72px; background: #d1fae5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px auto; border: 3px solid #a7f3d0;">
            ✓
        </div>

        <h1 style="font-size: 1.85rem; margin-bottom: 8px;">Congratulations! Your Booking is Confirmed</h1>
        <p class="lead" style="font-size: 1rem; margin-bottom: 24px;">
            A confirmation notification has been sent to your registered account.
        </p>

        <!-- PNR Pill -->
        <div style="display: inline-block; background: #f1f5f9; border: 2px dashed #94a3b8; border-radius: 12px; padding: 14px 28px; margin-bottom: 28px;">
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 600;">Booking Reference (PNR)</div>
            <div style="font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: 2px; margin-top: 4px;">
                <?= e($booking['booking_reference']) ?>
            </div>
        </div>

        <!-- Summary Details Grid -->
        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 24px; max-width: 600px; margin: 0 auto 30px auto; text-align: left;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.95rem;">
                <div>
                    <span class="text-muted">Bus Operator:</span><br>
                    <b><?= e($booking['operator_name']) ?></b>
                </div>
                <div>
                    <span class="text-muted">Coach Number:</span><br>
                    <b><?= e($booking['bus_number']) ?></b> (<?= e($booking['bus_type_name']) ?>)
                </div>
                <div>
                    <span class="text-muted">Route:</span><br>
                    <b><?= e($booking['source_city']) ?> → <?= e($booking['destination_city']) ?></b>
                </div>
                <div>
                    <span class="text-muted">Departure Date & Time:</span><br>
                    <b><?= format_date($booking['departure_datetime'], 'd M Y, h:i A') ?></b>
                </div>
                <div>
                    <span class="text-muted">Boarding Point:</span><br>
                    <b><?= e($booking['boarding_stop_name']) ?></b>
                </div>
                <div>
                    <span class="text-muted">Seats Reserved:</span><br>
                    <?php foreach ($booking['seats'] as $s): ?>
                        <span class="badge badge-primary"><?= e($s['seat_number']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid var(--border); margin:16px 0;">

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600;">Total Paid (Demo):</span>
                <span style="font-size: 1.35rem; font-weight: 800; color: #10b981;"><?= format_currency($booking['total_amount']) ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <a href="<?= url('/ticket.php?pnr=' . urlencode($booking['booking_reference'])) ?>" class="btn btn-primary btn-lg">
                🎫 View & Print E-Ticket
            </a>
            <a href="<?= url('/tracking.php?trip_id=' . $booking['trip_id']) ?>" class="btn btn-navy btn-lg">
                📍 Track Coach Live
            </a>
            <a href="<?= url('/passenger/dashboard.php') ?>" class="btn btn-outline btn-lg">
                Go to Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

