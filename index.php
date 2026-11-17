<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Route;
use App\Models\Bus;
use App\Models\Review;
use App\Models\Trip;

// Check database connection gracefully to prevent uncaught fatal errors
$isDbConnected = \App\Core\Database::isConnected();
$cities = [];
$popularRoutes = [];
$featuredBuses = [];
$testimonials = [];
$dbError = null;

if ($isDbConnected) {
    try {
        $cities = Route::getCities();
        $popularRoutes = Route::getAll(true);
        $featuredBuses = Bus::getAll(true);
        $testimonials = Review::getApproved(3);
    } catch (\Exception $e) {
        $dbError = $e->getMessage();
    }
} else {
    $dbError = "MySQL server is currently stopped in XAMPP or database 'smartbus' has not been imported yet.";
}

$title = 'SmartBus — Book Intercity Bus Tickets Online';
require_once __DIR__ . '/../views/layouts/header.php';
?>

<?php if (!empty($dbError)): ?>
<div class="container" style="margin-top: 20px;">
    <div class="alert alert-warning" style="border: 2px dashed #f59e0b; background: #fffbeb; padding: 24px; border-radius: 12px;">
        <div style="display: flex; gap: 16px; align-items: flex-start;">
            <div style="font-size: 2rem;">⚠️</div>
            <div style="flex: 1;">
                <h3 style="color: #92400e; font-size: 1.25rem; margin-bottom: 6px;">Database Connection Setup Required</h3>
                <p style="color: #78350f; font-size: 0.95rem; margin-bottom: 12px;"><?= e($dbError) ?></p>
                <div style="background: #ffffff; padding: 14px 18px; border-radius: 8px; border: 1px solid #fde68a; font-size: 0.9rem; line-height: 1.6;">
                    <b>Quick 2-Step Fix:</b><br>
                    1. Open your <b>XAMPP Control Panel</b> and click <b>Start</b> next to <b>MySQL</b> (make sure the MySQL module turns green).<br>
                    2. Once MySQL is started, click the button below to initialize all tables and seed records automatically:
                </div>
                <div style="margin-top: 14px;">
                    <a href="<?= url('/../database/install.php') ?>" class="btn btn-primary btn-sm" style="background:#10b981; border-color:#059669; font-size: 0.95rem; padding: 8px 18px;">
                        🚀 Click Here to Run 1-Click Database Setup
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-content">
        <div class="hero-badge">
            🚍 India's Smartest Intercity Bus Network
        </div>
        <h1>Effortless Bus Travel Booking</h1>
        <p>Book luxury AC sleepers, semi-sleepers & express coaches with real-time seat selection, transparent fares & live tracking.</p>
    </div>
</section>

<!-- Search Box (Overlapping) -->
<div class="container search-widget-container">
    <div class="search-box">
        <form action="<?= url('/search.php') ?>" method="GET" class="search-form" id="home-search-form">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="source-input">From (Source City)</label>
                <input type="text" id="source-input" name="source" class="form-control" list="source-cities-list" 
                       placeholder="e.g. Chennai" value="Chennai" required>
                <datalist id="source-cities-list">
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <button type="button" class="swap-btn" id="swap-cities-btn" title="Swap Source & Destination">
                ⇄
            </button>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="dest-input">To (Destination City)</label>
                <input type="text" id="dest-input" name="destination" class="form-control" list="dest-cities-list" 
                       placeholder="e.g. Bengaluru" value="Bengaluru" required>
                <datalist id="dest-cities-list">
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="date-input">Journey Date</label>
                <input type="date" id="date-input" name="date" class="form-control" 
                       value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div style="margin-bottom:0;">
                <button type="submit" class="btn btn-primary btn-lg" style="height:48px; width:100%;">
                    🔍 Search Buses
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Popular Routes Section -->
<section class="popular-routes">
    <div class="container">
        <div class="section-header">
            <h2>Trending & Popular Bus Routes</h2>
            <p>Direct routes connecting major metropolitan and commercial hubs</p>
        </div>

        <div class="route-grid">
            <?php foreach (array_slice($popularRoutes, 0, 12) as $r): ?>
                <a href="<?= url('/search.php?source=' . urlencode($r['source_city']) . '&destination=' . urlencode($r['destination_city']) . '&date=' . date('Y-m-d')) ?>" class="route-card">
                    <div class="route-title">
                        <span><?= e($r['source_city']) ?> → <?= e($r['destination_city']) ?></span>
                        <span style="font-size:1.1rem; color:var(--accent);">➔</span>
                    </div>
                    <div class="route-meta">
                        <span><?= (int)$r['distance_km'] ?> km • <?= format_duration($r['duration_minutes']) ?></span>
                        <span class="route-price">Fares from ₹550</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why SmartBus Benefits -->
<section style="padding: 60px 0; background: #ffffff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <h2>Why Travelers Choose SmartBus</h2>
            <p>Designed for comfort, speed, and safety on every journey</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
            <div class="card" style="padding: 28px; text-align: center;">
                <div style="width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px auto;">
                    💺
                </div>
                <h3 style="margin-bottom: 8px;">Guaranteed Seat Selection</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Choose your exact window or aisle berth with realistic 2+1 sleeper and 2+2 luxury seater layouts.
                </p>
            </div>

            <div class="card" style="padding: 28px; text-align: center;">
                <div style="width: 60px; height: 60px; background: #dbeafe; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px auto;">
                    📍
                </div>
                <h3 style="margin-bottom: 8px;">Live GPS Tracking</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Share live bus location coordinates, estimated arrival times, and boarding delay alerts with loved ones.
                </p>
            </div>

            <div class="card" style="padding: 28px; text-align: center;">
                <div style="width: 60px; height: 60px; background: #d1fae5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px auto;">
                    🛡️
                </div>
                <h3 style="margin-bottom: 8px;">Instant Refund Policy</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Plans changed? Cancel up to 2 hours before departure and receive your refund with transparent charges.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 4 Simple Booking Steps -->
<section style="padding: 60px 0;">
    <div class="container">
        <div class="section-header">
            <h2>How It Works in 4 Simple Steps</h2>
            <p>From search to boarding in under 2 minutes</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; text-align: center;">
            <div style="padding: 20px;">
                <div style="font-size: 2rem; font-weight: 800; color: #ef4444; margin-bottom: 8px;">01</div>
                <h4 style="margin-bottom: 8px;">Search Route</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Select source city, destination, and travel date.</p>
            </div>

            <div style="padding: 20px;">
                <div style="font-size: 2rem; font-weight: 800; color: #ef4444; margin-bottom: 8px;">02</div>
                <h4 style="margin-bottom: 8px;">Pick Your Seat</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem;">View interactive sleeper or seater layout and select berths.</p>
            </div>

            <div style="padding: 20px;">
                <div style="font-size: 2rem; font-weight: 800; color: #ef4444; margin-bottom: 8px;">03</div>
                <h4 style="margin-bottom: 8px;">Demo Payment</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Apply discount coupons and simulate instant UPI or card checkout.</p>
            </div>

            <div style="padding: 20px;">
                <div style="font-size: 2rem; font-weight: 800; color: #ef4444; margin-bottom: 8px;">04</div>
                <h4 style="margin-bottom: 8px;">Get Digital Ticket</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Download or print your QR-verified ticket and board the coach.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<?php if (!empty($testimonials)): ?>
<section style="padding: 60px 0; background: #ffffff; border-top: 1px solid var(--border);">
    <div class="container">
        <div class="section-header">
            <h2>Real Passenger Reviews</h2>
            <p>Verified passenger feedback from recent trips</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <?php foreach ($testimonials as $rev): ?>
                <div class="card" style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="color: #f59e0b; font-size: 1.1rem;"><?= str_repeat('★', (int)$rev['rating']) ?></span>
                        <span style="font-size: 0.8rem; color: var(--text-muted);"><?= format_date($rev['created_at']) ?></span>
                    </div>
                    <p style="font-style: italic; color: #334155; margin-bottom: 16px; font-size: 0.95rem;">
                        "<?= e($rev['comment']) ?>"
                    </p>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?= strtoupper(substr($rev['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem;"><?= e($rev['full_name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($rev['source_city']) ?> → <?= e($rev['destination_city']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Preview -->
<section style="padding: 60px 0;">
    <div class="container container-narrow">
        <div class="section-header">
            <h2>Frequently Asked Questions</h2>
            <p>Common questions about booking, payments, and cancellations</p>
        </div>

        <div class="card" style="padding: 20px 28px; margin-bottom: 14px;">
            <h4 style="margin-bottom: 6px;">How do I select my seats?</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Search for your route, choose a bus, and click "View Seats". You can interactively click available sleeper berths or pushback seats.
            </p>
        </div>

        <div class="card" style="padding: 20px 28px; margin-bottom: 14px;">
            <h4 style="margin-bottom: 6px;">How does demo payment simulation work?</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                SmartBus is an academic demonstration project. No real bank accounts or cards are charged. You can test instant simulated approval or test failure handling.
            </p>
        </div>

        <div class="card" style="padding: 20px 28px;">
            <h4 style="margin-bottom: 6px;">What is the cancellation and refund policy?</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Cancellations made 24+ hours before departure receive 90% refund, 12-24 hours receive 75%, and 2-12 hours receive 50%.
            </p>
        </div>

        <div style="text-align: center; margin-top: 24px;">
            <a href="<?= url('/pages/faq.php') ?>" class="btn btn-outline btn-sm">View All FAQs →</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

