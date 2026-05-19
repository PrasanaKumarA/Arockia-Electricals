<?php
// Serve manifest as proper JSON to bypass InfinityFree/Cloudflare HTML injection
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=604800');
require_once __DIR__ . '/includes/config.php';

$base = APP_URL;
$manifest = [
    'name'             => APP_NAME,
    'short_name'       => 'Arockia',
    'description'      => 'Inventory Management & Billing System for Arockia Electricals',
    'start_url'        => $base . '/index.php',
    'scope'            => $base . '/',
    'display'          => 'standalone',
    'background_color' => '#030712',
    'theme_color'      => '#4f46e5',
    'orientation'      => 'portrait-primary',
    'categories'       => ['business', 'productivity'],
    'icons'            => [
        [
            'src'     => $base . '/assets/icons/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src'     => $base . '/assets/icons/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'maskable',
        ],
        [
            'src'     => $base . '/assets/icons/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src'     => $base . '/assets/icons/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'maskable',
        ],
    ],
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
