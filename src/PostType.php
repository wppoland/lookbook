<?php

declare(strict_types=1);

namespace Lookbook;

use Lookbook\Contract\HasHooks;

defined('ABSPATH') || exit;

/**
 * Registers the `lookbook` custom post type that stores each shoppable image.
 *
 * A lookbook is admin-only content: it is not publicly queryable on its own
 * (no front-end archive or single template). Merchants create one, set its
 * featured image and pin product hotspots, then embed it anywhere with the
 * [lookbook id="N"] shortcode.
 *
 * The post title is the admin-facing name; the featured image is the canvas;
 * the hotspots live in the `_lookbook_hotspots` meta (see {@see Admin\MetaBox}).
 */
final class PostType implements HasHooks
{
    public const POST_TYPE = 'lookbook';

    public function registerHooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        $labels = [
            'name'                  => _x('Lookbooks', 'post type general name', 'plogins-lookbook'),
            'singular_name'         => _x('Lookbook', 'post type singular name', 'plogins-lookbook'),
            'menu_name'             => _x('Lookbooks', 'admin menu', 'plogins-lookbook'),
            'add_new'               => __('Add New', 'plogins-lookbook'),
            'add_new_item'          => __('Add New Lookbook', 'plogins-lookbook'),
            'edit_item'             => __('Edit Lookbook', 'plogins-lookbook'),
            'new_item'              => __('New Lookbook', 'plogins-lookbook'),
            'view_item'             => __('View Lookbook', 'plogins-lookbook'),
            'search_items'          => __('Search Lookbooks', 'plogins-lookbook'),
            'not_found'             => __('No lookbooks found.', 'plogins-lookbook'),
            'not_found_in_trash'    => __('No lookbooks found in Trash.', 'plogins-lookbook'),
            'all_items'             => __('All Lookbooks', 'plogins-lookbook'),
            'featured_image'        => __('Lookbook image', 'plogins-lookbook'),
            'set_featured_image'    => __('Set lookbook image', 'plogins-lookbook'),
            'remove_featured_image' => __('Remove lookbook image', 'plogins-lookbook'),
            'use_featured_image'    => __('Use as lookbook image', 'plogins-lookbook'),
        ];

        register_post_type(self::POST_TYPE, [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => false,
            'menu_position'      => 58,
            'menu_icon'          => 'dashicons-format-image',
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'hierarchical'       => false,
            'has_archive'        => false,
            'rewrite'            => false,
            'query_var'          => false,
            'supports'           => ['title', 'thumbnail'],
        ]);
    }
}
