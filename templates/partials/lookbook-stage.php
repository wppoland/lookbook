<?php
/**
 * Single lookbook scene: image or video plus its hotspot markers.
 *
 * @package Lookbook
 *
 * @var int                                                                         $lookbookId Lookbook post id.
 * @var int                                                                         $sceneIndex Zero-based scene index.
 * @var string                                                                      $mediaType  Scene media type (image|video).
 * @var int                                                                         $imageId    Attachment id (image or video poster).
 * @var string                                                                      $videoUrl   Video source URL when mediaType is video.
 * @var int                                                                         $videoId    Video attachment id.
 * @var string                                                                      $sceneLabel Tab label.
 * @var array<int, array{x: float, y: float, product_id: int, product: \WC_Product}> $hotspots  Resolved hotspots.
 * @var array<string, mixed>                                                        $settings   Resolved plugin settings.
 * @var string                                                                      $title      Lookbook title.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$showPrice      = ! empty($settings['show_price']);
$showAddToCart  = ! empty($settings['show_add_to_cart']);
$addToCartLabel = trim((string) ($settings['add_to_cart_text'] ?? ''));

$mediaContext = [
    'lookbookId' => $lookbookId,
    'sceneIndex' => $sceneIndex,
    'media_type' => $mediaType,
    'image_id'   => $imageId,
    'video_url'  => $videoUrl,
    'video_id'   => $videoId,
    'title'      => $title,
];

/** @var string $mediaHtml */
$mediaHtml = apply_filters('lookbook/scene_media_html', '', $mediaContext);

if ($mediaHtml === '') {
    if ($mediaType === 'video' && $videoUrl !== '') {
        $poster = $imageId > 0 ? wp_get_attachment_image_url($imageId, 'large') : false;
        $attrs  = [
            'class'    => 'lookbook__video',
            'src'      => $videoUrl,
            'playsinline' => true,
            'muted'    => true,
            'loop'     => true,
            'autoplay' => true,
            'preload'  => 'metadata',
        ];

        if (is_string($poster) && $poster !== '') {
            $attrs['poster'] = $poster;
        }

        $attrString = '';
        foreach ($attrs as $name => $value) {
            if ($value === true) {
                $attrString .= ' ' . esc_attr($name);
                continue;
            }

            $attrString .= sprintf(' %s="%s"', esc_attr($name), esc_attr((string) $value));
        }

        $mediaHtml = sprintf('<video%s></video>', $attrString);
    } else {
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

        $mediaHtml = is_string($image) ? $image : '';
    }
}

if ($mediaHtml === '') {
    return;
}

$baseId = 'lookbook-' . $lookbookId . '-scene-' . $sceneIndex;
$active = $sceneIndex === 0;
?>
<div
    class="lookbook__scene<?php echo $active ? ' is-active' : ''; ?>"
    id="<?php echo esc_attr($baseId); ?>"
    data-lookbook-scene
    data-scene-index="<?php echo esc_attr((string) $sceneIndex); ?>"
    role="tabpanel"
    aria-label="<?php echo esc_attr($sceneLabel !== '' ? $sceneLabel : sprintf(/* translators: %d: image number */ __('Image %d', 'lookbook'), $sceneIndex + 1)); ?>"
    <?php echo $active ? '' : ' hidden'; ?>
>
    <div class="lookbook__stage">
        <?php echo wp_kses_post($mediaHtml); ?>

        <?php if ($hotspots !== []) : ?>
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
                            data-lookbook-hotspot
                            data-lookbook-id="<?php echo esc_attr((string) $lookbookId); ?>"
                            data-scene-index="<?php echo esc_attr((string) $sceneIndex); ?>"
                            data-hotspot-index="<?php echo esc_attr((string) $index); ?>"
                            data-product-id="<?php echo esc_attr((string) $hotspot['product_id']); ?>"
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
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Default is sanitized above; custom HTML from filter is safe.
                                echo apply_filters('lookbook/card_image_html', wp_kses_post($thumb), $product, $hotspot);
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
