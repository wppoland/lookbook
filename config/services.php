<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Lookbook is self-contained: the lookbook post type, the hotspot
 * editor and the front-end renderer all live in this plugin.
 *
 * @package Lookbook
 */

declare(strict_types=1);

use Lookbook\Admin\MetaBox;
use Lookbook\Admin\Settings;
use Lookbook\Container;
use Lookbook\Migrator;
use Lookbook\PostType;
use Lookbook\Repository;
use Lookbook\Service\Block;
use Lookbook\Service\Renderer;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    $c->singleton(Repository::class, static fn (): Repository => new Repository());

    // The lookbook custom post type.
    $c->singleton(PostType::class, static fn (): PostType => new PostType());

    // Front-end renderer (shared by the shortcode and the block).
    $c->singleton(Renderer::class, static fn (Container $c): Renderer => new Renderer($c->get(Repository::class)));

    // Lookbook block — a thin wrapper over the renderer.
    $c->singleton(Block::class, static fn (Container $c): Block => new Block($c->get(Renderer::class)));

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(MetaBox::class, static fn (Container $c): MetaBox => new MetaBox($c->get(Repository::class)));
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
    }
};
