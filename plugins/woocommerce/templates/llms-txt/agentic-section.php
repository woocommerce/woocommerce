<?php
/**
 * Store interfaces and policies for agents.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/llms-txt/agentic-section.php.
 *
 * @var string               $store_api_url Store API URL.
 * @var array                $ucp_profile   UCP profile, or an empty array.
 * @var string               $ucp_url       UCP profile URL.
 * @var string               $ucp_version   UCP version.
 * @var array<int, string>   $capabilities  Available UCP capabilities.
 * @var string               $mcp_url       Merchant MCP URL, or an empty string.
 * @var array<string, string> $policy_urls   Public policy URLs.
 *
 * @package WooCommerce\Templates
 * @version 11.4.0
 */

defined( 'ABSPATH' ) || exit;

$policy_labels = array(
	'terms'   => 'Terms and conditions',
	'returns' => 'Refund and returns',
	'privacy' => 'Privacy policy',
);
$checkout_note = isset( $ucp_profile['ucp']['capabilities']['dev.ucp.shopping.checkout'] ) ? '' : ' Checkout is completed on the store, not through UCP.';
?>
## Agentic capabilities

- [WooCommerce Store API](<?php echo esc_url_raw( $store_api_url ); ?>): Browse products and use the cart. See the [Store API documentation](https://developer.woocommerce.com/docs/apis/store-api/).
<?php if ( $ucp_profile ) : ?>
- [Universal Commerce Protocol (UCP) profile](<?php echo esc_url_raw( $ucp_url ); ?>): <?php echo implode( ', ', $capabilities ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML. ?>. Version <?php echo $ucp_version . '.' . $checkout_note . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML. ?>
<?php endif; ?>
<?php if ( '' !== $mcp_url ) : ?>
- [Merchant MCP](<?php echo esc_url_raw( $mcp_url ); ?>): For authorized store management; authentication is required.
<?php endif; ?>
<?php if ( $policy_urls ) : ?>

## Policies

<?php endif; ?>
<?php foreach ( $policy_urls as $key => $url ) : ?>
- [<?php echo $policy_labels[ $key ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text Markdown, not HTML. ?>](<?php echo esc_url_raw( $url ); ?>)
<?php endforeach; ?>
