<?php
$pageTitle = "Support - RRR Esports";
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<section class="app-section active">
    <div class="card-panel">
        <h3>Contact Support</h3>
        <div onclick="window.open('https://t.me/example', '_blank')"
            style="background:#f4faff; border:1px solid #ddecff; padding:15px; border-radius:10px; margin-bottom:10px; display:flex; align-items:center; gap:15px; cursor:pointer;">
            <i class="fab fa-telegram" style="color:#0088cc; font-size:32px;"></i>
            <div>
                <strong style="color:#0088cc;">Telegram Support</strong>
                <p style="font-size:12px; color:#666;">Get instant help 24/7 regarding payment or match issues.</p>
            </div>
            <i class="fas fa-chevron-right" style="margin-left:auto; color:#ccc;"></i>
        </div>

        <div style="margin-top:20px; font-size:13px; color:#777;">
            For any other queries, email us at: support@rrresports.com
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
