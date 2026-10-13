<?php

return [
    'name'       => 'Absensi Hub',
    'timezone'   => 'Asia/Jakarta',
    'logbook_mode' => true,
    'project_stage' => 'Tahap Inti',
    'framework_summary' => 'PHP Native 8, MySQL, Tailwind CSS CDN, Alpine.js',
    'partner_name' => 'Pure Fresh',
    'github_repo_url' => 'https://github.com/SyahZidan/AbsensihubPurefresh.git',
    // Biarkan kosong '' → URL terdeteksi otomatis dari DOCUMENT_ROOT (bekerja di XAMPP & InfinityFree).
    // Jika gagal di hosting, isi secara manual, contoh: 'https://namadomain.infinityfreeapp.com'
    'base_url'   => '',
    'upload_dir' => __DIR__ . '/../public/uploads',
];
