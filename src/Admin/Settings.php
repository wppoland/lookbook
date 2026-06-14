<?php

declare(strict_types=1);

namespace Lookbook\Admin;

defined('ABSPATH') || exit;

use Lookbook\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu ("WooCommerce →
 * Lookbook"). Stores global presentation defaults in the `lookbook_settings`
 * option (array): the master toggle, marker style, whether the card shows the
 * price / add-to-cart link, and the add-to-cart label.
 *
 * All output is escaped; all input is sanitised on save. The screen uses
 * manage_woocommerce so shop managers can configure it.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'lookbook_settings';
    private const PAGE   = 'lookbook-settings';

    /** Allowed marker styles (mapped to CSS classes by the template). */
    private const MARKERS = ['dot', 'plus', 'numbered'];

    /** Incremented to give each inline-help control a unique id/anchor. */
    private int $helpSeq = 0;

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_' . self::PAGE) {
            return;
        }

        wp_enqueue_style(
            'lookbook-admin',
            LOOKBOOK_URL . 'assets/css/admin.css',
            [],
            \Lookbook\VERSION,
        );

        wp_enqueue_script(
            'lookbook-admin',
            LOOKBOOK_URL . 'assets/js/admin.js',
            [],
            \Lookbook\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Lookbook — Shoppable Image Gallery', 'lookbook'),
            __('Lookbook', 'lookbook'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->settings();
        ?>
        <div class="wrap lookbook-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="lookbook-intro">
                <p>
                    <?php esc_html_e('Lookbook turns an image into a shoppable scene: pin products as hotspots, then embed the lookbook anywhere with a shortcode or the Lookbook block. Create and edit lookbooks under the Lookbooks menu; these settings control how every lookbook looks and behaves on the storefront.', 'lookbook'); ?>
                </p>
                <p class="lookbook-intro__embed">
                    <?php
                    printf(
                        /* translators: %s: the shortcode example. */
                        esc_html__('Embed a lookbook with %s (replace 123 with the lookbook ID shown in the Lookbooks list).', 'lookbook'),
                        '<code>[lookbook id="123"]</code>',
                    );
                    ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="lookbook-card">
                    <h2><?php esc_html_e('General', 'lookbook'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable Lookbook', 'lookbook'); ?>
                                    <?php $this->help(__('The master switch. When off, lookbooks render nothing on the front end and no CSS/JS is loaded.', 'lookbook')); ?>
                                </th>
                                <td>
                                    <label for="lookbook_enabled">
                                        <input
                                            type="checkbox"
                                            id="lookbook_enabled"
                                            name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                            value="1"
                                            <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                        />
                                        <?php esc_html_e('Render lookbooks on the storefront.', 'lookbook'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="lookbook_marker_style"><?php esc_html_e('Marker style', 'lookbook'); ?></label>
                                    <?php $this->help(__('How each hotspot marker looks on the image. Dots are subtle, plus signs are clear, numbered markers help when a lookbook has many products.', 'lookbook')); ?>
                                </th>
                                <td>
                                    <select id="lookbook_marker_style" name="<?php echo esc_attr(self::OPTION); ?>[marker_style]">
                                        <?php
                                        $current = (string) ($settings['marker_style'] ?? 'dot');
                                        $labels  = [
                                            'dot'      => __('Pulsing dot', 'lookbook'),
                                            'plus'     => __('Plus sign', 'lookbook'),
                                            'numbered' => __('Numbered', 'lookbook'),
                                        ];
                                        foreach (self::MARKERS as $marker) :
                                            ?>
                                            <option value="<?php echo esc_attr($marker); ?>" <?php selected($current, $marker); ?>>
                                                <?php echo esc_html($labels[$marker] ?? $marker); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="lookbook-card">
                    <h2><?php esc_html_e('Product card', 'lookbook'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <?php
                            $this->checkboxRow(
                                'show_price',
                                __('Price', 'lookbook'),
                                __('Show the product price in the hotspot card.', 'lookbook'),
                                $settings,
                                __('The price is read live from WooCommerce, so it always reflects sales and currency settings.', 'lookbook'),
                            );
                            $this->checkboxRow(
                                'show_add_to_cart',
                                __('Add to cart link', 'lookbook'),
                                __('Show an add-to-cart link in the hotspot card.', 'lookbook'),
                                $settings,
                                __('Uses WooCommerce\'s add-to-cart URL, so it works for simple products and links variable products to their page.', 'lookbook'),
                            );
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="lookbook_add_to_cart_text"><?php esc_html_e('Add to cart label', 'lookbook'); ?></label>
                                    <?php $this->help(__('The text used for the add-to-cart link inside the card. Leave blank to use the WooCommerce default for each product.', 'lookbook')); ?>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="lookbook_add_to_cart_text"
                                        name="<?php echo esc_attr(self::OPTION); ?>[add_to_cart_text]"
                                        value="<?php echo esc_attr((string) ($settings['add_to_cart_text'] ?? '')); ?>"
                                        class="regular-text"
                                        placeholder="<?php esc_attr_e('e.g. Add to cart', 'lookbook'); ?>"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php
                /**
                 * Fires after Lookbook's own settings cards, before the submit
                 * button. PRO add-ons render their extra settings cards here; the
                 * sanitize filter below preserves any keys they add.
                 *
                 * @param array<string, mixed> $settings Resolved settings.
                 * @param string               $option   The option name.
                 */
                do_action('lookbook_admin_settings_after_cards', $settings, self::OPTION);
                ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render an accessible inline-help affordance: a "?" button that toggles a
     * popover describing the adjacent setting. Uses the native Popover API with a
     * scripted fallback for browsers without support.
     */
    private function help(string $text): void
    {
        $id = 'lookbook-help-' . (++$this->helpSeq);
        ?>
        <button
            type="button"
            class="lookbook-help"
            aria-label="<?php esc_attr_e('More information', 'lookbook'); ?>"
            aria-describedby="<?php echo esc_attr($id); ?>"
            aria-expanded="false"
            popovertarget="<?php echo esc_attr($id); ?>"
        >?</button>
        <div id="<?php echo esc_attr($id); ?>" class="lookbook-tip" role="tooltip" popover hidden>
            <?php echo esc_html($text); ?>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row in the form-table.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings, string $tip = ''): void
    {
        $id = 'lookbook_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php if ($tip !== '') {
                    $this->help($tip);
                } ?>
            </th>
            <td>
                <label for="<?php echo esc_attr($id); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($id); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                        value="1"
                        <?php checked((bool) ($settings[$key] ?? false), true); ?>
                    />
                    <?php echo esc_html($help); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitises and validates the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $marker = isset($raw['marker_style']) ? sanitize_key((string) $raw['marker_style']) : 'dot';

        if (! in_array($marker, self::MARKERS, true)) {
            $marker = 'dot';
        }

        $sanitized = [
            'enabled'          => ! empty($raw['enabled']),
            'marker_style'     => $marker,
            'show_price'       => ! empty($raw['show_price']),
            'show_add_to_cart' => ! empty($raw['show_add_to_cart']),
            'add_to_cart_text' => isset($raw['add_to_cart_text'])
                ? sanitize_text_field((string) $raw['add_to_cart_text'])
                : '',
        ];

        return (array) apply_filters('lookbook_sanitize_settings', $sanitized, $raw);
    }

    /**
     * Stored settings merged over packaged defaults.
     *
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
}
