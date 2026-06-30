<?php

declare(strict_types=1);

namespace Lookbook\Admin;

defined('ABSPATH') || exit;

use Lookbook\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu ("WooCommerce →
 * Lookbook"). Stores global presentation defaults in the `lookbook_settings`
 * option (array): the master toggle, whether the card shows the price /
 * add-to-cart link, and the add-to-cart label.
 *
 * All output is escaped; all input is sanitised on save. The screen uses
 * manage_woocommerce so shop managers can configure it.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'lookbook_settings';
    private const PAGE   = 'lookbook-settings';

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
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Lookbook: Shoppable Image Gallery', 'plogins-lookbook'),
            __('Lookbook', 'plogins-lookbook'),
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
                    <?php esc_html_e('Lookbook turns an image into a shoppable scene: pin products as hotspots, then embed the lookbook anywhere with a shortcode. Create and edit lookbooks under the Lookbooks menu; these settings control how every lookbook looks and behaves on the storefront.', 'plogins-lookbook'); ?>
                </p>
                <p class="lookbook-intro__embed">
                    <?php
                    printf(
                        /* translators: %s: the shortcode example. */
                        esc_html__('Embed a lookbook with %s (replace 123 with the lookbook ID shown in the Lookbooks list).', 'plogins-lookbook'),
                        '<code>[lookbook id="123"]</code>',
                    );
                    ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="lookbook-card">
                    <h2><?php esc_html_e('General', 'plogins-lookbook'); ?></h2>
                    <p class="lookbook-card__desc">
                        <?php esc_html_e('The master switch for everything Lookbook puts on your storefront.', 'plogins-lookbook'); ?>
                    </p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Show lookbooks on the storefront', 'plogins-lookbook'); ?>
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
                                        <?php esc_html_e('Render your lookbooks where the shortcode appears.', 'plogins-lookbook'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('Turn this off to hide every lookbook at once without deleting anything, the shortcode outputs nothing and Lookbook loads no CSS or JavaScript on the page. Useful while you are still setting up. On by default.', 'plogins-lookbook'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="lookbook-card">
                    <h2><?php esc_html_e('Product card', 'plogins-lookbook'); ?></h2>
                    <p class="lookbook-card__desc">
                        <?php esc_html_e('What a shopper sees in the little card that pops up when they tap a hotspot. These apply to every lookbook; the product name and image always show.', 'plogins-lookbook'); ?>
                    </p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <?php
                            $this->checkboxRow(
                                'show_price',
                                __('Price', 'plogins-lookbook'),
                                __('Show the product price in the pop-up card.', 'plogins-lookbook'),
                                __('Lets shoppers see the cost before they leave the image. On by default.', 'plogins-lookbook'),
                                $settings,
                            );
                            $this->checkboxRow(
                                'show_add_to_cart',
                                __('Add to cart link', 'plogins-lookbook'),
                                __('Show an add-to-cart link in the pop-up card.', 'plogins-lookbook'),
                                __('Lets shoppers buy straight from the image without opening the product page. On by default.', 'plogins-lookbook'),
                                $settings,
                            );
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="lookbook_add_to_cart_text"><?php esc_html_e('Add to cart label', 'plogins-lookbook'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="lookbook_add_to_cart_text"
                                        name="<?php echo esc_attr(self::OPTION); ?>[add_to_cart_text]"
                                        value="<?php echo esc_attr((string) ($settings['add_to_cart_text'] ?? '')); ?>"
                                        class="regular-text"
                                        placeholder="<?php esc_attr_e('e.g. Add to cart', 'plogins-lookbook'); ?>"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Overrides the wording on the add-to-cart link for every product, for example “Add to cart”, “Shop the look”, or “Add to bag”. Leave blank to keep each product’s own WooCommerce button text. Only used when the add-to-cart link above is on.', 'plogins-lookbook'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php
                /**
                 * Fires inside the settings form after the core setting cards,
                 * before the submit button. Add-ons (e.g. Lookbook Pro) hook
                 * this to render their own cards within the same form so their
                 * fields are submitted under the shared option.
                 *
                 * @param array<string, mixed> $settings Resolved settings (defaults merged over stored).
                 * @param string               $option   The shared option name.
                 */
                do_action('lookbook_admin_settings_after_cards', $settings, self::OPTION);
                ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row in the form-table.
     *
     * @param string               $label  The control label (the th).
     * @param string               $help   The inline label next to the checkbox.
     * @param string               $detail The description line below: name the effect.
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, string $detail, array $settings): void
    {
        $id = 'lookbook_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
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
                <p class="description"><?php echo esc_html($detail); ?></p>
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

        $sanitized = [
            'enabled'          => ! empty($raw['enabled']),
            'show_price'       => ! empty($raw['show_price']),
            'show_add_to_cart' => ! empty($raw['show_add_to_cart']),
            'add_to_cart_text' => isset($raw['add_to_cart_text'])
                ? sanitize_text_field((string) $raw['add_to_cart_text'])
                : '',
        ];

        /**
         * Filters the sanitised settings before they are stored. Add-ons (e.g.
         * Lookbook Pro) hook this to sanitise and re-attach their own keys,
         * which the core sanitiser would otherwise drop on save.
         *
         * @param array<string, mixed> $sanitized The core plugin's clean settings.
         * @param array<string, mixed> $raw       The raw submitted values.
         * @return array<string, mixed>
         */
        $filtered = apply_filters('lookbook_sanitize_settings', $sanitized, $raw);

        return is_array($filtered) ? $filtered : $sanitized;
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
