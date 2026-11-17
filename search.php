<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Trip;
use App\Models\Route;

$source = trim($_GET['source'] ?? 'Chennai');
$destination = trim($_GET['destination'] ?? 'Bengaluru');
$date = trim($_GET['date'] ?? date('Y-m-d'));

$busType = $_GET['bus_type'] ?? '';
$isAc = $_GET['is_ac'] ?? '';
$timeWindow = $_GET['time_window'] ?? '';
$minFare = $_GET['min_fare'] ?? '';
$maxFare = $_GET['max_fare'] ?? '';
$minRating = $_GET['min_rating'] ?? '';
$sort = $_GET['sort'] ?? 'departure_asc';

// Validation: Source and destination must differ
$validationError = '';
if (!empty($source) && !empty($destination) && strcasecmp($source, $destination) === 0) {
    $validationError = 'Source city and destination city cannot be identical. Please select different cities.';
}

$trips = [];
if (empty($validationError)) {
    if (!\App\Core\Database::isConnected()) {
        $validationError = "Cannot connect to MySQL server. Please make sure MySQL is started in your XAMPP Control Panel and database 'smartbus' is imported.";
    } else {
        try {
            $trips = Trip::search([
                'source' => $source,
                'destination' => $destination,
                'date' => $date,
                'bus_type' => $busType,
                'is_ac' => $isAc,
                'time_window' => $timeWindow,
                'min_fare' => $minFare,
                'max_fare' => $maxFare,
                'min_rating' => $minRating,
                'sort' => $sort
            ]);
        } catch (\Exception $e) {
            $validationError = $e->getMessage();
        }
    }
}

$cities = \App\Core\Database::isConnected() ? Route::getCities() : [];
$title = "Buses from {$source} to {$destination} on " . format_date($date) . " — SmartBus";
require_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="container">
    <!-- Inline Search Modification Bar -->
    <div class="card" style="margin: 20px 0; padding: 18px 24px; background: #ffffff;">
        <form action="<?= url('/search.php') ?>" method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 14px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:0.75rem;">From City</label>
                <input type="text" name="source" class="form-control" list="source-cities" value="<?= e($source) ?>" required>
                <datalist id="source-cities">
                    <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:0.75rem;">To City</label>
                <input type="text" name="destination" class="form-control" list="dest-cities" value="<?= e($destination) ?>" required>
                <datalist id="dest-cities">
                    <?php foreach ($cities as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:0.75rem;">Date of Journey</label>
                <input type="date" name="date" class="form-control" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" required>
            </div>

            <div style="margin-bottom:0;">
                <button type="submit" class="btn btn-navy" style="height:44px; padding:0 24px;">
                    Modify Search
                </button>
            </div>
        </form>
    </div>

    <!-- Main Results Page Layout -->
    <div class="search-page-layout">
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar">
            <form action="<?= url('/search.php') ?>" method="GET" id="filter-form">
                <input type="hidden" name="source" value="<?= e($source) ?>">
                <input type="hidden" name="destination" value="<?= e($destination) ?>">
                <input type="hidden" name="date" value="<?= e($date) ?>">

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:1.1rem;">Filters</h3>
                    <a href="<?= url('/search.php?source=' . urlencode($source) . '&destination=' . urlencode($destination) . '&date=' . urlencode($date)) ?>" style="font-size:0.8rem; color:#ef4444;">Reset</a>
                </div>

                <!-- Bus Category -->
                <div class="filter-group">
                    <div class="filter-title">Bus Type</div>
                    <label class="checkbox-label">
                        <input type="radio" name="bus_type" value="" <?= empty($busType) ? 'checked' : '' ?> onchange="this.form.submit()"> All Types
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="bus_type" value="sleeper" <?= $busType === 'sleeper' ? 'checked' : '' ?> onchange="this.form.submit()"> Sleeper Berths
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="bus_type" value="semi-sleeper" <?= $busType === 'semi-sleeper' ? 'checked' : '' ?> onchange="this.form.submit()"> Semi-Sleeper
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="bus_type" value="seater" <?= $busType === 'seater' ? 'checked' : '' ?> onchange="this.form.submit()"> Executive Seater
                    </label>
                </div>

                <!-- AC / Non-AC -->
                <div class="filter-group">
                    <div class="filter-title">Air Conditioning</div>
                    <label class="checkbox-label">
                        <input type="radio" name="is_ac" value="" <?= $isAc === '' ? 'checked' : '' ?> onchange="this.form.submit()"> All
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="is_ac" value="1" <?= $isAc === '1' ? 'checked' : '' ?> onchange="this.form.submit()"> AC Buses
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="is_ac" value="0" <?= $isAc === '0' ? 'checked' : '' ?> onchange="this.form.submit()"> Non-AC Buses
                    </label>
                </div>

                <!-- Departure Time Window -->
                <div class="filter-group">
                    <div class="filter-title">Departure Time</div>
                    <label class="checkbox-label">
                        <input type="radio" name="time_window" value="" <?= empty($timeWindow) ? 'checked' : '' ?> onchange="this.form.submit()"> Anytime
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="time_window" value="morning" <?= $timeWindow === 'morning' ? 'checked' : '' ?> onchange="this.form.submit()"> Morning (06:00 - 12:00)
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="time_window" value="afternoon" <?= $timeWindow === 'afternoon' ? 'checked' : '' ?> onchange="this.form.submit()"> Afternoon (12:00 - 18:00)
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="time_window" value="evening" <?= $timeWindow === 'evening' ? 'checked' : '' ?> onchange="this.form.submit()"> Evening (18:00 - 24:00)
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="time_window" value="night" <?= $timeWindow === 'night' ? 'checked' : '' ?> onchange="this.form.submit()"> Night (00:00 - 06:00)
                    </label>
                </div>

                <!-- Rating -->
                <div class="filter-group">
                    <div class="filter-title">Minimum Rating</div>
                    <label class="checkbox-label">
                        <input type="radio" name="min_rating" value="" <?= empty($minRating) ? 'checked' : '' ?> onchange="this.form.submit()"> Any Rating
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="min_rating" value="4.5" <?= $minRating === '4.5' ? 'checked' : '' ?> onchange="this.form.submit()"> 4.5+ ★ Star Rating
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="min_rating" value="4.0" <?= $minRating === '4.0' ? 'checked' : '' ?> onchange="this.form.submit()"> 4.0+ ★ Star Rating
                    </label>
                </div>
            </form>
        </aside>

        <!-- Search Results List -->
        <div>
            <!-- Results Header & Sort Controls -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h2 style="font-size:1.4rem;">
                        <?= e($source) ?> to <?= e($destination) ?>
                    </h2>
                    <span style="font-size:0.875rem; color:var(--text-muted);">
                        <?= count($trips) ?> buses found for <?= format_date($date) ?>
                    </span>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:0.85rem; font-weight:600; color:var(--text-muted);">Sort By:</span>
                    <select class="form-control" style="width:auto; padding:6px 12px; font-size:0.85rem;" onchange="location.href=this.value;">
                        <option value="<?= url('/search.php?' . http_build_query(array_merge($_GET, ['sort' => 'departure_asc']))) ?>" <?= $sort === 'departure_asc' ? 'selected' : '' ?>>Departure (Earliest)</option>
                        <option value="<?= url('/search.php?' . http_build_query(array_merge($_GET, ['sort' => 'departure_desc']))) ?>" <?= $sort === 'departure_desc' ? 'selected' : '' ?>>Departure (Latest)</option>
                        <option value="<?= url('/search.php?' . http_build_query(array_merge($_GET, ['sort' => 'fare_asc']))) ?>" <?= $sort === 'fare_asc' ? 'selected' : '' ?>>Fare: Low to High</option>
                        <option value="<?= url('/search.php?' . http_build_query(array_merge($_GET, ['sort' => 'fare_desc']))) ?>" <?= $sort === 'fare_desc' ? 'selected' : '' ?>>Fare: High to Low</option>
                        <option value="<?= url('/search.php?' . http_build_query(array_merge($_GET, ['sort' => 'duration_asc']))) ?>" <?= $sort === 'duration_asc' ? 'selected' : '' ?>>Fastest Journey</option>
                        <option value="<?= url('/search.php?' . http_build_query(array_merge($_GET, ['sort' => 'rating_desc']))) ?>" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Highest Rated</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($validationError)): ?>
                <div class="alert alert-danger">
                    <span>✕</span>
                    <div><?= e($validationError) ?></div>
                </div>
            <?php endif; ?>

            <?php if (empty($trips)): ?>
                <div class="card" style="padding: 60px 20px; text-align: center;">
                    <div style="font-size: 3.5rem; margin-bottom: 12px;">🚌</div>
                    <h3>No Buses Found on Selected Date</h3>
                    <p class="lead" style="max-width: 450px; margin: 10px auto 24px auto; font-size: 0.95rem;">
                        We couldn't find any scheduled buses between <?= e($source) ?> and <?= e($destination) ?> on <?= format_date($date) ?> matching your filters.
                    </p>
                    <div>
                        <a href="<?= url('/search.php?source=Chennai&destination=Bengaluru&date=' . date('Y-m-d')) ?>" class="btn btn-navy">
                            Try Chennai → Bengaluru (Today)
                        </a>
                        <a href="<?= url('/search.php?source=Chennai&destination=Coimbatore&date=' . date('Y-m-d')) ?>" class="btn btn-outline" style="margin-left: 10px;">
                            Try Chennai → Coimbatore
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($trips as $t): ?>
                    <div class="bus-card">
                        <div class="bus-card-header">
                            <!-- Operator Info -->
                            <div class="operator-info">
                                <h3><?= e($t['operator_name']) ?></h3>
                                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                    <span class="bus-type-tag"><?= e($t['bus_type_name']) ?></span>
                                    <span class="rating-badge">★ <?= number_format($t['rating'], 1) ?></span>
                                </div>
                                <div style="font-size:0.75rem; color:var(--text-light); margin-top:4px;">
                                    Coach: <?= e($t['bus_number']) ?>
                                </div>
                            </div>

                            <!-- Journey Schedule -->
                            <div class="journey-schedule">
                                <div class="time-point">
                                    <div class="time"><?= format_time($t['departure_datetime']) ?></div>
                                    <div class="place"><?= e($t['source_city']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-light);">
                                        <?= e($t['boarding_stops'][0]['stop_name'] ?? 'CMBT') ?>
                                    </div>
                                </div>

                                <div class="duration-indicator">
                                    <span class="duration-text"><?= format_duration($t['duration_minutes']) ?></span>
                                    <div class="duration-line"></div>
                                    <span style="font-size:0.7rem; color:var(--text-light);"><?= (int)$t['distance_km'] ?> km</span>
                                </div>

                                <div class="time-point">
                                    <div class="time"><?= format_time($t['arrival_datetime']) ?></div>
                                    <div class="place"><?= e($t['destination_city']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-light);">
                                        <?= e($t['dropping_stops'][0]['stop_name'] ?? 'Terminal') ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing & Action -->
                            <div class="price-booking">
                                <div class="fare"><?= format_currency($t['base_fare']) ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);">
                                    <b style="color:var(--success);"><?= $t['available_seats'] ?></b> seats left
                                </div>
                                <a href="<?= url('/seat_selection.php?trip_id=' . $t['id']) ?>" class="btn btn-primary btn-sm" style="margin-top:4px;">
                                    Select Seats
                                </a>
                            </div>
                        </div>

                        <!-- Amenities Bar -->
                        <div class="bus-card-footer">
                            <div class="amenities-list">
                                <?php foreach ($t['amenities'] as $am): ?>
                                    <span title="<?= e($am['description']) ?>" style="font-size:0.8rem; display:inline-flex; align-items:center; gap:4px;">
                                        ✓ <?= e($am['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div>
                                <a href="<?= url('/tracking.php?trip_id=' . $t['id']) ?>" style="font-size:0.8rem; font-weight:600; color:#2563eb;">
                                    📍 Live Route Track
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>

