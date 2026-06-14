<?php

declare(strict_types=1);

namespace Lookbook\Service;

use Lookbook\Contract\HasHooks;

defined('ABSPATH') || exit;

/**
 * Registers the "Lookbook" dynamic block (wppoland/lookbook).
 *
 * The block is a thin wrapper around {@see Renderer}: the editor offers a single
 * "Lookbook" selector, and the server renders the chosen lookbook with the same
 * code path as the shortcode, so there is exactly one renderer to maintain. If
 * the block editor is unavailable (older WordPress), registration is skipped
 * gracefully and the shortcode still works.
 */
final class Block implements HasHooks
{
    public function __construct(private readonly Renderer $renderer)
    {
    }

    public function registerHooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        if (! function_exists('register_block_type')) {
            return;
        }

        $metadata = LOOKBOOK_DIR . 'blocks/lookbook';

        if (! is_readable($metadata . '/block.json')) {
            return;
        }

        // Register the editor script by hand with explicit dependencies, since
        // there is no build step (no generated *.asset.php). block.json then
        // references this handle, so WordPress loads wp.element / wp.blockEditor /
        // wp.serverSideRender for it in the editor.
        wp_register_script(
            'lookbook-block-editor',
            LOOKBOOK_URL . 'blocks/lookbook/index.js',
            [
                'wp-blocks',
                'wp-element',
                'wp-block-editor',
                'wp-components',
                'wp-i18n',
                'wp-server-side-render',
            ],
            \Lookbook\VERSION,
            true,
        );

        register_block_type($metadata, [
            'render_callback' => [$this, 'render'],
        ]);
    }

    /**
     * Server render for the block.
     *
     * @param array<string, mixed> $attributes
     */
    public function render(array $attributes): string
    {
        $id = isset($attributes['lookbookId']) ? (int) $attributes['lookbookId'] : 0;

        return $this->renderer->render($id);
    }
}
