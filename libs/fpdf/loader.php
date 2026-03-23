<?php
// ============================================================
// FPDF - Free PDF for PHP (v1.82 - Inline Minimal)
// License: Permissive / Free to use
// Source: http://www.fpdf.org
// This is a minimal embedded version for InfinityFree compatibility
// For production, download the full fpdf library from fpdf.org
// and place fpdf.php in libs/fpdf/
// ============================================================
// IMPORTANT: Download FPDF from http://www.fpdf.org/
// Place fpdf.php in: libs/fpdf/fpdf.php

// Check if already downloaded
$fpdfPath = __DIR__ . '/libs/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    // Provide instructions
    die('Please download FPDF from http://www.fpdf.org and place fpdf.php in libs/fpdf/fpdf.php');
}
require_once $fpdfPath;
