<?php

// Basic site info
$site_name  = 'CABALLERO INDUSTRIES';
$site_year  = date('Y'); // updates automatically every year

// Top status bar (the thin technical bar above the main nav)
$status_left  = '[ LOC. 14.5995&deg; N / 120.9842&deg; E ] // SECURE_LINE_ONLINE';
$status_right = 'VER. 25.4.19 // TACTICAL_CORE';

// Main navigation
$nav_items = array(
    array('label' => 'HOME',       'target' => 'home'),
    array('label' => 'ABOUT ME',   'target' => 'about'),
    array('label' => 'GALLERY',    'target' => 'gallery'),
    array('label' => 'CONTACT US', 'target' => 'contact'),
);

// Strategic principles cards
$principles = array(
    array(
        'title' => 'INNOVATION',
        'text'  => 'Integrating avant-garde tech materials and modular designs into daily techwear.',
    ),
    array(
        'title' => 'QUALITY',
        'text'  => 'Strict wear-testing and military-grade thread components ensure endless durability.',
    ),
    array(
        'title' => 'PURPOSE',
        'text'  => 'Every pocket, seam, strap, and enclosure is engineered with precise utility.',
    ),
    array(
        'title' => 'DISCIPLINE',
        'text'  => 'Unwavering commitment to clean structural aesthetics and functional precision.',
    ),
);

// Featured collection products
$products = array(
    array(
        'code'  => 'CI 001',
        'name'  => 'TACTICAL JACKET',
        'price' => '&#8369;2,450',
        'image' => 'images/jacket1.png',
        'alt'   => 'Caballero Industries tactical jacket',
    ),
    array(
        'code'  => 'CI 002',
        'name'  => 'TACTICAL CAP',
        'price' => '&#8369;950',
        'image' => 'images/hat.png',
        'alt'   => 'Caballero Industries tactical cap',
    ),
    array(
        'code'  => 'CI 003',
        'name'  => 'TACTICAL BAG',
        'price' => '&#8369;1,650',
        'image' => 'images/bag.png',
        'alt'   => 'Caballero Industries tactical bag',
    ),
    array(
        'code'  => 'CI 004',
        'name'  => 'TACTICAL HOODIE',
        'price' => '&#8369;2,150',
        'image' => 'images/jacket.png',
        'alt'   => 'Caballero Industries tactical hoodie',
    ),
);

// Validated integrations / partner blocks (fictional brand-network
// callouts, not real company endorsements)
$partners = array(
    array('name' => 'APEX SYSTEMS',   'tag' => 'TACTICAL_NET'),
    array('name' => 'IRONFORGE CO.',  'tag' => 'HEAVY_FABRIC'),
    array('name' => 'VERTEX TECH',    'tag' => 'LAB_SPEC_V1'),
    array('name' => 'BLACKLINE MFG.', 'tag' => 'HARDWARE_SYS'),
);

// Footer link columns: 'Column Title' => list of [label, url]
$footer_columns = array(
    'COMPANY' => array(
        array('Home', '#home'),
        array('About', '#about'),
        array('Gallery', '#gallery'),
        array('Contact', '#contact'),
    ),
    'COLLECTION' => array(
        array('Jackets', '#gallery'),
        array('Caps', '#gallery'),
        array('Bags', '#gallery'),
        array('Hoodies', '#gallery'),
    ),
    'SUPPORT' => array(
        array('FAQ', '#'),
        array('Shipping', '#'),
        array('Returns', '#'),
        array('Size Guide', '#'),
    ),
);
