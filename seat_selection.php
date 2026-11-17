<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Trip;
use App\Services\BookingService;
use App\Core\Database;

$tripId = (int)($_GET['trip_id'] ?? 0);
$trip = Trip::findById($tripId);

if (!$trip) {
    flash('error', 'The requested trip could not be found.');
    redirect(url('/search.php'));
}

// ── Release reservations so seats are always bookable when visiting this page ──
try {
    $db = Database::getConnection();

    // 1. Release ALL expired PENDING bookings for this trip (any user)
    $db->prepare("
        UPDATE trip_seats
        SET status = 'available'
        WHERE trip_id = ? AND seat_number IN (
            SELECT bs.seat_number FROM booking_seats bs
            JOIN bookings b ON bs.booking_id = b.id
            WHERE b.trip_id = ? AND b.booking_status = 'PENDING' AND b.expires_at < NOW()
        )
    ")->execute([$tripId, $tripId]);

    $db->prepare("
        UPDATE bookings SET booking_status = 'EXPIRED', updated_at = NOW()
        WHERE trip_id = ? AND booking_status = 'PENDING' AND expires_at < NOW()
    ")->execute([$tripId]);

    // 2. If logged in: also cancel THIS user's own PENDING booking for this trip
    //    (handles the case where user goes back from payment page without paying)
    if (Auth::check()) {
        $userId = Auth::id();

        $myPendingStmt = $db->prepare("
            SELECT id FROM bookings
            WHERE trip_id = ? AND user_id = ? AND booking_status = 'PENDING'
            ORDER BY id DESC LIMIT 1
        ");
        $myPendingStmt->execute([$tripId, $userId]);
        $myPending = $myPendingStmt->fetchColumn();

        if ($myPending) {
            // Release the seats
            $db->prepare("
                UPDATE trip_seats
                SET status = 'available'
                WHERE trip_id = ? AND seat_number IN (
                    SELECT seat_number FROM booking_seats WHERE booking_id = ?
                )
            ")->execute([$tripId, $myPending]);

            // Mark booking as expired/cancelled
            $db->prepare("
                UPDATE bookings SET booking_status = 'EXPIRED', updated_at = NOW() WHERE id = ?
            ")->execute([$myPending]);
        }
    }
} catch (\Exception $e) {
    error_log('Seat cleanup error: ' . $e->getMessage());
}
// ─────────────────────────────────────────────────────────────────────────────

$seatLayout = Trip::getSeatLayout($tripId);
$hasUpperDeck = (bool)$trip['has_upper_deck'];
$error = '';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();

    // Check login
    if (!Auth::check()) {
        flash('warning', 'Please sign in or register to complete your seat reservation.');
        redirect(url('/login.php?redirect=' . urlencode(url('/seat_selection.php?trip_id=' . $tripId))));
    }

    $selectedSeats = $_POST['seats'] ?? [];
    $passengers = $_POST['passengers'] ?? [];
    $boardingStopId = (int)($_POST['boarding_stop_id'] ?? 0);
    $droppingStopId = (int)($_POST['dropping_stop_id'] ?? 0);
    $couponCode = trim($_POST['coupon_code'] ?? '');

    if (empty($selectedSeats)) {
        $error = 'Please select at least one seat to proceed.';
    } elseif ($boardingStopId <= 0 || $droppingStopId <= 0) {
        $error = 'Please select your boarding and dropping points.';
    } elseif (count($passengers) !== count($selectedSeats)) {
        $error = 'Passenger details are missing or incomplete. Got ' . count($passengers) . ' passenger record(s) for ' . count($selectedSeats) . ' seat(s). Please fill in name and age for every selected seat and try again.';
    } else {
        // Validate each passenger entry has name and age
        $passengerError = '';
        foreach ($passengers as $idx => $p) {
            if (empty(trim($p['name'] ?? ''))) {
                $passengerError = 'Please enter the name for passenger ' . ($idx + 1) . '.';
                break;
            }
            if (empty($p['age']) || (int)$p['age'] < 1) {
                $passengerError = 'Please enter a valid age for passenger ' . ($idx + 1) . '.';
                break;
            }
        }

        if ($passengerError) {
            $error = $passengerError;
        } else {
            try {
                $bookingResult = BookingService::createBooking(
                    Auth::id(),
                    $tripId,
                    $selectedSeats,
                    $passengers,
                    $boardingStopId,
                    $droppingStopId,
                    $couponCode
                );

                // Redirect to demo payment page
                redirect(url('/payment_demo.php?booking_id=' . $bookingResult['booking_id']));
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$title = "Select Seats — {$trip['operator_name']} ({$trip['source_city']} to {$trip['destination_city']})";
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container">
    <!-- Booking Steps Indicator -->
    <div class="steps-indicator">
        <div class="step-item completed">
            <div class="step-circle">✓</div>
            <div class="step-title">1. Search Bus</div>
        </div>
        <div class="step-item active">
            <div class="step-circle">2</div>
            <div class="step-title">2. Select Seats & Details</div>
        </div>
        <div class="step-item">
            <div class="step-circle">3</div>
            <div class="step-title">3. Demo Payment</div>
        </div>
        <div class="step-item">
            <div class="step-circle">4</div>
            <div class="step-title">4. Ticket Confirmation</div>
        </div>
    </div>

    <!-- Trip Header Card -->
    <div class="card" style="margin-bottom: 24px; padding: 20px 24px; background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="badge badge-secondary"><?= e($trip['bus_type_name']) ?></span>
                <h2 style="font-size: 1.35rem; margin-top: 4px;"><?= e($trip['operator_name']) ?> (<?= e($trip['bus_number']) ?>)</h2>
                <div style="font-size: 0.9rem; color: var(--text-muted);">
                    <?= e($trip['source_city']) ?> → <?= e($trip['destination_city']) ?> • Departure: <?= format_date($trip['departure_datetime'], 'd M Y, h:i A') ?>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 0.8rem; color: var(--text-muted);">Base Fare per seat</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);"><?= format_currency($trip['base_fare']) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <span>✕</span>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="<?= url('/seat_selection.php?trip_id=' . $tripId) ?>" method="POST" id="seat-booking-form">
        <?= csrf_field() ?>
        <input type="hidden" id="trip-base-fare" value="<?= (float)$trip['base_fare'] ?>">
        <input type="hidden" name="coupon_code" id="hidden-applied-coupon" value="">
        <div id="hidden-seat-inputs"></div>

        <div class="seat-selection-wrapper">
            <!-- Left: Interactive Bus Layout & Passenger Details -->
            <div>
                <!-- Boarding & Dropping Points Selectors -->
                <div class="card" style="margin-bottom: 24px; padding: 20px;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 14px;">Boarding & Dropping Points</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Boarding Point (Departure)</label>
                            <select name="boarding_stop_id" class="form-control" required>
                                <?php foreach ($trip['boarding_stops'] as $bs): ?>
                                    <option value="<?= $bs['stop_id'] ?>">
                                        <?= e($bs['stop_name']) ?> (<?= e($bs['landmark'] ?? $bs['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Dropping Point (Arrival)</label>
                            <select name="dropping_stop_id" class="form-control" required>
                                <?php foreach ($trip['dropping_stops'] as $ds): ?>
                                    <option value="<?= $ds['stop_id'] ?>">
                                        <?= e($ds['stop_name']) ?> (<?= e($ds['landmark'] ?? $ds['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bus Seat Chassis -->
                <div class="bus-chassis">
                    <div class="bus-front">
                        <div class="driver-cabin">
                            <div class="steering-wheel"></div>
                            <span>Driver Cabin (Front)</span>
                        </div>
                        <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">
                            Entrance Door ▶
                        </div>
                    </div>

                    <?php if ($hasUpperDeck): ?>
                        <div class="deck-tabs">
                            <button type="button" class="deck-tab-btn active" data-deck="lower">Lower Deck</button>
                            <button type="button" class="deck-tab-btn" data-deck="upper">Upper Deck</button>
                        </div>
                    <?php endif; ?>

                    <!-- Lower Deck -->
                    <div class="deck-view" data-deck="lower">
                        <div class="seat-grid">
                            <?php foreach ($seatLayout['lower'] as $rowNum => $rowSeats): ?>
                                <div class="seat-row">
                                    <div class="seat-col-pair">
                                        <?php 
                                        $leftSeats = array_slice($rowSeats, 0, count($rowSeats) > 3 ? 2 : 1);
                                        foreach ($leftSeats as $s): 
                                            $isSleeper = ($s['seat_type'] === 'sleeper');
                                        ?>
                                            <div class="seat <?= $isSleeper ? 'seat-sleeper' : 'seat-seater' ?> <?= e($s['status']) ?>"
                                                 data-seat-num="<?= e($s['seat_number']) ?>"
                                                 data-fare-mult="<?= e($s['fare_multiplier']) ?>"
                                                 title="Seat <?= e($s['seat_number']) ?> (<?= ucfirst($s['status']) ?>)">
                                                <?= e($s['seat_number']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="aisle-space">AISLE</div>

                                    <div class="seat-col-pair">
                                        <?php 
                                        $rightSeats = array_slice($rowSeats, count($rowSeats) > 3 ? 2 : 1);
                                        foreach ($rightSeats as $s): 
                                            $isSleeper = ($s['seat_type'] === 'sleeper');
                                        ?>
                                            <div class="seat <?= $isSleeper ? 'seat-sleeper' : 'seat-seater' ?> <?= e($s['status']) ?>"
                                                 data-seat-num="<?= e($s['seat_number']) ?>"
                                                 data-fare-mult="<?= e($s['fare_multiplier']) ?>"
                                                 title="Seat <?= e($s['seat_number']) ?> (<?= ucfirst($s['status']) ?>)">
                                                <?= e($s['seat_number']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Upper Deck (if available) -->
                    <?php if ($hasUpperDeck): ?>
                        <div class="deck-view" data-deck="upper" style="display: none;">
                            <div class="seat-grid">
                                <?php foreach ($seatLayout['upper'] as $rowNum => $rowSeats): ?>
                                    <div class="seat-row">
                                        <div class="seat-col-pair">
                                            <?php 
                                            $leftSeats = array_slice($rowSeats, 0, 1);
                                            foreach ($leftSeats as $s): 
                                            ?>
                                                <div class="seat seat-sleeper <?= e($s['status']) ?>"
                                                     data-seat-num="<?= e($s['seat_number']) ?>"
                                                     data-fare-mult="<?= e($s['fare_multiplier']) ?>"
                                                     title="Upper Berth <?= e($s['seat_number']) ?> (<?= ucfirst($s['status']) ?>)">
                                                    <?= e($s['seat_number']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="aisle-space">AISLE</div>

                                        <div class="seat-col-pair">
                                            <?php 
                                            $rightSeats = array_slice($rowSeats, 1);
                                            foreach ($rightSeats as $s): 
                                            ?>
                                                <div class="seat seat-sleeper <?= e($s['status']) ?>"
                                                     data-seat-num="<?= e($s['seat_number']) ?>"
                                                     data-fare-mult="<?= e($s['fare_multiplier']) ?>"
                                                     title="Upper Berth <?= e($s['seat_number']) ?> (<?= ucfirst($s['status']) ?>)">
                                                    <?= e($s['seat_number']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Seat Legend -->
                    <div class="seat-legend">
                        <div class="legend-item">
                            <div class="legend-color avail"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color sel"></div>
                            <span>Selected</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color book"></div>
                            <span>Already Booked</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Passenger Details Form -->
                <div class="card" style="margin-top: 24px; padding: 24px;">
                    <h3 style="font-size: 1.15rem; margin-bottom: 6px;">Passenger Information</h3>
                    <p class="lead" style="font-size: 0.85rem; margin-bottom: 16px;">
                        Provide the legal name and age as per Govt. ID for each selected seat.
                    </p>

                    <div id="passenger-inputs-container">
                        <p class="text-muted" style="font-size: 0.9rem;">Please click on one or more available seats above to enter passenger details.</p>
                    </div>
                </div>
            </div>

            <!-- Right: Fare Breakdown & Coupon Sidebar -->
            <div>
                <div class="booking-summary-card">
                    <h3 style="font-size: 1.2rem; margin-bottom: 16px;">Booking Summary</h3>

                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px;">Selected Seats:</div>
                    <div id="selected-seats-pills" class="selected-seats-badge-list">
                        <span class="text-muted" style="font-size: 0.85rem;">No seats selected yet</span>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--border); margin: 16px 0;">

                    <!-- Coupon Code Section -->
                    <div style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 0.8rem;">Have a Promo Coupon?</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="coupon-code-input" class="form-control" placeholder="e.g. WELCOME50" style="text-transform: uppercase;">
                            <button type="button" id="apply-coupon-btn" class="btn btn-navy btn-sm">Apply</button>
                        </div>
                        <div id="coupon-message" style="font-size: 0.75rem; margin-top: 4px;"></div>
                        <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;">Try code: <b>WELCOME50</b> or <b>SMART100</b></div>
                    </div>

                    <div class="summary-row">
                        <span>Total Seats</span>
                        <span id="summary-seat-count" style="font-weight: 600;">0</span>
                    </div>

                    <div class="summary-row">
                        <span>Base Fare</span>
                        <span id="summary-base-fare" style="font-weight: 600;">₹ 0.00</span>
                    </div>

                    <div class="summary-row" style="color: #10b981;">
                        <span>Coupon Discount</span>
                        <span id="summary-discount" style="font-weight: 600;">- ₹ 0.00</span>
                    </div>

                    <div class="summary-row">
                        <span>GST (5%)</span>
                        <span id="summary-tax" style="font-weight: 600;">₹ 0.00</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total Payable</span>
                        <span id="summary-total">₹ 0.00</span>
                    </div>

                    <button type="submit" id="proceed-to-book-btn" class="btn btn-primary btn-block btn-lg" style="margin-top: 20px;" disabled>
                        Proceed to Demo Payment ➔
                    </button>

                    <div style="text-align: center; margin-top: 12px; font-size: 0.75rem; color: var(--text-light);">
                        🔒 Safe & Secure 256-Bit SSL Demo Checkout
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="<?= asset('js/seat-selection.js') ?>"></script>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

