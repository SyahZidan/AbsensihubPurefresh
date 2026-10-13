<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/holidays.php';

verify_csrf();
$admin = require_login('admin');

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    db()->prepare('DELETE FROM holidays WHERE id = ?')->execute([$id]);
    log_admin_action((int) $admin['id'], 'holiday_delete', 'holidays', $id, 'Hapus hari libur');
}

flash('success', 'Hari libur dihapus.');
redirect(route_url('admin.holidays'));
