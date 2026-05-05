/**
 * External dependencies
 */
import { Badge, Stack } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { VariationEntityRecord } from './types';

const GAP_PX = 4;

export function VariationOptionsCell( {
	item,
}: {
	item: VariationEntityRecord;
} ) {
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

			const allWidth = widths.reduce(
				( sum, width, index ) =>
					sum + width + ( index > 0 ? GAP_PX : 0 ),
				0
			);
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
			<span className="woocommerce-variation-view__variation-options--empty">
				{ '\u2014' }
			</span>
		);
	}

	const visible = item.attributes.slice( 0, visibleCount );
	const hiddenCount = total - visibleCount;

	return (
		<div className="woocommerce-variation-view__variation-options-cell">
			<div
				className="woocommerce-variation-view__variation-options-measure"
				aria-hidden="true"
			>
				{ item.attributes.map( ( attr, i ) => (
					<Badge
						key={ `m-${ attr.id || attr.name }` }
						intent="none"
						ref={ ( el: HTMLSpanElement | null ) => {
							measureRefs.current[ i ] = el;
						} }
					>
						{ attr.option }
					</Badge>
				) ) }
				<Badge
					intent="none"
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
				className="woocommerce-variation-view__variation-options-row"
			>
				{ visible.map( ( attr ) => (
					<Badge key={ attr.id || attr.name } intent="none">
						{ attr.option }
					</Badge>
				) ) }
				{ hiddenCount > 0 && (
					<Badge intent="none">{ `+${ hiddenCount }` }</Badge>
				) }
			</Stack>
		</div>
	);
}

export const variationOptionsField: Field< VariationEntityRecord > = {
	id: 'variation_options',
	label: __( 'Options', 'woocommerce' ),
	getValue: ( { item } ) =>
		item.attributes.map( ( attr ) => attr.option ).join( ' ' ),
	render: ( { item } ) => <VariationOptionsCell item={ item } />,
	enableSorting: false,
	enableHiding: false,
	enableGlobalSearch: true,
};
