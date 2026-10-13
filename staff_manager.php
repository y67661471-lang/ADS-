<?php
require_once '../config/db.php';
require_once 'includes/header.php';

// Security Check
if (!hasPermission('staff')) {
    echo "<div class='panel'><h3>Access Denied</h3><p>You do not have permission to view this page.</p></div>";
    require_once 'includes/footer.php';
    exit;
}

$msg = '';

// --- POST HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. ADD / EDIT ROLE
    if (isset($_POST['action_role'])) {
        $name = trim($_POST['role_name']);
        $perms = $_POST['perms'] ?? [];
        $json_perms = json_encode($perms);

        if ($_POST['role_id']) {
            // Update
            $stmt = $pdo->prepare("UPDATE staff_roles SET title=?, permissions=? WHERE id=?");
            $stmt->execute([$name, $json_perms, $_POST['role_id']]);
            $msg = "Role updated successfully!";
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO staff_roles (title, permissions) VALUES (?, ?)");
            $stmt->execute([$name, $json_perms]);
            $msg = "Role created successfully!";
        }
    }

    // 2. DELETE ROLE
    if (isset($_POST['delete_role_id'])) {
        // Prevent deleting Super Admin
        $r = $pdo->prepare("SELECT title FROM staff_roles WHERE id=?")->execute([$_POST['delete_role_id']]);
        if ($_POST['delete_role_id'] == 1) {
            $msg = "Cannot delete Super Admin role.";
        } else {
            $pdo->prepare("DELETE FROM staff_roles WHERE id=?")->execute([$_POST['delete_role_id']]);
            $msg = "Role deleted.";
        }
    }

    // 3. ADD / EDIT STAFF
    if (isset($_POST['action_staff'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role_id = $_POST['role_id'];
        $password = trim($_POST['password']);

        if ($_POST['staff_id']) {
            // Update
            $sql = "UPDATE admins SET name=?, email=?, role_id=?";
            $params = [$name, $email, $role_id];

            if (!empty($password)) {
                $sql .= ", password=?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id=?";
            $params[] = $_POST['staff_id'];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Staff updated successfully!";
        } else {
            // Create
            if (empty($password)) {
                $msg = "Password is required for new staff.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, $role_id]);
                $msg = "New Staff added successfully!";
            }
        }
    }

    // 4. DELETE STAFF
    if (isset($_POST['delete_staff_id'])) {
        if ($_POST['delete_staff_id'] == $_SESSION['admin_id']) {
            $msg = "You cannot delete yourself!";
        } else {
            $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$_POST['delete_staff_id']]);
            $msg = "Staff member deleted.";
        }
    }
}

// --- FETCH DATA ---
$roles = $pdo->query("SELECT * FROM staff_roles ORDER BY id ASC")->fetchAll();
$staffs = $pdo->query("SELECT a.*, r.title as role_name FROM admins a JOIN staff_roles r ON a.role_id = r.id ORDER BY a.id ASC")->fetchAll();

?>

<style>
    .tab-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .tab-btn {
        padding: 10px 20px;
        border: none;
        background: #eee;
        cursor: pointer;
        font-weight: 600;
        border-radius: 5px;
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

    .perm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .perm-item {
        background: #f9f9f9;
        padding: 8px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
</style>

<div class="header-bar">
    <h2 class="page-title">Staff Manager</h2>
</div>

<?php if ($msg): ?>
    <div style="background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:20px;"><?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="tab-nav">
    <button class="tab-btn active" onclick="openTab('staff')">Staff Members</button>
    <button class="tab-btn" onclick="openTab('roles')">Roles & Permissions</button>
</div>

<!-- STAFF TAB -->
<div id="tab-staff" class="tab-content active">

    <!-- ADD STAFF FORM -->
    <div class="panel" style="max-width:800px; margin-bottom:20px;">
        <h3>Add / Edit Staff</h3>
        <form method="POST">
            <input type="hidden" name="action_staff" value="1">
            <input type="hidden" name="staff_id" id="edit-staff-id">

            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="edit-staff-name" class="input-field" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit-staff-email" class="input-field" required>
                </div>
                <div class="form-group">
                    <label>Password <small>(Leave blank if not changing)</small></label>
                    <input type="text" name="password" class="input-field" placeholder="New Password">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" id="edit-staff-role" class="input-field">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between;">
                <button type="submit" class="action-btn btn-blue">Save Staff</button>
                <div type="button" onclick="resetStaffForm()"
                    style="cursor:pointer; color:#666; font-size:12px; margin-top:10px;">Reset Form</div>
            </div>
        </form>
    </div>

    <!-- STAFF LIST -->
    <div class="panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffs as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($s['role_name']); ?></span>
                            </td>
                            <td>
                                <button class="action-btn btn-dark" style="padding:5px 10px;"
                                    onclick="editStaff(<?php echo htmlspecialchars(json_encode($s)); ?>)">Edit</button>
                                <?php if ($s['id'] != $_SESSION['admin_id']): ?>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('Delete this staff?');">
                                        <input type="hidden" name="delete_staff_id" value="<?php echo $s['id']; ?>">
                                        <button class="action-btn btn-red" style="padding:5px 10px;">Del</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ROLES TAB -->
<div id="tab-roles" class="tab-content">

    <!-- ADD ROLE FORM -->
    <div class="panel" style="max-width:800px; margin-bottom:20px;">
        <h3>Add / Edit Role</h3>
        <form method="POST">
            <input type="hidden" name="action_role" value="1">
            <input type="hidden" name="role_id" id="edit-role-id">

            <div class="form-group">
                <label>Role Name</label>
                <input type="text" name="role_name" id="edit-role-name" class="input-field"
                    placeholder="e.g. Accountant" required>
            </div>

            <label>Permissions (Features this role can access)</label>
            <div class="perm-grid">
                <label class="perm-item"><input type="checkbox" name="perms[]" value="all"> <strong>Super Admin
                        (All)</strong></label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="users"> User Management</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="deposits"> Deposits
                    Manager</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="withdraws"> Withdrawals
                    Manager</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="tournaments"> Tournaments</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="content"> App Content
                    (Banners/Cats)</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="notice"> Noticeboard</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="settings"> Settings</label>
                <label class="perm-item"><input type="checkbox" name="perms[]" value="staff"> Staff Manager</label>
            </div>

            <div style="margin-top:20px; display:flex; justify-content:space-between;">
                <button type="submit" class="action-btn btn-blue">Save Role</button>
                <div type="button" onclick="resetRoleForm()"
                    style="cursor:pointer; color:#666; font-size:12px; margin-top:10px;">Reset Form</div>
            </div>
        </form>
    </div>

    <!-- ROLES LIST -->
    <div class="panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role Name</th>
                        <th>Permissions Count</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $r):
                        $p_list = json_decode($r['permissions'], true);
                        $count = in_array('all', $p_list) ? 'ALL' : count($p_list);
                        ?>
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['title']); ?></strong></td>
                            <td><?php echo $count; ?></td>
                            <td>
                                <button class="action-btn btn-dark" style="padding:5px 10px;"
                                    onclick='editRole(<?php echo json_encode($r); ?>)'>Edit</button>
                                <?php if ($r['id'] != 1): // Cannot delete Super Admin ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this role?');">
                                        <input type="hidden" name="delete_role_id" value="<?php echo $r['id']; ?>">
                                        <button class="action-btn btn-red" style="padding:5px 10px;">Del</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function openTab(name) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        event.target.classList.add('active');
    }

    function editStaff(staff) {
        openTab('staff');
        document.getElementById('edit-staff-id').value = staff.id;
        document.getElementById('edit-staff-name').value = staff.name;
        document.getElementById('edit-staff-email').value = staff.email;
        document.getElementById('edit-staff-role').value = staff.role_id;
        window.scrollTo(0, 0);
    }

    function resetStaffForm() {
        document.getElementById('edit-staff-id').value = '';
        document.getElementById('edit-staff-name').value = '';
        document.getElementById('edit-staff-email').value = '';
        document.getElementById('edit-staff-role').value = '';
    }

    function editRole(role) {
        openTab('roles');
        document.getElementById('edit-role-id').value = role.id;
        document.getElementById('edit-role-name').value = role.title;

        // Reset checkboxes
        document.querySelectorAll('input[name="perms[]"]').forEach(cb => cb.checked = false);

        // Check permissions
        const perms = JSON.parse(role.permissions);
        perms.forEach(p => {
            const cb = document.querySelector(`input[value="${p}"]`);
            if (cb) cb.checked = true;
        });
        window.scrollTo(0, 0);
    }

    function resetRoleForm() {
        document.getElementById('edit-role-id').value = '';
        document.getElementById('edit-role-name').value = '';
        document.querySelectorAll('input[name="perms[]"]').forEach(cb => cb.checked = false);
    }
</script>

<?php require_once 'includes/footer.php'; ?>