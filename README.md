# Lookbook - Shoppable Image Gallery for WooCommerce

Turn any image into a shoppable lookbook: pin WooCommerce products as hotspots
that reveal a small product card (thumbnail, title, price, add-to-cart link).

Self-contained WooCommerce plugin — no shared kit dependency.

## What it does

- A **lookbook** is one image plus hotspots. Each hotspot is positioned by an
  X/Y percentage and linked to a WooCommerce product.
- The front end renders the image with accessible hotspot markers; activating a
  marker opens a product-card popover.
- Embed with the `[lookbook id="N"]` shortcode or the **Lookbook** block.

## Architecture

- `lookbook.php` — bootstrap. Boots on `init:0` and fires `do_action('lookbook/booted', …)`
  from `Plugin::boot()`. Never calls translation functions at `plugins_loaded` scope.
- `src/Plugin.php` + `src/Container.php` — minimal DI container (lazy singletons, `has()`).
- `src/PostType.php` — the `lookbook` custom post type (admin-only, title + thumbnail).
- `src/Repository.php` — single source of truth for hotspot shape + sanitisation.
- `src/Admin/MetaBox.php` — the hotspot repeater editor with a live preview.
- `src/Admin/Settings.php` — **WooCommerce → Lookbook** settings page.
- `src/Service/Renderer.php` — front-end renderer shared by the shortcode and block.
- `src/Service/Block.php` — the `wppoland/lookbook` dynamic block.
- `templates/` — front-end + admin templates. `blocks/lookbook/` — block metadata + editor script.

## Development

```bash
composer install
composer cs        # PHPCS (WordPress security sniffs)
composer analyse   # PHPStan level 6
```

The MVP hotspot editor is intentionally simple (a repeater of X%, Y%, product
ID). Drag-to-place is a Lookbook Pro feature.
