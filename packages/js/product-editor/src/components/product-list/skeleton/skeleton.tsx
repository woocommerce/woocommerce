/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export function Skeleton() {
	return (
		<div
			aria-hidden="true"
			aria-label={ __( 'Loading linked products', 'woocommerce' ) }
			className="woocommerce-product-list"
		>
			<div role="table">
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
								<div className="woocommerce-product-list__product-image skeleton" />
								<div className="woocommerce-product-list__product-info">
									<div className="woocommerce-product-list__product-name skeleton"></div>
									<div className="woocommerce-product-list__product-price skeleton" />
								</div>
							</div>
							<div
								role="cell"
								className="woocommerce-product-list__actions"
							>
								<div className="skeleton" />
								<div className="skeleton" />
							</div>
						</div>
					) ) }
				</div>
			</div>
		</div>
	);
}
