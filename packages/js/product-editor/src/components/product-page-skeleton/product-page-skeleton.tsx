/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export function ProductPageSkeleton() {
	return (
		<div className="woocommerce-product-page-skeleton" aria-hidden="true">
			<div className="woocommerce-product-page-skeleton__header">
				<div className="woocommerce-product-page-skeleton__header-row">
					<div />
					<div className="woocommerce-product-page-skeleton__header-title" />
					<div className="woocommerce-product-page-skeleton__header-actions">
						<div className="woocommerce-product-page-skeleton__header-actions-other" />
						<div className="woocommerce-product-page-skeleton__header-actions-main" />
						<div className="woocommerce-product-page-skeleton__header-actions-config" />
					</div>
				</div>

				<div className="woocommerce-product-page-skeleton__header-row">
					<div className="woocommerce-product-page-skeleton__tabs">
						{ Array( 7 )
							.fill( 0 )
							.map( ( _, index ) => (
								<div
									key={ index }
									className="woocommerce-product-page-skeleton__tab-item"
								/>
							) ) }
					</div>
				</div>
			</div>

			<div className="woocommerce-product-page-skeleton__body">
				<div className="woocommerce-product-page-skeleton__body-tabs-content">
					<div className="woocommerce-product-page-skeleton__block-title" />

					<div className="woocommerce-product-page-skeleton__block-label" />
					<div className="woocommerce-product-page-skeleton__block-input" />

					<div className="woocommerce-product-page-skeleton__block-label" />
					<div className="woocommerce-product-page-skeleton__block-textarea" />

					<div className="woocommerce-product-page-skeleton__block-label" />
					<div className="woocommerce-product-page-skeleton__block-textarea" />

					<div className="woocommerce-product-page-skeleton__block-separator" />

					<div className="woocommerce-product-page-skeleton__block-title" />

					<div className="woocommerce-product-page-skeleton__block-label" />
					<div className="woocommerce-product-page-skeleton__block-input" />

					<div className="woocommerce-product-page-skeleton__block-label" />
					<div className="woocommerce-product-page-skeleton__block-textarea" />

					<div className="woocommerce-product-page-skeleton__block-label" />
					<div className="woocommerce-product-page-skeleton__block-textarea" />
				</div>
			</div>
		</div>
	);
}
