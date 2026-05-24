<?php
include 'db.php';
session_start();

$search = $_GET['search'] ?? '';

$sql = "
    SELECT e.*, o.org_name, 
    (e.maximum_capacity - (SELECT COUNT(*) FROM rsvp r WHERE r.event_id = e.event_id)) as spots_left
    FROM event e
    JOIN organization o ON e.organization_id = o.user_id
";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE e.title ILIKE ? OR o.org_name ILIKE ? OR e.description ILIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY e.start_datetime ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
$dashboard_link = ($role === 'org') ? 'org_dashboard.php' : 'student_dashboard.php';

$events_upcoming = array_values(array_filter($events, fn($e) => strtotime($e['start_datetime']) > time()));
$events_finished = array_values(array_filter($events, fn($e) => strtotime($e['end_datetime']) < time()));
$events_all      = array_values($events);
$gradients = ['linear-gradient(135deg, #76D7C4, #48C9B0)', 'linear-gradient(135deg, #FAD7A0, #E67E22)', 'linear-gradient(135deg, #85929E, #34495E)'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Univents</title>
    <link rel="stylesheet" href="about-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        header { padding: 20px 0; background: #F9F7F2; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .nav-wrapper { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 5%; }
        .logo { font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none; }
        nav ul { display: flex; list-style: none; gap: 40px; margin: 0; padding: 0; }
        nav ul li a { text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem; transition: 0.3s; }
        nav ul li a.active { color: #4BA68D; border-bottom: 2px solid #4BA68D; padding-bottom: 5px; }
        .nav-buttons { display: flex; align-items: center; gap: 25px; }
        .btn-text { text-decoration: none; color: #333; font-weight: 700; font-size: 0.9rem; }
        .btn-primary-nav { background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; }

        .events-hero { background: #326257; padding: 80px 0; color: white; position: relative; overflow: hidden; }
        .hero-circle-lg { position: absolute; width: 500px; height: 500px; background: rgba(255,255,255,0.08); border-radius: 50%; right: -100px; top: -50px; }
        .hero-circle-sm { position: absolute; width: 300px; height: 300px; background: rgba(0,0,0,0.15); border-radius: 50%; right: 50px; bottom: -100px; }
        .hero-title { font-family: 'Montserrat'; font-size: 4.5rem; line-height: 0.9; margin: 15px 0; font-weight: 900; }
        .hero-desc { max-width: 400px; opacity: 0.8; font-size: 0.9rem; margin-bottom: 30px; }
        .search-container { display: flex; gap: 10px; max-width: 600px; }
        .search-input { flex: 1; padding: 15px 25px; border-radius: 12px; border: none; background: rgba(255,255,255,0.2); color: white; outline: none; }
        .search-input::placeholder { color: rgb(114, 186, 169); }
        .btn-search { background: #E68A6E; border: none; padding: 0 30px; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; }

        .e-card { background: white; border-radius: 25px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: flex; flex-direction: column; transition: 0.3s; }
        .e-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .e-card-header { height: 160px; padding: 30px; display: flex; align-items: flex-end; }
        .cat-tag { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 8px; color: white; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .e-card-body { padding: 30px; flex: 1; display: flex; flex-direction: column; }
        .e-org { font-size: 0.75rem; color: #888; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .e-org::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #5DADE2; display: inline-block; }
        .e-title { font-family: 'Montserrat'; font-size: 1.4rem; margin: 15px 0; line-height: 1.2; flex: 1; font-weight: 900; }
        .e-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.8rem; color: #666; margin-bottom: 20px; }
        .e-card-footer { border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .spots-label { color: #E67E22; font-weight: 700; font-size: 0.9rem; }
        .btn-view-details { background: #F1948A; color: white; padding: 12px 25px; border-radius: 12px; font-weight: 900; text-decoration: none; font-size: 0.8rem; transition: 0.2s; }

        /* Tab bar */
        .tab-bar-events { display: flex; gap: 8px; margin-bottom: 36px; border-bottom: 2px solid #e0dbd0; }
        .tab-btn-ev {
            padding: 13px 28px; background: none; border: none; cursor: pointer;
            font-family: 'Montserrat'; font-weight: 800; font-size: 0.85rem;
            color: #aaa; letter-spacing: 0.5px; position: relative; bottom: -2px;
            border-bottom: 3px solid transparent; transition: 0.2s;
        }
        .tab-btn-ev:hover { color: #555; }
        .tab-btn-ev.active { color: #326257; border-bottom-color: #326257; }
        .tab-count-ev { background: #eee; color: #888; border-radius: 20px; padding: 2px 9px; font-size: 0.75rem; margin-left: 7px; }
        .tab-btn-ev.active .tab-count-ev { background: #cce8e3; color: #326257; }

        /* Tab panels */
        .tab-panel-ev { display: none; }
        .tab-panel-ev.active { display: block; }

        /* Card grid inside each tab */
        .ev-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            min-height: 200px;
        }

        .empty-ev { display: flex; justify-content: center; align-items: center; padding: 80px 20px; color: #bbb; font-size: 1rem; grid-column: 1 / -1; }

        /* All cards hidden by default — JS shows current page */
        .ev-card-item { display: none; }
        .ev-card-item.visible { display: flex; flex-direction: column; }

        /* Pagination */
        .pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 32px 0 60px;
            padding-top: 20px;
            border-top: 1px solid #e8e3d8;
            gap: 12px;
            flex-wrap: wrap;
        }
        .pagination-info {
            font-size: 0.8rem;
            color: #aaa;
            font-family: 'Inter';
        }
        .pagination-controls { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .page-btn {
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border: 1.5px solid #e0dbd0;
            border-radius: 10px;
            background: white;
            color: #555;
            font-family: 'Montserrat';
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.18s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .page-btn:hover:not(:disabled) { border-color: #326257; color: #326257; background: #f0faf8; }
        .page-btn.active { background: #326257; color: white; border-color: #326257; }
        .page-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .page-ellipsis { font-size: 0.8rem; color: #bbb; padding: 0 3px; }
    </style>
</head>
<body style="background: #F9F7F2;">

    <header>
        <div class="nav-wrapper">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
                <img src="logo.png" alt="Univents Logo" style="height: 38px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
                Univents
            </a>
            <?php if (!$is_logged_in): ?>
                <nav>
                    <ul>
                        <li><a href="index.php">HOME</a></li>
                        <li><a href="events.php" class="active">EVENTS</a></li>
                        <li><a href="rsvps.php">RSVPs</a></li>
                        <li><a href="about.php">ABOUT</a></li>
                    </ul>
                </nav>
                <div class="nav-buttons">
                    <a href="register.php" class="btn-text">SIGN-UP</a>
                    <a href="login.php" class="btn-primary-nav">LOG-IN</a>
                </div>
            <?php else: ?>
                <div class="nav-buttons">
                    <a href="<?= $dashboard_link ?>" style="text-decoration: none; color: #326257; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                        <i class='bx bx-arrow-back'></i> BACK TO DASHBOARD
                    </a>
                    <a href="logout.php" class="btn-primary-nav">LOGOUT</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <section class="events-hero">
        <div class="hero-circle-lg"></div>
        <div class="hero-circle-sm"></div>
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">
            <span class="sub-label" style="color: #FAD7A0; font-weight: 700; text-transform: uppercase;">Campus Events</span>
            <h1 class="hero-title">WHAT'S HAPPENING <br> ON CAMPUS</h1>
            <p class="hero-desc">Browse, filter, and RSVP to events from all organizations across university.</p>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search events, organizations, venue..."
                       value="<?= htmlspecialchars($search) ?>" id="searchInput">
                <button class="btn-search" onclick="doSearch()">Search</button>
            </div>
        </div>
    </section>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">

        <!-- TAB BAR -->
        <div style="padding-top: 40px;">
            <div class="tab-bar-events">
                <button class="tab-btn-ev active" onclick="switchEvTab('upcoming', this)">
                    UPCOMING <span class="tab-count-ev"><?= count($events_upcoming) ?></span>
                </button>
                <button class="tab-btn-ev" onclick="switchEvTab('finished', this)">
                    FINISHED <span class="tab-count-ev"><?= count($events_finished) ?></span>
                </button>
                <button class="tab-btn-ev" onclick="switchEvTab('all', this)">
                    ALL EVENTS <span class="tab-count-ev"><?= count($events_all) ?></span>
                </button>
            </div>
        </div>

        <?php
        $tabs = [
            'upcoming' => $events_upcoming,
            'finished' => $events_finished,
            'all'      => $events_all,
        ];
        foreach ($tabs as $tabName => $tabEvents):
        ?>
        <div class="tab-panel-ev <?= $tabName === 'upcoming' ? 'active' : '' ?>" id="evTab-<?= $tabName ?>">

            <!-- Card grid -->
            <div class="ev-grid" id="evGrid-<?= $tabName ?>">
                <?php if (empty($tabEvents)): ?>
                    <div class="empty-ev">No events in this category.</div>
                <?php else: ?>
                    <?php foreach($tabEvents as $index => $e):
                        $bg = $gradients[$index % count($gradients)]; ?>
                    <div class="ev-card-item e-card" data-tab="<?= $tabName ?>" data-index="<?= $index ?>">
                        <div class="e-card-header" style="background: <?= $bg ?>;">
                            <span class="cat-tag">Event</span>
                        </div>
                        <div class="e-card-body">
                            <span class="e-org"><?= strtoupper(htmlspecialchars($e['org_name'])) ?></span>
                            <h3 class="e-title"><?= htmlspecialchars($e['title']) ?></h3>
                            <div class="e-meta">
                                <span><i class='bx bx-calendar'></i> <?= date('M d', strtotime($e['start_datetime'])) ?></span>
                                <span><i class='bx bx-map-pin'></i> <?= htmlspecialchars($e['venue']) ?></span>
                            </div>
                            <div class="e-card-footer">
                                <span class="spots-label"><?= $e['spots_left'] ?> spots left</span>
                                <a href="view_event.php?id=<?= $e['event_id'] ?>" class="btn-view-details">View Details</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if (!empty($tabEvents)): ?>
            <div class="pagination-wrap" id="evPag-<?= $tabName ?>">
                <span class="pagination-info" id="evPagInfo-<?= $tabName ?>"></span>
                <div class="pagination-controls" id="evPagCtrl-<?= $tabName ?>"></div>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

    </div>

<script>
const ITEMS_PER_PAGE = 6;

const pagState = {
    upcoming: { page: 1 },
    finished: { page: 1 },
    all:      { page: 1 },
};

function getCards(tab) {
    return document.querySelectorAll(`.ev-card-item[data-tab="${tab}"]`);
}

function totalPages(tab) {
    return Math.max(1, Math.ceil(getCards(tab).length / ITEMS_PER_PAGE));
}

function renderPage(tab) {
    const cards = getCards(tab);
    const total = cards.length;
    if (total === 0) return;

    const tp    = totalPages(tab);
    const page  = pagState[tab].page;
    const start = (page - 1) * ITEMS_PER_PAGE;
    const end   = Math.min(start + ITEMS_PER_PAGE, total);

    cards.forEach((c, i) => c.classList.toggle('visible', i >= start && i < end));

    // Info
    const infoEl = document.getElementById(`evPagInfo-${tab}`);
    if (infoEl) infoEl.textContent = `Showing ${start + 1}–${end} of ${total} events`;

    // Controls
    const ctrl = document.getElementById(`evPagCtrl-${tab}`);
    if (!ctrl) return;
    ctrl.innerHTML = '';
    if (tp <= 1) return;

    ctrl.appendChild(makeBtn('‹', page === 1, () => goTo(tab, page - 1)));

    getPageNums(page, tp).forEach(p => {
        if (p === '…') {
            const s = document.createElement('span');
            s.className = 'page-ellipsis';
            s.textContent = '…';
            ctrl.appendChild(s);
        } else {
            const b = makeBtn(p, false, () => goTo(tab, p));
            if (p === page) b.classList.add('active');
            ctrl.appendChild(b);
        }
    });

    ctrl.appendChild(makeBtn('›', page === tp, () => goTo(tab, page + 1)));
}

function makeBtn(label, disabled, onClick) {
    const b = document.createElement('button');
    b.className = 'page-btn';
    b.textContent = label;
    b.disabled = disabled;
    if (!disabled) b.addEventListener('click', onClick);
    return b;
}

function goTo(tab, page) {
    pagState[tab].page = Math.max(1, Math.min(page, totalPages(tab)));
    renderPage(tab);
    // Scroll to top of content area smoothly
    document.querySelector('.tab-bar-events').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function getPageNums(current, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    const left  = Math.max(2, current - 2);
    const right = Math.min(total - 1, current + 2);
    if (left > 2)       pages.push('…');
    for (let i = left; i <= right; i++) pages.push(i);
    if (right < total - 1) pages.push('…');
    pages.push(total);
    return pages;
}

function switchEvTab(name, btn) {
    document.querySelectorAll('.tab-panel-ev').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn-ev').forEach(b => b.classList.remove('active'));
    document.getElementById('evTab-' + name).classList.add('active');
    btn.classList.add('active');
    renderPage(name);
}

function doSearch() {
    const q = document.getElementById('searchInput').value.trim();
    window.location.href = 'events.php' + (q ? '?search=' + encodeURIComponent(q) : '');
}

document.getElementById('searchInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') doSearch();
});

document.addEventListener('DOMContentLoaded', () => {
    renderPage('upcoming');
    renderPage('finished');
    renderPage('all');
});
</script>
</body>
</html>