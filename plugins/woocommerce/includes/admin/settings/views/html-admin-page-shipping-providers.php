<?php
/**
 * Shipping providers admin
 *
 * @package WooCommerce\Admin\Shipping
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2 class="wc-shipping-zones-heading">
	<span><?php esc_html_e( 'Shipping providers', 'woocommerce' ); ?></span>
	<a class="page-title-action wc-shipping-provider-add-new" href="#"><?php esc_html_e( 'Add shipping provider', 'woocommerce' ); ?></a>
</h2>

<p class="wc-shipping-zone-help-text">
	<?php esc_html_e( 'Add custom shipping providers so they appear in the fulfillment form when creating shipments. Use the tracking URL template to auto-generate tracking links.', 'woocommerce' ); ?>
</p>

<table class="wc-shipping-classes widefat">
	<thead>
		<tr>
			<?php foreach ( $shipping_provider_columns as $class => $heading ) : // @phpstan-ignore variable.undefined ?>
				<th class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $heading ); ?></th>
			<?php endforeach; ?>
			<th />
		</tr>
	</thead>
	<tbody class="wc-shipping-provider-rows wc-shipping-tables-tbody"></tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-provider-row-blank">
	<tr>
		<td class="wc-shipping-classes-blank-state" colspan="<?php echo absint( count( $shipping_provider_columns ) + 1 ); ?>"><p><?php esc_html_e( 'No custom shipping providers have been created.', 'woocommerce' ); ?></p></td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-provider-configure">
<div class="wc-backbone-modal wc-shipping-class-modal">
		<div class="wc-backbone-modal-content" data-id="{{ data.term_id }}">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Add shipping provider', 'woocommerce' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
				<form action="" method="post">
					<input type="hidden" name="term_id" value="{{{ data.term_id }}}" />
					<?php
					foreach ( $shipping_provider_columns as $class => $heading ) {
						echo '<div class="wc-shipping-class-modal-input ' . esc_attr( $class ) . '">';
						switch ( $class ) {
							case 'wc-shipping-provider-name':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?> *
								</div>
								<div class="edit">
									<input type="text" name="name" data-attribute="name" value="{{ data.name }}" placeholder="<?php esc_attr_e( 'e.g. My Local Courier', 'woocommerce' ); ?>" />
								</div>
								<div class="wc-shipping-class-modal-help-text"><?php esc_html_e( 'The display name for this shipping provider.', 'woocommerce' ); ?></div>
								<?php
								break;
							case 'wc-shipping-provider-slug':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?>
								</div>
								<div class="edit">
									<input type="text" name="slug" data-attribute="slug" value="{{ data.slug }}" placeholder="<?php esc_attr_e( 'e.g. my-local-courier', 'woocommerce' ); ?>" />
								</div>
								<div class="wc-shipping-class-modal-help-text"><?php esc_html_e( 'Unique identifier (auto-generated if left blank).', 'woocommerce' ); ?></div>
								<?php
								break;
							case 'wc-shipping-provider-tracking-url-template':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?>
								</div>
								<div class="edit">
									<input type="text" name="tracking_url_template" data-attribute="tracking_url_template" value="{{ data.tracking_url_template }}" placeholder="<?php esc_attr_e( 'e.g. https://example.com/track?id=__PLACEHOLDER__', 'woocommerce' ); ?>" />
								</div>
								<div class="wc-shipping-class-modal-help-text"><?php esc_html_e( 'Use __PLACEHOLDER__ where the tracking number should appear in the URL.', 'woocommerce' ); ?></div>
								<?php
								break;
							case 'wc-shipping-provider-icon':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?>
								</div>
								<div class="edit">
									<input type="text" name="icon" data-attribute="icon" value="{{ data.icon }}" placeholder="<?php esc_attr_e( 'e.g. https://example.com/icon.png', 'woocommerce' ); ?>" />
								</div>
								<div class="wc-shipping-class-modal-help-text"><?php esc_html_e( 'Optional URL for the provider icon.', 'woocommerce' ); ?></div>
								<?php
								break;
							default:
								?>
								<div class="view wc-shipping-class-hide-sibling-view">
									<?php echo esc_html( $heading ); ?>
								</div>
								<?php
								/**
								 * Fires for custom columns in the shipping providers configure modal.
								 *
								 * @since 10.7.0
								 */
								do_action( 'woocommerce_shipping_providers_column_' . $class );
								break;
						}
						echo '</div>';
					}
					?>
				</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" disabled class="button button-primary button-large disabled">
							<div class="wc-backbone-modal-action-{{ data.action === 'create' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Create', 'woocommerce' ); ?></div>
							<div class="wc-backbone-modal-action-{{ data.action === 'edit' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Save', 'woocommerce' ); ?></div>
						</button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/html" id="tmpl-wc-shipping-provider-row">
	<tr data-id="{{ data.term_id }}">
		<?php
		foreach ( $shipping_provider_columns as $class => $heading ) {
			echo '<td class="' . esc_attr( $class ) . '">';
			switch ( $class ) {
				case 'wc-shipping-provider-name':
					?>
					<div class="view">
						{{ data.name }}
					</div>
					<?php
					break;
				case 'wc-shipping-provider-slug':
					?>
					<div class="view">{{ data.slug }}</div>
					<?php
					break;
				case 'wc-shipping-provider-tracking-url-template':
					?>
					<div class="view">{{ data.tracking_url_template }}</div>
					<?php
					break;
				case 'wc-shipping-provider-icon':
					?>
					<div class="view">{{ data.icon }}</div>
					<?php
					break;
				default:
					/**
					 * Fires for custom columns in the shipping providers table row.
					 *
					 * @since 10.7.0
					 */
					do_action( 'woocommerce_shipping_providers_column_' . $class );
					break;
			}
			echo '</td>';
		}
		?>
		<td class="wc-shipping-zone-actions">
			<div>
				<a class="wc-shipping-provider-edit wc-shipping-zone-action-edit" href="#"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-provider-delete wc-shipping-zone-actions"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a>
			</div>
		</td>
	</tr>
</script>
