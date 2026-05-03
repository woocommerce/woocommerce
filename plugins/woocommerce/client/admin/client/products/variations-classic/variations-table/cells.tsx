/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { Badge, Stack } from '@wordpress/ui';
import CurrencyFactory from '@woocommerce/currency';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { CURRENCY } from '~/utils/admin-settings';
import type { Variation } from '../types';

const currency = CurrencyFactory( CURRENCY );

export function VariantCell( { item }: { item: Variation } ) {
	const placeholderSrc = getSetting< string >( 'placeholderImgSrc', '' );
	const src = item.image?.src || placeholderSrc;

	return (
		<Stack direction="row" gap="sm" align="center">
			{ src && (
				<img
					src={ src }
					alt={ item.image?.alt || '' }
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

// Stack gap="xs" resolves to 4px in the WordPress Design System token scale.
const GAP_PX = 4;

export function VariationOptionsCell( { item }: { item: Variation } ) {
	const total = item.attributes.length;
	const containerRef = useRef< HTMLDivElement >( null );
	const measureRefs = useRef< Array< HTMLSpanElement | null > >( [] );
	const overflowMeasureRef = useRef< HTMLSpanElement | null >( null );
	const [ visibleCount, setVisibleCount ] = useState( total );

	useLayoutEffect( () => {
		const container = containerRef.current;
		if ( ! container || total === 0 ) {
			return;
		}

		const recalc = () => {
			const containerWidth = container.clientWidth;
			const widths = measureRefs.current.map(
				( el ) => el?.offsetWidth ?? 0
			);
			const overflowWidth = overflowMeasureRef.current?.offsetWidth ?? 0;

			let allWidth = 0;
			widths.forEach( ( w, i ) => {
				allWidth += w + ( i > 0 ? GAP_PX : 0 );
			} );
			if ( allWidth <= containerWidth ) {
				setVisibleCount( total );
				return;
			}

			let used = 0;
			let count = 0;
			for ( let i = 0; i < widths.length; i++ ) {
				const add = widths[ i ] + ( i > 0 ? GAP_PX : 0 );
				const remaining = widths.length - i - 1;
				const reserve = remaining > 0 ? GAP_PX + overflowWidth : 0;
				if ( used + add + reserve <= containerWidth ) {
					used += add;
					count++;
				} else {
					break;
				}
			}
			setVisibleCount( Math.max( count, 0 ) );
		};

		recalc();
		const observer = new ResizeObserver( recalc );
		observer.observe( container );
		return () => observer.disconnect();
	}, [ total, item.attributes ] );

	if ( total === 0 ) {
		return (
			<span className="wc-variations-classic__variation-options--empty">
				—
			</span>
		);
	}

	const visible = item.attributes.slice( 0, visibleCount );
	const hiddenCount = total - visibleCount;

	return (
		<div className="wc-variations-classic__variation-options-cell">
			{ /* Off-screen measurement layer used to capture each badge's natural width. */ }
			<div
				className="wc-variations-classic__variation-options-measure"
				aria-hidden="true"
			>
				{ item.attributes.map( ( attr, i ) => (
					<Badge
						key={ `m-${ attr.id || attr.name }` }
						intent="draft"
						ref={ ( el: HTMLSpanElement | null ) => {
							measureRefs.current[ i ] = el;
						} }
					>
						{ attr.option }
					</Badge>
				) ) }
				<Badge
					intent="draft"
					ref={ ( el: HTMLSpanElement | null ) => {
						overflowMeasureRef.current = el;
					} }
				>
					{ `+${ total }` }
				</Badge>
			</div>
			<Stack
				ref={ containerRef }
				direction="row"
				gap="xs"
				align="center"
				justify="flex-start"
				className="wc-variations-classic__variation-options-row"
			>
				{ visible.map( ( attr ) => (
					<Badge key={ attr.id || attr.name } intent="draft">
						{ attr.option }
					</Badge>
				) ) }
				{ hiddenCount > 0 && (
					<Badge intent="draft">{ `+${ hiddenCount }` }</Badge>
				) }
			</Stack>
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
