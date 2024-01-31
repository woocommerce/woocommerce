/**
 * External dependencies
 */
import { createElement, Fragment, useContext } from '@wordpress/element';
import { CurrencyContext } from '@woocommerce/currency';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { FormattedPriceProps } from './types';

export function FormattedPrice( {
	product,
	className,
	...props
}: FormattedPriceProps ) {
	const { formatAmount } = useContext( CurrencyContext );

	return (
		<>
			{ ( Boolean( product.regular_price ) ||
				Boolean( product.price ) ) && (
				<span
					{ ...props }
					className={ classNames(
						'woocommerce-product-formatted-price',
						className
					) }
				>
					{ product.on_sale && (
						<span>
							{ product.sale_price
								? formatAmount( product.sale_price )
								: formatAmount( product.price ) }
						</span>
					) }

					{ product.regular_price && (
						<span
							className={ classNames( {
								'woocommerce-product-formatted-price--on-sale':
									product.on_sale,
							} ) }
						>
							{ formatAmount( product.regular_price ) }
						</span>
					) }
				</span>
			) }
		</>
	);
}
