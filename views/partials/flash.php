<?php
$flashSuccess = flash('success');
$flashError = flash('error');
$flashWarning = flash('warning');
$flashInfo = flash('info');

$flashPayload = array_filter([
    'success' => $flashSuccess,
    'error' => $flashError,
    'warning' => $flashWarning,
    'info' => $flashInfo,
], static fn ($v) => $v !== null && $v !== '');

if ($flashPayload !== []): ?>
<script type="application/json" id="app-flash-data"><?= json_encode($flashPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
