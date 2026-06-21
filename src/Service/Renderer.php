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

        $scenes = $this->resolveScenes($lookbookId, $post, $imageId, $hotspots);

        if ($scenes === []) {
            return '';
        }

        $context = [
            'lookbookId' => $lookbookId,
            'title'      => $post->post_title,
            'scenes'     => $scenes,
            'settings'   => $this->settings(),
        ];

        $html = $this->renderTemplate('lookbook', $context);

        if ('' !== $html) {
            /**
             * Fires after a lookbook renders on the storefront.
             *
             * @param int                  $lookbookId Lookbook post ID.
             * @param array<string, mixed> $context    Template context.
             */
            do_action('lookbook/rendered', $lookbookId, $context);
        }

        return $html;
    }

    /**
     * Build the scene list (featured image first), then allow PRO to append more.
     *
     * @param array<int, array{x: float, y: float, product_id: int, product: \WC_Product}> $featuredHotspots
     * @return array<int, array{
     *     media_type: string,
     *     image_id: int,
     *     video_url: string,
     *     video_id: int,
     *     label: string,
     *     hotspots: array<int, array{x: float, y: float, product_id: int, product: \WC_Product}>
     * }>
     */
    private function resolveScenes(int $lookbookId, \WP_Post $post, int $featuredImageId, array $featuredHotspots): array
    {
        $default = [
            [
                'media_type' => 'image',
                'image_id'   => $featuredImageId,
                'video_url'  => '',
                'video_id'   => 0,
                'label'      => '',
                'hotspots'   => $featuredHotspots,
            ],
        ];

        /** @var array<int, array<string, mixed>> $raw */
        $raw = apply_filters('lookbook/scenes', $default, $lookbookId, $post);

        $scenes = [];

        foreach ($raw as $scene) {
            if (! is_array($scene)) {
                continue;
            }

            $mediaType = isset($scene['media_type']) ? sanitize_key((string) $scene['media_type']) : 'image';
            if ($mediaType !== 'video') {
                $mediaType = 'image';
            }

            $imageId  = isset($scene['image_id']) ? (int) $scene['image_id'] : 0;
            $videoUrl = isset($scene['video_url']) ? esc_url_raw((string) $scene['video_url']) : '';
            $videoId  = isset($scene['video_id']) ? absint($scene['video_id']) : 0;

            if ($mediaType === 'video') {
                if ($videoUrl === '' && $videoId > 0) {
                    $attachmentUrl = wp_get_attachment_url($videoId);
                    $videoUrl      = is_string($attachmentUrl) ? esc_url_raw($attachmentUrl) : '';
                }

                if ($videoUrl === '') {
                    continue;
                }
            } elseif ($imageId <= 0) {
                continue;
            }

            $spots = $scene['hotspots'] ?? [];
            if (! is_array($spots)) {
                $spots = [];
            }

            $scenes[] = [
                'media_type' => $mediaType,
                'image_id'   => $imageId,
                'video_url'  => $videoUrl,
                'video_id'   => $videoId,
                'label'      => isset($scene['label']) ? (string) $scene['label'] : '',
                'hotspots'   => $spots,
            ];
        }

        return $scenes;
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

            $resolved[] = array_merge(
                [
                    'x'          => $hotspot['x'],
                    'y'          => $hotspot['y'],
                    'product_id' => $hotspot['product_id'],
                    'product'    => $product,
                ],
                $this->extraHotspotFields($hotspot),
            );
        }

        return $resolved;
    }

    /**
     * Preserve optional hotspot fields registered by add-ons (e.g. video_url).
     *
     * @param array<string, mixed> $hotspot
     * @return array<string, mixed>
     */
    private function extraHotspotFields(array $hotspot): array
    {
        $extra = [];

        if (isset($hotspot['video_url']) && is_string($hotspot['video_url']) && $hotspot['video_url'] !== '') {
            $extra['video_url'] = esc_url_raw($hotspot['video_url']);
        }

        if (isset($hotspot['video_id']) && absint($hotspot['video_id']) > 0) {
            $extra['video_id'] = absint($hotspot['video_id']);
        }

        /** @var array<string, mixed> $extra */
        return apply_filters('lookbook/hotspot_fields', $extra, $hotspot);
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
