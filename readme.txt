=== Lookbook - Shoppable Image Gallery for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, shoppable, hotspot, lookbook, product gallery
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn any image into a shoppable lookbook: pin WooCommerce products as hotspots that reveal a product card with price and add to cart.

== Description ==

Lookbook turns a single image into a shoppable scene. Upload a photo, pin your
WooCommerce products to it as hotspots, and embed the result anywhere with a
shortcode or the Lookbook block. When a shopper activates a hotspot, a small
product card appears with the thumbnail, title, price and an add-to-cart link —
so they can buy straight from the image.

It is built for stores that sell the look: fashion outfits, room sets, gift
guides, recipe ingredient shots, anywhere several products live in one picture.

= How it works =

1. Create a lookbook under the **Lookbooks** menu and set its image with the
   Featured image box.
2. Add a hotspot for each product: position it with an X and Y percentage and
   enter the product ID. A live preview shows where each marker lands.
3. Embed it with `[lookbook id="123"]` or the **Lookbook** block.

= Features =

* Shoppable image: pin any number of products as hotspots on one image.
* Simple hotspot editor — position by X/Y percentage with a live image preview.
* Accessible hotspot markers: real buttons, keyboard operable, with screen-reader labels.
* Product card popover with thumbnail, title, live price and an add-to-cart link.
* Three marker styles: pulsing dot, plus sign, or numbered.
* `[lookbook id="N"]` shortcode **and** a Lookbook block.
* Reads product data live from WooCommerce — prices and stock are always current.
* Graceful by design: missing image, no hotspots or deleted products render cleanly, never broken.
* Modern, dark-mode-aware styling that respects prefers-reduced-motion.
* No layout shift, no jQuery, assets load only where a lookbook appears.
* Translation ready (POT included) and clean uninstall.
* HPOS and cart/checkout blocks compatible.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/lookbook`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be installed and active.
3. Create your first lookbook under **Lookbooks → Add New**: set the Featured image, then add product hotspots.
4. Adjust global presentation under **WooCommerce → Lookbook**.
5. Embed a lookbook with `[lookbook id="123"]` or the Lookbook block.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Lookbook only runs when WooCommerce is active, and hotspots link to
WooCommerce products.

= How do I find a lookbook's ID? =

Open the Lookbooks list under the Lookbooks menu; the ID is shown when editing
(post=123 in the URL). Use it in the shortcode or block.

= How do I position a hotspot? =

Each hotspot has an X and a Y value, both percentages from the top-left of the
image (0–100). The editor shows a live preview so you can line markers up with
the products in the photo.

= What happens if a pinned product is deleted? =

That hotspot is simply skipped. Lookbook never renders a marker for a product
that is gone or unpublished.

= Can I show the price and an add-to-cart button? =

Yes. Both are toggles under **WooCommerce → Lookbook**, and you can customise the
add-to-cart label.

= Will it slow my pages down or shift the layout? =

No. The stylesheet and script load only on pages that actually render a lookbook,
the script is deferred, and the image reserves its space, so there is no
Cumulative Layout Shift.

== Screenshots ==

1. A shoppable lookbook on the storefront with product hotspots and an open product card.
2. The hotspot editor with a live image preview.
3. The Lookbook settings screen under WooCommerce.

== Changelog ==

= 0.1.0 =
* Initial release: shoppable lookbooks with an image, product hotspots positioned by percentage, an accessible product-card popover, three marker styles, and a `[lookbook]` shortcode plus a Lookbook block.
