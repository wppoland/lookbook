<?php

declare(strict_types=1);

namespace Lookbook\Service;

use Lookbook\Contract\HasHooks;
use Lookbook\PostType;
use Lookbook\Repository;

defined('ABSPATH') || exit;

/**
 * Renders a lookbook on the front end: the image plus accessible hotspot markers
 * that reveal a small product card (thumbnail, title, price, add-to-cart link).
 *
 * Powers the [lookbook] shortcode. It is defensive
 * by design — a disabled plugin, a missing lookbook, a lookbook with no image,
 * or hotspots that point at gone products all degrade to rendering nothing (or
 * just the image) rather than producing broken markup. All output is escaped.
 */
final class Renderer implements HasHooks
{
    private const OPTION = 'lookbook_settings';
    private const HANDLE = 'lookbook';

    public function __construct(private readonly Repository $repository)
    {
    }

    public function registerHooks(): void
    {
        add_shortcode('lookbook', [$this, 'shortcode']);
        // Register assets so they can be enqueued on demand when a lookbook renders.
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
    }

    public function registerAssets(): void
    {
        wp_register_style(
            self::HANDLE,
            LOOKBOOK_URL . 'assets/css/lookbook.css',
            [],
            \Lookbook\VERSION,
        );

        wp_register_script(
            self::HANDLE,
            LOOKBOOK_URL . 'assets/js/lookbook.js',
            [],
            \Lookbook\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    /**
     * [lookbook id="123"] shortcode handler.
     *
     * @param array<string, mixed>|string $atts
     */
    public function shortcode(array|string $atts): string
    {
        $atts = shortcode_atts(['id' => 0], is_array($atts) ? $atts : [], 'lookbook');

        return $this->render((int) $atts['id']);
    }

    /**
     * Render a lookbook by id and return its HTML (empty string when nothing
     * should be shown).
     */
    public function render(int $lookbookId): string
    {
        if ($lookbookId <= 0 || ! $this->isEnabled()) {
            return '';
        }

        $post = get_post($lookbookId);

        if (! $post instanceof \WP_Post || $post->post_type !== PostType::POST_TYPE || $post->post_status !== 'publish') {
            return '';
        }

        $imageId = (int) get_post_thumbnail_id($lookbookId);

        if ($imageId <= 0) {
            return '';
        }

        $hotspots = $this->resolveHotspots($lookbookId);

        // Lazily load assets only when a lookbook actually renders.
        if (wp_style_is(self::HANDLE, 'registered')) {
            wp_enqueue_style(self::HANDLE);
        }
        if (wp_script_is(self::HANDLE, 'registered')) {
            wp_enqueue_script(self::HANDLE);
        }

        $context = [
            'lookbookId' => $lookbookId,
            'title'      => $post->post_title,
            'imageId'    => $imageId,
            'hotspots'   => $hotspots,
            'settings'   => $this->settings(),
        ];

        return $this->renderTemplate('lookbook', $context);
    }

    /**
     * Resolve stored hotspots into render-ready rows, dropping any that point at
     * a product that is gone or not visible.
     *
     * @return array<int, array{
     *     x: float, y: float, product_id: int, product: \WC_Product
     * }>
     */
    private function resolveHotspots(int $lookbookId): array
    {
        $resolved = [];

        foreach ($this->repository->hotspots($lookbookId) as $hotspot) {
            if (! function_exists('wc_get_product')) {
                break;
            }

            $product = wc_get_product($hotspot['product_id']);

            if (! $product instanceof \WC_Product || $product->get_status() !== 'publish') {
                continue;
            }

            $resolved[] = [
                'x'          => $hotspot['x'],
                'y'          => $hotspot['y'],
                'product_id' => $hotspot['product_id'],
                'product'    => $product,
            ];
        }

        return $resolved;
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require LOOKBOOK_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $template, array $context): string
    {
        $file = LOOKBOOK_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            return '';
        }

        extract($context, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
