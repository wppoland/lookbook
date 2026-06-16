<?php
/**
 * Uninstall cleanup for Lookbook.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the global options
 * Lookbook creates and deletes the lookbook posts (with their hotspot meta).
 *
 * @package Lookbook
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('lookbook_settings');
delete_option('lookbook_db_version');

// Remove lookbook posts and their meta.
$lookbook_posts = get_posts([
    'post_type'      => 'lookbook',
    'post_status'    => 'any',
    'numberposts'    => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
]);

foreach ($lookbook_posts as $lookbook_post_id) {
    wp_delete_post((int) $lookbook_post_id, true);
}
