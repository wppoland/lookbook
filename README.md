# Lookbook - Shoppable Image Gallery for WooCommerce

Lookbook turns a single image into a shoppable scene. Pin your WooCommerce products to a photo as
hotspots; when a shopper activates one, a small product card appears with the thumbnail, title,
live price and an add-to-cart link — so they can buy straight from the image.

## Features

- Pin any number of products as hotspots on one image, positioned by X/Y percentage.
- Simple hotspot editor with a live image preview.
- Accessible hotspot markers: real buttons, keyboard operable, with screen-reader labels.
- Product card popover with thumbnail, title, live price and an add-to-cart link.
- Three marker styles: pulsing dot, plus sign, or numbered.
- Embed with the `[lookbook id="N"]` shortcode or the Lookbook block.
- No layout shift, no jQuery; assets load only where a lookbook appears.

## Installation

1. Upload the plugin to `/wp-content/plugins/lookbook`, or install it via Plugins → Add New.
2. Activate it. WooCommerce must be installed and active.
3. Create a lookbook under Lookbooks → Add New: set the Featured image, then add product hotspots.
4. Embed it with `[lookbook id="123"]` or the Lookbook block.

## Frequently Asked Questions

**Does it require WooCommerce?**
Yes. Lookbook only runs when WooCommerce is active, and hotspots link to WooCommerce products.

**What happens if a pinned product is deleted?**
That hotspot is simply skipped. Lookbook never renders a marker for a product that is gone or
unpublished.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
