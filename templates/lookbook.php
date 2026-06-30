<?php
/**
 * Front-end lookbook template: one or more shoppable images with hotspot markers.
 *
 * @package Lookbook
 *
 * @var int   $lookbookId Lookbook post id.
 * @var string $title     Lookbook title.
 * @var array<int, array{media_type: string, image_id: int, video_url: string, video_id: int, label: string, hotspots: array<int, array{x: float, y: float, product_id: int, product: \WC_Product}>}> $scenes
 * @var array<string, mixed> $settings Resolved plugin settings.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ($scenes === []) {
    return;
}

$multi = count($scenes) > 1;
$rootId = 'lookbook-' . $lookbookId;
?>
<div
    class="lookbook<?php echo $multi ? ' lookbook--multi' : ''; ?>"
    id="<?php echo esc_attr($rootId); ?>"
    data-lookbook
    data-lookbook-id="<?php echo esc_attr((string) $lookbookId); ?>"
>
    <?php if ($multi) : ?>
        <div class="lookbook__nav" role="tablist" aria-label="<?php esc_attr_e('Lookbook images', 'plogins-lookbook'); ?>">
            <?php foreach ($scenes as $sceneIndex => $scene) :
                $label = trim((string) ($scene['label'] ?? ''));
                if ($label === '') {
                    $label = sprintf(
                        /* translators: %d: image number */
                        __('Image %d', 'plogins-lookbook'),
                        $sceneIndex + 1,
                    );
                }
                ?>
                <button
                    type="button"
                    class="lookbook__nav-btn<?php echo $sceneIndex === 0 ? ' is-active' : ''; ?>"
                    role="tab"
                    id="<?php echo esc_attr($rootId . '-tab-' . $sceneIndex); ?>"
                    aria-controls="<?php echo esc_attr($rootId . '-scene-' . $sceneIndex); ?>"
                    aria-selected="<?php echo $sceneIndex === 0 ? 'true' : 'false'; ?>"
                    data-lookbook-tab
                    data-scene-index="<?php echo esc_attr((string) $sceneIndex); ?>"
                >
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="lookbook__scenes">
        <?php
        foreach ($scenes as $sceneIndex => $scene) {
            $partial = LOOKBOOK_DIR . 'templates/partials/lookbook-stage.php';
            if (! is_readable($partial)) {
                continue;
            }

            $mediaType  = isset($scene['media_type']) ? sanitize_key((string) $scene['media_type']) : 'image';
            $imageId    = (int) ($scene['image_id'] ?? 0);
            $videoUrl   = isset($scene['video_url']) ? (string) $scene['video_url'] : '';
            $videoId    = (int) ($scene['video_id'] ?? 0);
            $sceneLabel = trim((string) ($scene['label'] ?? ''));
            $hotspots   = is_array($scene['hotspots'] ?? null) ? $scene['hotspots'] : [];

            if ($mediaType === 'video') {
                if ($videoUrl === '') {
                    continue;
                }
            } elseif ($imageId <= 0) {
                continue;
            }

            require $partial;
        }
        ?>
    </div>
</div>
