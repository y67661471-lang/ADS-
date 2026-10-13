<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// SAVE SETTINGS

// Handle File Upload for Avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Multiple File Uploads (Avatar, Logo, Favicon)
    $uploadKeys = ['default_avatar_file' => 'default_avatar_url', 'app_logo_file' => 'app_logo', 'favicon_file' => 'favicon'];

    foreach ($uploadKeys as $fileKey => $dbKey) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            $fileName = $dbKey . '_' . time() . '_' . basename($_FILES[$fileKey]['name']);
            $targetPath = $uploadDir . $fileName;

            // Simple validation: check if image/icon
            $check = getimagesize($_FILES[$fileKey]['tmp_name']);
            // Allow .ico files which might fail getimagesize
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($check !== false || $ext === 'ico') {
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                    $_POST[$dbKey] = 'assets/uploads/' . $fileName;
                }
            }
        }
    }

    foreach ($_POST as $key => $val) {
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)");
        $stmt->execute([$key, $val]);
    }
}

// Fetch Settings
$settings = $pdo->query("SELECT key_name, key_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>


<!-- Tab Buttons -->
<div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
    <button type="button" class="tab-btn active" onclick="openTab('general')">General</button>
    <button type="button" class="tab-btn" onclick="openTab('payment')">Payment</button>
    <button type="button" class="tab-btn" onclick="openTab('google')">Google Auth</button>
    <button type="button" class="tab-btn" onclick="openTab('display')">Display</button>
    <button type="button" class="tab-btn" onclick="openTab('branding')">Branding</button>
</div>

<style>
    .tab-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        background: #e9ecef;
        color: #495057;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }

    .tab-btn.active {
        background: var(--primary);
        color: white;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="panel" style="max-width:100%;">
    <form method="POST" enctype="multipart/form-data">

        <!-- Tab: General -->
        <div id="tab-general" class="tab-content active">
            <h3 style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">General Settings</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>App Name</label>
                    <input type="text" name="app_name" class="input-field"
                        value="<?php echo htmlspecialchars($settings['app_name'] ?? 'RRR Esports'); ?>">
                </div>
                <div class="form-group">
                    <label>Site URL</label>
                    <input type="text" name="site_url" class="input-field"
                        value="<?php echo htmlspecialchars($settings['site_url'] ?? 'http://localhost/rrr esports'); ?>">
                </div>
                <div class="form-group">
                    <label>App Icon URL</label>
                    <input type="text" name="app_icon" class="input-field"
                        value="<?php echo htmlspecialchars($settings['app_icon'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Support Link (Telegram)</label>
                    <input type="text" name="telegram_link" class="input-field"
                        value="<?php echo htmlspecialchars($settings['telegram_link'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Minimum Deposit (BDT)</label>
                    <input type="number" name="min_deposit" class="input-field"
                        value="<?php echo htmlspecialchars($settings['min_deposit'] ?? '20'); ?>">
                </div>
            </div>
        </div>

        <!-- Tab: Payment -->
        <div id="tab-payment" class="tab-content">
            <h3 style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">Payment Configuration
            </h3>

            <div class="form-group" style="margin-bottom:25px;">
                <label style="display:block; margin-bottom:10px;">Active Payment Mode</label>
                <div style="display:flex; gap:10px;">
                    <label style="cursor:pointer; flex:1;">
                        <input type="radio" name="payment_mode" value="manual" <?php echo ($settings['payment_mode'] ?? 'manual') === 'manual' ? 'checked' : ''; ?> style="display:none;"
                            onchange="this.form.submit()">
                        <div
                            style="padding:15px; text-align:center; border:1px solid #ddd; border-radius:8px; background: <?php echo ($settings['payment_mode'] ?? 'manual') === 'manual' ? '#007bff' : '#f8f9fa'; ?>; color: <?php echo ($settings['payment_mode'] ?? 'manual') === 'manual' ? '#fff' : '#333'; ?>; font-weight:600;">
                            <i class="fas fa-hand-holding-usd"></i> Manual (Send Money)
                        </div>
                    </label>
                    <label style="cursor:pointer; flex:1;">
                        <input type="radio" name="payment_mode" value="auto" <?php echo ($settings['payment_mode'] ?? '') === 'auto' ? 'checked' : ''; ?> style="display:none;" onchange="this.form.submit()">
                        <div
                            style="padding:15px; text-align:center; border:1px solid #ddd; border-radius:8px; background: <?php echo ($settings['payment_mode'] ?? '') === 'auto' ? '#28a745' : '#f8f9fa'; ?>; color: <?php echo ($settings['payment_mode'] ?? '') === 'auto' ? '#fff' : '#333'; ?>; font-weight:600;">
                            <i class="fas fa-robot"></i> Auto Payment (Gateway)
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Bkash Number (Personal)</label>
                    <input type="number" name="bkash_number" class="input-field"
                        value="<?php echo htmlspecialchars($settings['bkash_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Nagad Number (Personal)</label>
                    <input type="number" name="nagad_number" class="input-field"
                        value="<?php echo htmlspecialchars($settings['nagad_number'] ?? ''); ?>">
                </div>
            </div>

            <hr style="margin:20px 0; border:0; border-top:1px dashed #ddd;">
            <p style="font-size:12px; color:#666; margin-bottom:10px;">Gateway Configuration (Only if Auto Payment is
                selected)</p>

            <div class="form-grid">
                <div class="form-group">
                    <label>API Key / Brand Key</label>
                    <input type="text" name="auto_pay_key" class="input-field"
                        value="<?php echo htmlspecialchars($settings['auto_pay_key'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Gateway URL</label>
                    <input type="text" name="auto_pay_url" class="input-field"
                        value="<?php echo htmlspecialchars($settings['auto_pay_url'] ?? 'https://pay.quickpaybd.xyz'); ?>">
                </div>
            </div>
        </div>

        <!-- Tab: Google Auth -->
        <div id="tab-google" class="tab-content">
            <h3 style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">Google Sign-In</h3>

            <div class="form-group" style="margin-bottom:25px;">
                <label style="display:block; margin-bottom:10px;">Status</label>
                <div style="display:flex; gap:10px;">
                    <label style="cursor:pointer; flex:1;" onclick="updateRadioVisuals(this, 'red')">
                        <input type="radio" name="google_auth_enable" value="0" <?php echo ($settings['google_auth_enable'] ?? '0') == '0' ? 'checked' : ''; ?> style="display:none;">
                        <div id="btn-disable"
                            style="padding:15px; text-align:center; border:1px solid #ddd; border-radius:8px; background: <?php echo ($settings['google_auth_enable'] ?? '0') == '0' ? '#dc3545' : '#f8f9fa'; ?>; color: <?php echo ($settings['google_auth_enable'] ?? '0') == '0' ? '#fff' : '#333'; ?>; font-weight:600;">
                            Disabled
                        </div>
                    </label>
                    <label style="cursor:pointer; flex:1;" onclick="updateRadioVisuals(this, 'green')">
                        <input type="radio" name="google_auth_enable" value="1" <?php echo ($settings['google_auth_enable'] ?? '0') == '1' ? 'checked' : ''; ?> style="display:none;">
                        <div id="btn-enable"
                            style="padding:15px; text-align:center; border:1px solid #ddd; border-radius:8px; background: <?php echo ($settings['google_auth_enable'] ?? '0') == '1' ? '#28a745' : '#f8f9fa'; ?>; color: <?php echo ($settings['google_auth_enable'] ?? '0') == '1' ? '#fff' : '#333'; ?>; font-weight:600;">
                            Enabled
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Client ID</label>
                    <input type="text" name="google_client_id" class="input-field"
                        value="<?php echo htmlspecialchars($settings['google_client_id'] ?? ''); ?>"
                        placeholder="ENTER_CLIENT_ID.apps.googleusercontent.com">
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="password" name="google_client_secret" class="input-field"
                        value="<?php echo htmlspecialchars($settings['google_client_secret'] ?? ''); ?>"
                        placeholder="Enter Client Secret">
                </div>
            </div>


            <div
                style="background:#e8f0fe; border:1px solid #c9daf8; padding:15px; border-radius:8px; margin-top:20px; color:#1a73e8;">
                <h4 style="margin:0 0 10px; display:flex; align-items:center;"><i class="fab fa-google"
                        style="margin-right:8px;"></i> Google Console Configuration</h4>
                <p style="font-size:13px; margin-bottom:15px; color:#444;">Copy these values to your <a
                        href="https://console.cloud.google.com/apis/credentials" target="_blank"
                        style="text-decoration:underline; color:#1a73e8;">Google Cloud Console</a> credentials.</p>

                <?php
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                // Calculate Root Directory (Admin is in /admin, so dirname returns path without /admin)
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                $baseDir = dirname($scriptDir); // Go up one level from /admin to root
                // Ensure no double slashes if root is just /
                $baseUrl = $protocol . $host . ($baseDir == '/' ? '' : $baseDir);
                ?>

                <div class="form-group" style="margin-bottom:10px;">
                    <label style="font-size:12px; font-weight:600; color:#555;">Authorized JavaScript Origins</label>
                    <div style="display:flex;">
                        <input type="text" class="input-field" value="<?php echo $protocol . $host; ?>" readonly
                            style="background:#fff; color:#333; font-family:monospace;">
                        <button type="button" class="btn-success"
                            style="padding:0 15px; margin-left:5px; border-radius:5px; border:none; cursor:pointer;"
                            onclick="navigator.clipboard.writeText('<?php echo $protocol . $host; ?>')"><i
                                class="fas fa-copy"></i></button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label style="font-size:12px; font-weight:600; color:#555;">Authorized Redirect URI (If
                        asked)</label>
                    <div style="display:flex;">
                        <input type="text" class="input-field"
                            value="<?php echo $baseDir == '/' ? $protocol . $host . '/login.php' : $baseUrl . '/login.php'; ?>"
                            readonly style="background:#fff; color:#333; font-family:monospace;">
                        <button type="button" class="btn-success"
                            style="padding:0 15px; margin-left:5px; border-radius:5px; border:none; cursor:pointer;"
                            onclick="navigator.clipboard.writeText('<?php echo $baseDir == '/' ? $protocol . $host . '/login.php' : $baseUrl . '/login.php'; ?>')"><i
                                class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <script>
                function updateRadioVisuals(label, color) {
                    document.getElementById('btn-disable').style.background = '#f8f9fa';
                    document.getElementById('btn-disable').style.color = '#333';
                    document.getElementById('btn-enable').style.background = '#f8f9fa';
                    document.getElementById('btn-enable').style.color = '#333';

                    const div = label.querySelector('div');
                    div.style.background = (color === 'red') ? '#dc3545' : '#28a745';
                    div.style.color = '#fff';
                }
            </script>
        </div>

        <!-- Tab: Display -->
        <div id="tab-display" class="tab-content">
            <h3 style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">Display Settings</h3>

            <div class="form-group">
                <label>Default Profile Avatar</label>
                <div class="form-grid" style="align-items:start;">
                    <div>
                        <input type="text" name="default_avatar_url" class="input-field" placeholder="Image URL"
                            value="<?php echo htmlspecialchars($settings['default_avatar_url'] ?? ''); ?>"
                            style="margin-bottom:10px;">
                        <input type="file" name="default_avatar_file" class="input-field" style="padding:8px;">
                    </div>
                    <?php if (!empty($settings['default_avatar_url'])): ?>
                        <div style="text-align:center;">
                            <img src="<?php echo strpos($settings['default_avatar_url'], 'http') === 0 ? $settings['default_avatar_url'] : '../' . $settings['default_avatar_url']; ?>"
                                style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #ddd; box-shadow:0 5px 15px rgba(0,0,0,0.1);">
                            <p style="font-size:12px; margin-top:5px; color:#666;">Current Preview</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Branding -->
        <div id="tab-branding" class="tab-content">
            <h3 style="margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">Branding Settings</h3>

            <div class="form-group">
                <label>App Logo (Header)</label>
                <div class="form-grid" style="align-items:start;">
                    <div>
                        <input type="text" name="app_logo" class="input-field" placeholder="Image URL"
                            value="<?php echo htmlspecialchars($settings['app_logo'] ?? ''); ?>"
                            style="margin-bottom:10px;">
                        <input type="file" name="app_logo_file" class="input-field" style="padding:8px;">
                    </div>
                    <?php if (!empty($settings['app_logo'])): ?>
                        <div style="text-align:center;">
                            <img src="<?php echo strpos($settings['app_logo'], 'http') === 0 ? $settings['app_logo'] : '../' . $settings['app_logo']; ?>"
                                style="max-height:80px; object-fit:contain; border:1px solid #ddd; padding:5px;">
                            <p style="font-size:12px; margin-top:5px; color:#666;">Logo Preview</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Favicon (Browser Tab Icon)</label>
                <div class="form-grid" style="align-items:start;">
                    <div>
                        <input type="text" name="favicon" class="input-field" placeholder="Image URL"
                            value="<?php echo htmlspecialchars($settings['favicon'] ?? ''); ?>"
                            style="margin-bottom:10px;">
                        <input type="file" name="favicon_file" class="input-field" style="padding:8px;">
                    </div>
                    <?php if (!empty($settings['favicon'])): ?>
                        <div style="text-align:center;">
                            <img src="<?php echo strpos($settings['favicon'], 'http') === 0 ? $settings['favicon'] : '../' . $settings['favicon']; ?>"
                                style="width:32px; height:32px; object-fit:contain; border:1px solid #ddd; padding:2px;">
                            <p style="font-size:12px; margin-top:5px; color:#666;">Favicon</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-top:30px; text-align:right;">
            <button type="submit" class="action-btn btn-blue" style="padding:12px 30px; font-size:14px;"><i
                    class="fas fa-save"></i> Save All Settings</button>
        </div>

    </form>
</div>

<script>
    function openTab(tabName) {
        // Hide all, remove active class
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

        // Show target
        document.getElementById('tab-' + tabName).classList.add('active');

        // Highlight button (find by text content or index - simplifying by searching standard logic)
        // Actually, event.target is simpler if passed, but I passed content. Let's look up by onclick attribute for simplicity in this context
        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(btn => {
            if (btn.getAttribute('onclick').includes(tabName)) {
                btn.classList.add('active');
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>