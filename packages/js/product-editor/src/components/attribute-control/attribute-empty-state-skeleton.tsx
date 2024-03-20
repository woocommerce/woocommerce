/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import classNames from 'classnames';

export function AttributeEmptyStateSkeleton() {
	return (
		<div className="woocommerce-product-page-attribute-skeleton">
			{ Array( 3 )
				.fill( 0 )
				.map( ( _, index ) => (
					<div
						key={ index }
						className="woocommerce-product-page-attribute-skeleton__row"
					>
						<div
							className={ classNames(
								'woocommerce-product-page-attribute-skeleton__item'
							) }
						>
							<div
								className={ classNames(
									`woocommerce-product-page-attribute-skeleton__name${ index }`,
									`woocommerce-product-page-attribute-skeleton__row${ index }`
								) }
							></div>
						</div>
						<div className="woocommerce-product-page-attribute-skeleton__item">
							<div
								className={ classNames(
									`woocommerce-product-page-attribute-skeleton__value${ index }`,
									`woocommerce-product-page-attribute-skeleton__row${ index }`
								) }
							></div>
						</div>
						<div className="woocommerce-product-page-attribute-skeleton__last-item">
							<div
								className={ classNames(
									'woocommerce-product-page-attribute-skeleton__buttons',
									`woocommerce-product-page-attribute-skeleton__row${ index }`
								) }
							></div>
						</div>
					</div>
				) ) }
		</div>
	);
}
