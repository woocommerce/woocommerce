/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

export function EmptyState(
	props: React.DetailedHTMLProps<
		React.HTMLAttributes< HTMLDivElement >,
		HTMLDivElement
	>
) {
	return (
		<div
			{ ...props }
			role="none"
			className="wp-block-woocommerce-product-variation-items-field__empty-state"
		>
			<div className="wp-block-woocommerce-product-variation-items-field__empty-state-row">
				<div>{ __( 'Variation', 'woocommerce' ) }</div>
				<div>
					<div className="wp-block-woocommerce-product-variation-items-field__empty-state-name" />
				</div>
				<div>
					<div className="wp-block-woocommerce-product-variation-items-field__empty-state-actions" />
				</div>
			</div>

			<div className="wp-block-woocommerce-product-variation-items-field__empty-state-row">
				<div>{ __( 'Colors', 'woocommerce' ) }</div>
				<div>
					<div className="wp-block-woocommerce-product-variation-items-field__empty-state-name" />
				</div>
				<div>
					<div className="wp-block-woocommerce-product-variation-items-field__empty-state-actions" />
				</div>
			</div>

			<div className="wp-block-woocommerce-product-variation-items-field__empty-state-row">
				<div>{ __( 'Sizes', 'woocommerce' ) }</div>
				<div>
					<div className="wp-block-woocommerce-product-variation-items-field__empty-state-name" />
				</div>
				<div>
					<div className="wp-block-woocommerce-product-variation-items-field__empty-state-actions" />
				</div>
			</div>
		</div>
	);
}
