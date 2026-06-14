<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Lookbook\Contract\HasHooks.
 *
 * @package Lookbook
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Lookbook\Admin\MetaBox;
use Lookbook\Admin\Settings;
use Lookbook\PostType;
use Lookbook\Service\Renderer;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        PostType::class,
        Renderer::class,
        MetaBox::class,
        Settings::class,
    ]
    : [
        PostType::class,
        Renderer::class,
    ];
