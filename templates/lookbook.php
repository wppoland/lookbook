<?php
/**
 * Front-end lookbook template: a shoppable image with accessible hotspot markers.
 *
 * Each marker is a real <button> with a popovertarget pointing at a product card
 * popover (native Popover API; the bundled script supplies a fallback and arrow
 * positioning). Markers are absolutely positioned by x/y percentage so the layout
 * is fluid and there is no Cumulative Layout Shift — the image reserves its space
 * via width/height attributes.
 *
 * @package Lookbook
 *
 * @var int                                                                         $lookbookId Lookbook post id.
 * @var string                                                                      $title      Lookbook title.
 * @var int                                                                         $imageId    Featured image attachment id.
 * @var array<int, array{x: float, y: float, product_id: int, product: \WC_Product}> $hotspots  Resolved hotspots.
 * @var array<string, mixed>                                                        $settings   Resolved plugin settings.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope, not true globals.

$markerStyle    = in_array(($settings['marker_style'] ?? 'dot'), ['dot', 'plus', 'numbered'], true)
    ? (string) $settings['marker_style']
    : 'dot';
$showPrice      = ! empty($settings['show_price']);
$showAddToCart  = ! empty($settings['show_add_to_cart']);
$addToCartLabel = trim((string) ($settings['add_to_cart_text'] ?? ''));

$image = wp_get_attachment_image(
    $imageId,
    'large',
    false,
    [
        'class'    => 'lookbook__image',
        'loading'  => 'lazy',
        'decoding' => 'async',
        'alt'      => $title !== '' ? $title : __('Shoppable lookbook', 'lookbook'),
    ],
);

if (! is_string($image) || $image === '') {
    return;
}

$baseId = 'lookbook-' . $lookbookId;
?>
<div
    class="lookbook lookbook--marker-<?php echo esc_attr($markerStyle); ?>"
    id="<?php echo esc_attr($baseId); ?>"
    data-lookbook
>
    <div class="lookbook__stage">
        <?php
        // Image markup is built by core with safe attributes.
        echo wp_kses_post($image);
        ?>

        <?php if ($hotspots === []) : ?>
            <?php // No purchasable hotspots: show just the image, never a broken marker. ?>
        <?php else : ?>
            <ul class="lookbook__hotspots" role="list">
                <?php foreach ($hotspots as $index => $hotspot) :
                    $product   = $hotspot['product'];
                    $number    = $index + 1;
                    $popoverId = $baseId . '-card-' . $number;

                    $productName  = $product->get_name();
                    $productUrl   = $product->get_permalink();
                    $priceHtml    = $product->get_price_html();
                    $addUrl       = method_exists($product, 'add_to_cart_url') ? $product->add_to_cart_url() : $productUrl;
                    $defaultLabel = $product->add_to_cart_text();
                    $ctaLabel     = $addToCartLabel !== '' ? $addToCartLabel : $defaultLabel;

                    /* translators: %s: product name. */
                    $markerLabel = sprintf(__('View %s', 'lookbook'), $productName);
                    ?>
                    <li
                        class="lookbook__hotspot"
                        style="left: <?php echo esc_attr((string) $hotspot['x']); ?>%; top: <?php echo esc_attr((string) $hotspot['y']); ?>%;"
                    >
                        <button
                            type="button"
                            class="lookbook__marker"
                            popovertarget="<?php echo esc_attr($popoverId); ?>"
                            aria-label="<?php echo esc_attr($markerLabel); ?>"
                            aria-haspopup="dialog"
                        >
                            <span class="lookbook__marker-number" aria-hidden="true"><?php echo esc_html((string) $number); ?></span>
                            <span class="screen-reader-text"><?php echo esc_html($markerLabel); ?></span>
                        </button>

                        <div
                            id="<?php echo esc_attr($popoverId); ?>"
                            class="lookbook__card"
                            role="dialog"
                            aria-label="<?php echo esc_attr($productName); ?>"
                            popover
                        >
                            <a class="lookbook__card-link" href="<?php echo esc_url($productUrl); ?>">
                                <?php
                                $thumb = $product->get_image(
                                    'woocommerce_thumbnail',
                                    ['class' => 'lookbook__card-thumb', 'loading' => 'lazy', 'decoding' => 'async'],
                                );
                                echo wp_kses_post($thumb);
                                ?>
                                <span class="lookbook__card-title"><?php echo esc_html($productName); ?></span>
                            </a>

                            <?php if ($showPrice && is_string($priceHtml) && $priceHtml !== '') : ?>
                                <span class="lookbook__card-price"><?php echo wp_kses_post($priceHtml); ?></span>
                            <?php endif; ?>

                            <?php if ($showAddToCart && $product->is_purchasable() && $product->is_in_stock()) : ?>
                                <a
                                    class="lookbook__card-cta button"
                                    href="<?php echo esc_url($addUrl); ?>"
                                    data-product-id="<?php echo esc_attr((string) $hotspot['product_id']); ?>"
                                    rel="nofollow"
                                >
                                    <?php echo esc_html($ctaLabel); ?>
                                </a>
                            <?php else : ?>
                                <a class="lookbook__card-cta button" href="<?php echo esc_url($productUrl); ?>">
                                    <?php esc_html_e('View product', 'lookbook'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
