<?php

declare(strict_types=1);

namespace Lookbook\Admin;

use Lookbook\Contract\HasHooks;
use Lookbook\PostType;
use Lookbook\Repository;

defined('ABSPATH') || exit;

/**
 * The hotspot editor meta box shown on the Edit Lookbook screen.
 *
 * The editor is deliberately simple: a repeater where each row is an x%, a
 * y% and a product id. The featured image is the canvas; positions are entered
 * as percentages from the top-left.
 *
 * All output is escaped; the save handler verifies a nonce and the edit
 * capability, then defers to {@see Repository::sanitizeHotspots()} for shape and
 * bounds. Nothing is written on autosave.
 */
final class MetaBox implements HasHooks
{
    private const NONCE_ACTION = 'lookbook_save_hotspots';
    private const NONCE_NAME   = 'lookbook_hotspots_nonce';

    public function __construct(private readonly Repository $repository)
    {
    }

    public function registerHooks(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post_' . PostType::POST_TYPE, [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMetaBox(): void
    {
        add_meta_box(
            'lookbook-hotspots',
            __('Shoppable hotspots', 'plogins-lookbook'),
            [$this, 'render'],
            PostType::POST_TYPE,
            'normal',
            'high',
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        $screen = get_current_screen();

        if (! $screen instanceof \WP_Screen || $screen->post_type !== PostType::POST_TYPE) {
            return;
        }

        wp_enqueue_style(
            'lookbook-admin',
            LOOKBOOK_URL . 'assets/css/admin.css',
            [],
            \Lookbook\VERSION,
        );

        wp_enqueue_script(
            'lookbook-editor',
            LOOKBOOK_URL . 'assets/js/editor.js',
            [],
            \Lookbook\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );

        wp_localize_script('lookbook-editor', 'lookbookEditor', [
            'i18n' => [
                'confirmRemove' => __('Remove this hotspot?', 'plogins-lookbook'),
            ],
        ]);
    }

    public function render(\WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $context = [
            'hotspots' => $this->repository->hotspots($post->ID),
        ];

        $file = LOOKBOOK_DIR . 'templates/admin/hotspot-editor.php';

        if (! is_readable($file)) {
            return;
        }

        extract($context, EXTR_SKIP);
        require $file;
    }

    /**
     * Persist the hotspots when the lookbook is saved.
     */
    public function save(int $postId, \WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== PostType::POST_TYPE) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified on the next line.
        $nonce = isset($_POST[self::NONCE_NAME]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])) : '';

        if ($nonce === '' || ! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
        $raw = isset($_POST['lookbook_hotspots']) && is_array($_POST['lookbook_hotspots'])
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Sanitised field-by-field in the repository.
            ? wp_unslash($_POST['lookbook_hotspots'])
            : [];

        $this->repository->saveHotspots($postId, $this->repository->sanitizeHotspots($raw));
    }
}
