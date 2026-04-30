/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Variation } from '../types';

export function VariantCell( { item }: { item: Variation } ) {
	const label = item.attributes.length
		? item.attributes.map( ( a ) => a.option ).join( ' · ' )
		: __( '(No attributes)', 'woocommerce' );

	return (
		<div className="wc-variations-classic__variant-cell">
			{ item.image?.src && (
				<img
					src={ item.image.src }
					alt={ item.image.alt || '' }
					className="wc-variations-classic__variant-image"
					width={ 36 }
					height={ 36 }
				/>
			) }
			<span className="wc-variations-classic__variant-label">
				{ label }
			</span>
		</div>
	);
}

export function PriceCell( { item }: { item: Variation } ) {
	if ( ! item.regular_price ) {
		return (
			<span className="wc-variations-classic__price wc-variations-classic__price--empty">
				—
			</span>
		);
	}

	const isOnSale = Boolean(
		item.sale_price && item.sale_price !== item.regular_price
	);

	return (
		<span className="wc-variations-classic__price">
			{ isOnSale && <del>{ item.regular_price }</del> }
			<ins>{ isOnSale ? item.sale_price : item.regular_price }</ins>
		</span>
	);
}

export function StockCell( { item }: { item: Variation } ) {
	const statusLabels: Record< string, string > = {
		instock: __( 'In stock', 'woocommerce' ),
		outofstock: __( 'Out of stock', 'woocommerce' ),
		onbackorder: __( 'On backorder', 'woocommerce' ),
	};

	const label = statusLabels[ item.stock_status ] || item.stock_status;
	const qty =
		item.manage_stock && item.stock_quantity !== null
			? ` (${ item.stock_quantity })`
			: '';

	return (
		<span
			className={ `wc-variations-classic__stock wc-variations-classic__stock--${ item.stock_status }` }
		>
			{ label }
			{ qty }
		</span>
	);
}
