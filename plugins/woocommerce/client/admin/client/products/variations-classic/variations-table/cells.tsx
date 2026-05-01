/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Stack } from '@wordpress/ui';
import { Tag } from '@woocommerce/components';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { CURRENCY } from '~/utils/admin-settings';
import type { Variation } from '../types';

const currency = CurrencyFactory( CURRENCY );

export function VariantCell( { item }: { item: Variation } ) {
	return (
		<Stack direction="row" gap="sm" align="center">
			{ item.image?.src && (
				<img
					src={ item.image.src }
					alt={ item.image.alt || '' }
					className="wc-variations-classic__variant-image"
					width={ 36 }
					height={ 36 }
				/>
			) }
			<span className="wc-variations-classic__variant-id">
				#{ item.id }
			</span>
		</Stack>
	);
}

export function ValuesCell( { item }: { item: Variation } ) {
	if ( ! item.attributes.length ) {
		return <span className="wc-variations-classic__values--empty">—</span>;
	}

	return (
		<Stack direction="row" gap="xs" justify="flex-start" wrap="wrap">
			{ item.attributes.map( ( attr ) => (
				<Tag
					key={ attr.id || attr.name }
					label={ attr.option }
					id={ attr.id || attr.name }
					screenReaderLabel={ attr.option }
				/>
			) ) }
		</Stack>
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
			{ isOnSale && (
				<del>{ currency.formatAmount( item.regular_price ) }</del>
			) }
			<ins>
				{ currency.formatAmount(
					isOnSale ? item.sale_price : item.regular_price
				) }
			</ins>
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
