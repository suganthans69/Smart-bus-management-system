<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Models\Booking;
use App\Services\QrCodeService;

$pnr = trim($_GET['pnr'] ?? '');
$booking = Booking::findByReference($pnr);

if (!$booking) {
    flash('error', 'Ticket not found for the requested PNR reference.');
    redirect(url('/'));
}

// Generate verification link for QR code
// Note: We use dynamic URL so it works on any local port/subpath
$verificationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('/ticket_verify.php?pnr=' . urlencode($booking['booking_reference']));
$qrSvg = QrCodeService::generateSvg($verificationUrl, 130);

$title = "E-Ticket — PNR " . e($booking['booking_reference']) . " — SmartBus";
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container container-narrow" style="padding: 30px 20px 80px 20px;">
    <!-- Print / Action Toolbar -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;" class="no-print">
        <a href="<?= url('/passenger/bookings.php') ?>" class="btn btn-outline btn-sm">
            ← Back to Bookings
        </a>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="window.print()" class="btn btn-navy btn-sm">
                🖨️ Print Ticket / Save as PDF
            </button>
            <a href="<?= url('/tracking.php?trip_id=' . $booking['trip_id']) ?>" class="btn btn-primary btn-sm">
                📍 Track Bus Live
            </a>
        </div>
    </div>

    <!-- Official E-Ticket Card -->
    <div class="card ticket-card" style="box-shadow: var(--shadow-xl); border: 2px solid #cbd5e1; border-radius: 16px; overflow: hidden; background: #ffffff;">
        <!-- Ticket Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 24px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #ef4444;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="<?= asset('images/logo.svg') ?>" alt="Logo" width="36" height="36">
                <div>
                    <div style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px;">Smart<span style="color:#ef4444;">Bus</span></div>
                    <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Official Electronic Travel Pass</div>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 600;">PNR Number</div>
                <div style="font-size: 1.4rem; font-weight: 900; color: #38bdf8; letter-spacing: 1px;"><?= e($booking['booking_reference']) ?></div>
            </div>
        </div>

        <!-- Ticket Body -->
        <div style="padding: 30px;">
            <!-- Journey Overview Grid -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; padding-bottom: 24px; border-bottom: 2px dashed #e2e8f0; margin-bottom: 24px;">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Bus Operator & Coach</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #0f172a;"><?= e($booking['operator_name']) ?></div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 2px;">
                        Vehicle Reg: <b><?= e($booking['bus_number']) ?></b> • <?= e($booking['bus_type_name']) ?>
                    </div>
                </div>

                <div style="text-align: right;">
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Ticket Status</div>
                    <?php if ($booking['booking_status'] === 'CONFIRMED'): ?>
                        <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 12px;">CONFIRMED</span>
                    <?php elseif ($booking['booking_status'] === 'CANCELLED'): ?>
                        <span class="badge badge-danger" style="font-size: 0.85rem; padding: 6px 12px;">CANCELLED</span>
                    <?php else: ?>
                        <span class="badge badge-warning" style="font-size: 0.85rem; padding: 6px 12px;"><?= e($booking['booking_status']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Route & Timings Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding-bottom: 24px; border-bottom: 2px dashed #e2e8f0; margin-bottom: 24px;">
                <!-- Departure Point -->
                <div style="background: #f8fafc; padding: 18px; border-radius: 10px; border-left: 4px solid #10b981;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #10b981;">Departure / Boarding</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 4px 0;"><?= format_time($booking['departure_datetime']) ?></div>
                    <div style="font-weight: 700; font-size: 1rem; color: #1e293b;"><?= e($booking['source_city']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        Point: <b><?= e($booking['boarding_stop_name']) ?></b> (<?= e($booking['boarding_landmark']) ?>)
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;">
                        Date: <?= format_date($booking['departure_datetime'], 'l, d F Y') ?>
                    </div>
                </div>

                <!-- Arrival Point -->
                <div style="background: #f8fafc; padding: 18px; border-radius: 10px; border-left: 4px solid #ef4444;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #ef4444;">Arrival / Dropping</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 4px 0;"><?= format_time($booking['arrival_datetime']) ?></div>
                    <div style="font-weight: 700; font-size: 1rem; color: #1e293b;"><?= e($booking['destination_city']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                        Point: <b><?= e($booking['dropping_stop_name']) ?></b> (<?= e($booking['dropping_landmark']) ?>)
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 2px;">
                        Date: <?= format_date($booking['arrival_datetime'], 'l, d F Y') ?>
                    </div>
                </div>
            </div>

            <!-- Passengers & QR Verification Section -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; padding-bottom: 24px; border-bottom: 2px dashed #e2e8f0; margin-bottom: 24px; align-items: center;">
                <!-- Passengers Table -->
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 10px;">Passenger List</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1.5px solid #cbd5e1; text-align: left;">
                                <th style="padding: 6px 0; color: var(--text-muted);">#</th>
                                <th style="padding: 6px 0; color: var(--text-muted);">Name</th>
                                <th style="padding: 6px 0; color: var(--text-muted);">Age</th>
                                <th style="padding: 6px 0; color: var(--text-muted);">Gender</th>
                                <th style="padding: 6px 0; color: var(--text-muted); text-align: right;">Seat No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($booking['passengers'] as $idx => $p): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 0; font-weight: 600;"><?= $idx + 1 ?></td>
                                    <td style="padding: 8px 0; font-weight: 700; color: #0f172a;"><?= e($p['passenger_name']) ?></td>
                                    <td style="padding: 8px 0;"><?= (int)$p['passenger_age'] ?> yrs</td>
                                    <td style="padding: 8px 0; text-transform: capitalize;"><?= e($p['passenger_gender']) ?></td>
                                    <td style="padding: 8px 0; text-align: right;">
                                        <span class="badge badge-primary"><?= e($p['seat_number']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- QR Code Verification Box -->
                <div style="text-align: center; border-left: 1px solid #e2e8f0; padding-left: 20px;">
                    <?= $qrSvg ?>
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; margin-top: 6px;">
                        Scan QR to Verify Authenticity
                    </div>
                </div>
            </div>

            <!-- Fare & Payment Summary -->
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; max-width: 380px;">
                    <b>Terms & Instructions:</b><br>
                    • Please arrive at the boarding point at least 15 minutes before scheduled departure.<br>
                    • Passengers must carry a valid Government-issued Photo ID.<br>
                    • Digital display of this e-ticket on smartphone is legally accepted by conductor.
                </div>

                <div style="min-width: 240px; text-align: right;">
                    <div style="font-size: 0.85rem; display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span class="text-muted">Base Fare (<?= $booking['total_seats'] ?> Seats):</span>
                        <span><?= format_currency($booking['base_amount']) ?></span>
                    </div>

                    <?php if ((float)$booking['discount_amount'] > 0): ?>
                        <div style="font-size: 0.85rem; color: #10b981; display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Promo Discount:</span>
                            <span>- <?= format_currency($booking['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div style="font-size: 0.85rem; display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span class="text-muted">GST & Toll:</span>
                        <span><?= format_currency($booking['tax_amount']) ?></span>
                    </div>

                    <div style="border-top: 2px solid #0f172a; padding-top: 6px; display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 900; color: #0f172a;">
                        <span>Total Paid:</span>
                        <span style="color: #ef4444;"><?= format_currency($booking['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Footer Bar -->
        <div style="background: #f8fafc; border-top: 1px solid var(--border); padding: 14px 30px; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #64748b;">
            <div>Helpline: +91 98765 43210 • Email: support@smartbus.com</div>
            <div>Issued by SmartBus Reservation System</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

