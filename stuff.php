<?php
/**
 * config.php
 * -------------------------------------------------------------
 * All the text/content for the site lives here as plain PHP
 * arrays. index.php loops over these arrays to build the page.
 * Edit the values below to change site content — no HTML editing
 * needed for things like nav links, products, or principles.
 * -------------------------------------------------------------
 */

// Basic site info
$site_name  = 'CABALLERO INDUSTRIES';
$site_year  = date('Y'); // updates automatically every year

// Top status bar (the thin technical bar above the main nav)
$status_left  = '[ LOC. 14.5995&deg; N / 120.9842&deg; E ] // SECURE_LINE_ONLINE';
$status_right = 'VER. 25.4.19 // TACTICAL_CORE';

// Main navigation.
// type "anchor" = a section on the homepage (e.g. #gallery)
// type "page"   = a separate .php file (has its own "href")
// includes/helpers.php (nav_href) turns these into the right URL
// depending on which page is currently open.
$nav_items = array(
    array('label' => 'HOME',       'type' => 'anchor', 'target' => 'home'),
    array('label' => 'ABOUT ME',   'type' => 'anchor', 'target' => 'about'),
    array('label' => 'GALLERY',    'type' => 'anchor', 'target' => 'gallery'),
    array('label' => 'CONTACT US', 'type' => 'page',   'href' => 'contact.php'),
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

// NOTE: products used to be a static array here. They now live in the
// "products" database table instead (see database/schema.sql), so the
// homepage and product pages query the database directly. This keeps
// products editable through the admin pages without touching code.

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
        array('Home', 'index.php#home'),
        array('About', 'index.php#about'),
        array('Gallery', 'index.php#gallery'),
        array('Contact', 'contact.php'),
    ),
    'COLLECTION' => array(
        array('Jackets', 'index.php#gallery'),
        array('Caps', 'index.php#gallery'),
        array('Bags', 'index.php#gallery'),
        array('Hoodies', 'index.php#gallery'),
    ),
    'SUPPORT' => array(
        array('FAQ', '#'),
        array('Shipping', '#'),
        array('Returns', '#'),
        array('Size Guide', '#'),
    ),
);
