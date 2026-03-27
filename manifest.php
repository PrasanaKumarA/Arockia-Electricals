<?php
// Serve manifest as proper JSON to bypass InfinityFree/Cloudflare HTML injection
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=604800');
require_once __DIR__ . '/includes/config.php';

$manifest = [
    'name'             => APP_NAME,
    'short_name'       => 'Arockia',
    'description'      => 'Inventory Management & Billing System for Arockia Electricals',
    'start_url'        => '/',
    'display'          => 'standalone',
    'background_color' => '#1e3a5f',
    'theme_color'      => '#1e3a5f',
    'orientation'      => 'portrait-primary',
    'categories'       => ['business', 'productivity'],
    'icons'            => [
        [
            'src'     => APP_URL . '/assets/icons/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src'     => APP_URL . '/assets/icons/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
