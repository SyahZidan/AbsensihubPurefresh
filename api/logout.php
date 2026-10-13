<?php

require_once __DIR__ . '/../includes/auth.php';

logout_user();
session_start();
flash('success', 'Berhasil keluar.');
redirect(route_url('login'));
