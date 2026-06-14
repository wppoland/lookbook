<?php
/**
 * Default settings, merged under the option key `lookbook_settings`.
 *
 * These are global presentation defaults applied to every lookbook on the
 * storefront. The per-lookbook image and hotspots are stored on the lookbook
 * post itself (see {@see \Lookbook\Repository}); merchants tune these shared
 * defaults from WooCommerce → Lookbook.
 *
 * @package Lookbook
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    // Master switch. When off, lookbooks render nothing and no assets load.
    'enabled' => true,

    // Product card contents.
    'show_price'       => true,
    'show_add_to_cart' => true,

    // Override for the add-to-cart link label. Empty = WooCommerce default.
    'add_to_cart_text' => '',
];
