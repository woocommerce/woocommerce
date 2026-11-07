<?php
/**
 * Store guidance for agents.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/llms-txt.php.
 *
 * @var string $site_name Store name.
 * @var string $tagline   Store tagline.
 * @var string $guidance  Store interfaces and policies.
 *
 * @package WooCommerce\Templates
 * @version 11.4.0
 */

defined( 'ABSPATH' ) || exit;
?>
# <?php echo $site_name . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML. ?>
<?php if ( '' !== $tagline ) : ?>
> <?php echo $tagline . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML. ?>
<?php endif; ?>
<?php echo $guidance; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML. ?>
