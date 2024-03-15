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
			className="woocommerce-product-custom-fields__empty-state"
		>
			<div className="woocommerce-product-custom-fields__empty-state-row">
				<div>{ __( 'Custom field 1', 'woocommerce' ) }</div>
				<div>
					<div className="woocommerce-product-custom-fields__empty-state-name" />
				</div>
				<div>
					<div className="woocommerce-product-custom-fields__empty-state-actions" />
				</div>
			</div>

			<div className="woocommerce-product-custom-fields__empty-state-row">
				<div>{ __( 'Custom field 2', 'woocommerce' ) }</div>
				<div>
					<div className="woocommerce-product-custom-fields__empty-state-name" />
				</div>
				<div>
					<div className="woocommerce-product-custom-fields__empty-state-actions" />
				</div>
			</div>

			<div className="woocommerce-product-custom-fields__empty-state-row">
				<div>{ __( 'Custom field 3', 'woocommerce' ) }</div>
				<div>
					<div className="woocommerce-product-custom-fields__empty-state-name" />
				</div>
				<div>
					<div className="woocommerce-product-custom-fields__empty-state-actions" />
				</div>
			</div>
		</div>
	);
}
