<?php
/**
 * Hotspot editor meta box. A simple repeater: each row is an x%, a y% and a
 * product id. The featured image is the canvas; positions are entered as
 * percentages from the top-left.
 *
 * @package Lookbook
 *
 * @var array<int, array{x: float, y: float, product_id: int}> $hotspots Stored hotspots.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope, not true globals.
?>
<div class="lookbook-editor" data-lookbook-editor>
    <p class="lookbook-editor__intro">
        <?php esc_html_e('Set the lookbook image using the Featured image box, then add a hotspot for each product. Position each one with X and Y as a percentage from the top-left of the image (0–100), and enter the product ID to link.', 'lookbook'); ?>
    </p>

    <div class="lookbook-editor__rows-wrap">
        <table class="widefat lookbook-editor__table" data-lookbook-rows>
                <thead>
                    <tr>
                        <th scope="col" class="lookbook-editor__col-num"><?php esc_html_e('#', 'lookbook'); ?></th>
                        <th scope="col"><?php esc_html_e('X %', 'lookbook'); ?></th>
                        <th scope="col"><?php esc_html_e('Y %', 'lookbook'); ?></th>
                        <th scope="col"><?php esc_html_e('Product ID', 'lookbook'); ?></th>
                        <th scope="col" class="lookbook-editor__col-actions"><span class="screen-reader-text"><?php esc_html_e('Actions', 'lookbook'); ?></span></th>
                    </tr>
                </thead>
                <tbody data-lookbook-rows-body>
                    <?php
                    $rows = $hotspots === [] ? [['x' => 50.0, 'y' => 50.0, 'product_id' => 0]] : $hotspots;
                    foreach ($rows as $i => $row) :
                        ?>
                        <tr class="lookbook-editor__row" data-lookbook-row>
                            <td class="lookbook-editor__col-num"><span data-lookbook-row-number><?php echo esc_html((string) ($i + 1)); ?></span></td>
                            <td>
                                <label class="screen-reader-text" for="lookbook_x_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('X percentage', 'lookbook'); ?></label>
                                <input
                                    type="number"
                                    id="lookbook_x_<?php echo esc_attr((string) $i); ?>"
                                    name="lookbook_hotspots[<?php echo esc_attr((string) $i); ?>][x]"
                                    value="<?php echo esc_attr((string) $row['x']); ?>"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    class="small-text"
                                    data-lookbook-x
                                />
                            </td>
                            <td>
                                <label class="screen-reader-text" for="lookbook_y_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('Y percentage', 'lookbook'); ?></label>
                                <input
                                    type="number"
                                    id="lookbook_y_<?php echo esc_attr((string) $i); ?>"
                                    name="lookbook_hotspots[<?php echo esc_attr((string) $i); ?>][y]"
                                    value="<?php echo esc_attr((string) $row['y']); ?>"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    class="small-text"
                                    data-lookbook-y
                                />
                            </td>
                            <td>
                                <label class="screen-reader-text" for="lookbook_pid_<?php echo esc_attr((string) $i); ?>"><?php esc_html_e('Product ID', 'lookbook'); ?></label>
                                <input
                                    type="number"
                                    id="lookbook_pid_<?php echo esc_attr((string) $i); ?>"
                                    name="lookbook_hotspots[<?php echo esc_attr((string) $i); ?>][product_id]"
                                    value="<?php echo esc_attr((string) ($row['product_id'] ?: '')); ?>"
                                    min="1"
                                    step="1"
                                    class="small-text"
                                    data-lookbook-pid
                                />
                            </td>
                            <td class="lookbook-editor__col-actions">
                                <button type="button" class="button-link lookbook-editor__remove" data-lookbook-remove>
                                    <?php esc_html_e('Remove', 'lookbook'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button lookbook-editor__add" data-lookbook-add>
                    <?php esc_html_e('+ Add hotspot', 'lookbook'); ?>
                </button>
        </p>
    </div>
</div>
