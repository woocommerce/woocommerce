/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export function Skeleton() {
	return (
		<div
			role="table"
			aria-hidden="true"
			aria-label={ __( 'Loading linked products', 'woocommerce' ) }
		>
			<div role="rowgroup">
				<div role="rowheader">
					<div role="columnheader">
						<div className="skeleton" />
					</div>
					<div role="columnheader" />
				</div>
			</div>
			<div role="rowgroup">
				{ Array.from( { length: 3 } ).map( ( _, index ) => (
					<div role="row" key={ index }>
						<div role="cell">
							<div className="wp-block-woocommerce-product-linked-list-field__product-image skeleton" />
							<div className="wp-block-woocommerce-product-linked-list-field__product-info">
								<div className="wp-block-woocommerce-product-linked-list-field__product-name skeleton"></div>
								<div className="wp-block-woocommerce-product-linked-list-field__product-price skeleton" />
							</div>
						</div>
						<div
							role="cell"
							className="wp-block-woocommerce-product-linked-list-field__actions"
						>
							<div className="skeleton" />
							<div className="skeleton" />
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
}
