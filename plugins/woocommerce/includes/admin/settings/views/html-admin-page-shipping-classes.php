<?php
/**
 * Shipping classes admin
 *
 * @package WooCommerce\Admin\Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2 class="wc-shipping-zones-heading">
	<span><?php esc_html_e( 'Shipping classes', 'woocommerce' ); ?></span>
	<a class="page-title-action wc-shipping-class-add-new" href="#"><?php esc_html_e( 'Add shipping class', 'woocommerce' ); ?></a>
</h2>

<p class="wc-shipping-zone-help-text">
	<?php esc_html_e( 'Use shipping classes to customize the shipping rates for different groups of products, such as heavy items that require higher postage fees.', 'woocommerce' ); ?> <a target="_blank" href="https://woocommerce.com/document/product-shipping-classes/"><?php esc_html_e( 'Learn more', 'woocommerce' ); ?></a>
</p>

<table class="wc-shipping-classes widefat">
	<thead>
		<tr>
			<?php foreach ( $shipping_class_columns as $class => $heading ) : ?>
				<th class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $heading ); ?></th>
			<?php endforeach; ?>
			<th />
		</tr>
	</thead>
	<tbody class="wc-shipping-class-rows wc-shipping-tables-tbody"></tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-class-row-blank">
	<tr>
		<td class="wc-shipping-classes-blank-state" colspan="<?php echo absint( count( $shipping_class_columns ) + 1 ); ?>"><p><?php esc_html_e( 'No shipping classes have been created.', 'woocommerce' ); ?></p></td>
	</tr>
</script>

<!-- 1. Placeholder becomes the "label" in view class div -->
<!-- 1. Add labelFor or some kind of attribute for semantic HTML-->

<script type="text/html" id="tmpl-wc-shipping-class-configure">
<div class="wc-backbone-modal wc-shipping-class-modal">
		<div class="wc-backbone-modal-content" data-id="{{ data.term_id }}">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Add shipping class', 'woocommerce' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
				<form action="" method="post">
					<input type="hidden" name="term_id" value="{{{ data.term_id }}}" />
					<?php
					foreach ( $shipping_class_columns as $class => $heading ) {
						echo '<div class="wc-shipping-class-modal-input ' . esc_attr( $class ) . '">';
						switch ( $class ) {
							case 'wc-shipping-class-name':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?> *
								</div>
								<div class="edit">
									<input type="text" name="name" data-attribute="name" value="{{ data.name }}" placeholder="<?php esc_attr_e( 'e.g. Heavy', 'woocommerce' ); ?>" />
								</div>
								<div class="wc-shipping-class-modal-help-text"><?php esc_html_e( 'Give your shipping class a name for easy identification', 'woocommerce' ); ?></div>
								<?php
								break;
							case 'wc-shipping-class-slug':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?>
								</div>
								<div class="edit">
									<input type="text" name="slug" data-attribute="slug" value="{{ data.slug }}" placeholder="<?php esc_attr_e( 'e.g. heavy-packages', 'woocommerce' ); ?>" />
								</div>
								<div class="wc-shipping-class-modal-help-text"><?php esc_html_e( 'Slug (unique identifier) can be left blank and auto-generated, or you can enter one', 'woocommerce' ); ?></div>
								<?php
								break;
							case 'wc-shipping-class-description':
								?>
								<div class="view">
									<?php echo esc_html( $heading ); ?> *
								</div>
								<div class="edit">
									<input type="text" name="description" data-attribute="description" value="{{ data.description }}" placeholder="<?php esc_attr_e( 'e.g. For heavy items requiring higher postage', 'woocommerce' ); ?>" />
								</div>
								<?php
								break;
							case 'wc-shipping-class-count':
								break;
							default:
								?>
								<div class="view wc-shipping-class-hide-sibling-view">
									<?php echo esc_html( $heading ); ?>
								</div>
								<?php
								// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
								do_action( 'woocommerce_shipping_classes_column_' . $class );
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

<script type="text/html" id="tmpl-wc-shipping-class-row">
	<tr data-id="{{ data.term_id }}">
		<?php
		foreach ( $shipping_class_columns as $class => $heading ) {
			echo '<td class="' . esc_attr( $class ) . '">';
			switch ( $class ) {
				case 'wc-shipping-class-name':
					?>
					<div class="view">
						{{ data.name }}
					</div>
					<?php
					break;
				case 'wc-shipping-class-slug':
					?>
					<div class="view">{{ data.slug }}</div>
					<?php
					break;
				case 'wc-shipping-class-description':
					?>
					<div class="view">{{ data.description }}</div>
					<?php
					break;
				case 'wc-shipping-class-count':
					?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&product_shipping_class=' ) ); ?>{{data.slug}}">{{ data.count }}</a>
					<?php
					break;
				default:
					// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					do_action( 'woocommerce_shipping_classes_column_' . $class );
					break;
			}
			echo '</td>';
		}
		?>
		<td class="wc-shipping-zone-actions">
			<div>
				<a class="wc-shipping-class-edit wc-shipping-zone-action-edit" href="#"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-class-delete wc-shipping-zone-actions"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a>
			</div>
		</td>
	</tr>
</script>
