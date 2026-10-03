<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$stmt = $pdo->query(
    "SELECT f.*, (SELECT COUNT(*) FROM form_fields ff WHERE ff.form_id = f.id) AS field_count
     FROM forms f
     WHERE f.status = 'ACTIVE' AND f.public_visibility = 'PUBLIC'
     ORDER BY f.created_at DESC"
);
$openForms = $stmt->fetchAll();

function opportunity_tag($title) {
    $t = strtolower($title);
    if (strpos($t, 'workshop') !== false) return ['🛠️', 'Workshop', '#DBEAFE', '#1D4ED8'];
    if (strpos($t, 'webinar') !== false) return ['🎥', 'Webinar', '#FEF3C7', '#92400E'];
    if (strpos($t, 'hackathon') !== false) return ['⚡', 'Hackathon', '#FCE7F3', '#9D174D'];
    if (strpos($t, 'internship') !== false) return ['🎯', 'Internship', '#F2E1FE', '#7C1FB8'];
    return ['📘', 'Program', '#E5E7EB', '#374151'];
}

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'CANDIDATE') {
    $backLink = '../candidate/dashboard.php';
    $backLabel = '← Back to Dashboard';
} elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'ADMIN') {
    $backLink = '../admin/dashboard.php';
    $backLabel = '← Back to Dashboard';
} else {
    $backLink = 'index.php';
    $backLabel = '← Back to Home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Open Opportunities - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .forms-hero {
            background: var(--nav-bg);
            padding: 56px 20px 70px;
            text-align: center;
            color: #fff;
        }
        .forms-hero h1 { font-size: 30px; font-weight: 800; margin: 0 0 10px; letter-spacing: -0.01em; }
        .forms-hero h1 span { color: var(--accent-soft); }
        .forms-hero p { color: #B8BCC0; font-size: 14.5px; margin: 0 auto 22px; max-width: 480px; }
        .trust-row {
            display: flex;
            justify-content: center;
            gap: 22px;
            flex-wrap: wrap;
            font-size: 12.5px;
            color: #C9CDD0;
        }
        .trust-row span { display: flex; align-items: center; gap: 6px; font-weight: 600; }
        .forms-wrap { max-width: 720px; margin: -40px auto 50px; padding: 0 20px; }
        .count-strip {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(28,29,31,0.08);
            text-align: center;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text);
        }
        .count-strip span { color: var(--accent); }
        .opp-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 22px 24px;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px rgba(28,29,31,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }
        .opp-card:hover {
            box-shadow: 0 8px 24px rgba(28,29,31,0.1);
            transform: translateY(-1px);
        }
        .opp-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 999px;
            margin-bottom: 8px;
        }
        .opp-info h2 { font-size: 16.5px; margin: 0 0 4px; }
        .opp-info p { font-size: 13px; color: var(--text-muted); margin: 0 0 6px; }
        .opp-meta { font-size: 12px; color: var(--text-muted); }
        .opp-card a.btn { white-space: nowrap; text-decoration: none; }
        .back-home { text-align: center; margin-top: 28px; }
        .back-home a {
            display: inline-block;
            color: var(--text);
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 10px 22px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(28,29,31,0.05);
            transition: background 0.15s ease;
        }
        .back-home a:hover { background: var(--bg); }
    </style>
</head>
<body>
    <div class="forms-hero">
        <h1>Talent<span>Track</span> Opportunities</h1>
        <p>Genuine internships, workshops and programs — verified by our admin team, updated in real time.</p>
        <div class="trust-row">
            <span>🔒 Secure Applications</span>
            <span>✅ Verified Opportunities</span>
            <span>⚡ Apply in Minutes</span>
        </div>
    </div>

    <div class="forms-wrap">
        <?php if (!empty($openForms)): ?>
            <div class="count-strip"><span><?= count($openForms) ?></span> opportunit<?= count($openForms) === 1 ? 'y' : 'ies' ?> open right now</div>
        <?php endif; ?>

        <?php if (empty($openForms)): ?>
            <div class="card empty-state">No opportunities are open right now. Please check back later.</div>
        <?php else: ?>
            <?php foreach ($openForms as $f): [$emoji, $label, $bg, $fg] = opportunity_tag($f['title']); ?>
                <div class="opp-card">
                    <div class="opp-info">
                        <div class="opp-tag" style="background:<?= $bg ?>;color:<?= $fg ?>;"><?= $emoji ?> <?= $label ?></div>
                        <h2><?= sanitize($f['title']) ?></h2>
                        <?php if ($f['description']): ?><p><?= sanitize($f['description']) ?></p><?php endif; ?>
                        <div class="opp-meta"><?= (int)$f['field_count'] ?> fields to fill</div>
                    </div>
                    <a href="form.php?slug=<?= urlencode($f['slug']) ?>" class="btn">Apply Now</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="back-home"><a href="<?= $backLink ?>"><?= $backLabel ?></a></div>
    </div>
</body>
</html>
