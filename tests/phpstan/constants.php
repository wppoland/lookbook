<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Lookbook
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('LOOKBOOK_DIR')) {
        define('LOOKBOOK_DIR', '/tmp/lookbook/');
    }
    if (! defined('LOOKBOOK_URL')) {
        define('LOOKBOOK_URL', 'https://example.test/wp-content/plugins/lookbook/');
    }
}

namespace Lookbook {
    if (! defined('Lookbook\\VERSION')) {
        define('Lookbook\\VERSION', '0.1.0');
    }
    if (! defined('Lookbook\\PLUGIN_FILE')) {
        define('Lookbook\\PLUGIN_FILE', '/tmp/lookbook/lookbook.php');
    }
}
