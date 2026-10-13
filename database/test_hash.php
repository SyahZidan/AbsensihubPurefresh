<?php
// Temporary script to verify bcrypt hash against common candidate passwords.
$hash = '$2y$10$EdLivR9Q.ZJphyX2KTX0JuRqWenPxD853KtjD21bAZFd3p3wWkrCq';
$candidates = [
    '123456',
    '12345678',
    'admin',
    'admin123',
    'password',
    'purefresh',
    'Purefresh123',
    '081234567890',
    '081200000000',
    'karyawan',
    'karyawan123',
    'absen',
    'absen123'
];
echo "<h3>Bcrypt Hash Verification</h3>";
echo "Hash: <code>$hash</code><br><br>";
$found = false;
foreach ($candidates as $candidate) {
    if (password_verify($candidate, $hash)) {
        echo "✅ MATCH FOUND! The password is: <strong>" . htmlspecialchars($candidate) . "</strong><br>";
        $found = true;
        break;
    }
}
if (!$found) {
    echo "❌ No match found in common candidates list.<br>";
}
