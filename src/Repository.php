<?php

declare(strict_types=1);

namespace Lookbook;

defined('ABSPATH') || exit;

/**
 * Reads and persists lookbook hotspot data.
 *
 * Hotspots are stored as a single post-meta array under `_lookbook_hotspots`.
 * Each hotspot is `['x' => float, 'y' => float, 'product_id' => int]`, where
 * x/y are percentages (0–100) of the image's width/height. The repository is
 * the single source of truth for shape and sanitisation, so the editor and the
 * front-end renderer never disagree about the data.
 */
final class Repository
{
    public const META_HOTSPOTS = '_lookbook_hotspots';

    /**
     * Resolved, validated hotspots for a lookbook.
     *
     * Only hotspots that reference a published, visible product survive; the rest
     * are dropped so the renderer never paints a marker for a missing product.
     *
     * @return array<int, array{x: float, y: float, product_id: int}>
     */
    public function hotspots(int $lookbookId): array
    {
        $raw = get_post_meta($lookbookId, self::META_HOTSPOTS, true);

        if (! is_array($raw)) {
            return [];
        }

        $clean = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $productId = isset($row['product_id']) ? absint($row['product_id']) : 0;

            if ($productId <= 0) {
                continue;
            }

            $clean[] = [
                'x'          => $this->clampPercent($row['x'] ?? 50),
                'y'          => $this->clampPercent($row['y'] ?? 50),
                'product_id' => $productId,
            ];
        }

        return $clean;
    }

    /**
     * Sanitise a raw POST payload into storable hotspots.
     *
     * @param mixed $raw
     * @return array<int, array{x: float, y: float, product_id: int}>
     */
    public function sanitizeHotspots(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $clean = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $productId = isset($row['product_id']) ? absint($row['product_id']) : 0;

            if ($productId <= 0) {
                continue;
            }

            $clean[] = [
                'x'          => $this->clampPercent($row['x'] ?? 50),
                'y'          => $this->clampPercent($row['y'] ?? 50),
                'product_id' => $productId,
            ];
        }

        return array_values($clean);
    }

    /**
     * Persist sanitised hotspots for a lookbook.
     *
     * @param array<int, array{x: float, y: float, product_id: int}> $hotspots
     */
    public function saveHotspots(int $lookbookId, array $hotspots): void
    {
        if ($hotspots === []) {
            delete_post_meta($lookbookId, self::META_HOTSPOTS);
            return;
        }

        update_post_meta($lookbookId, self::META_HOTSPOTS, $hotspots);
    }

    /**
     * Clamp an arbitrary value to a 0–100 percentage with one decimal place.
     *
     * @param mixed $value
     */
    private function clampPercent(mixed $value): float
    {
        $float = is_numeric($value) ? (float) $value : 0.0;

        return round(max(0.0, min(100.0, $float)), 1);
    }
}
