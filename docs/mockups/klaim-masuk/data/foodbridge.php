<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profil Pengguna & Instansi
    |--------------------------------------------------------------------------
    */

    'user' => [
        'name' => 'Ladesh Jagat',
        'email' => 'ladeshjagat1@gmail.com',
    ],

    'organization' => [
        'name' => 'SDN 1 Dayeuhkolot',
        'email' => 'sdn1dayeuhkolot@school.id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Sidebar
    |--------------------------------------------------------------------------
    */

    'navigation' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'active' => false],
        ['label' => 'Posting Makanan', 'icon' => 'utensils', 'active' => false],
        ['label' => 'Klaim Masuk', 'icon' => 'inbox', 'active' => true, 'badge' => 5],
        ['label' => 'Request Board', 'icon' => 'clipboard', 'active' => false],
        ['label' => 'Ulasan dan Laporan', 'icon' => 'star', 'active' => false],
    ],

];
