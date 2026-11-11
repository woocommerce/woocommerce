/**
 * External dependencies
 */
import { useLayoutEffect, useRef } from '@wordpress/element';
import {
	animateHeightChange,
	fadeAway,
	slideFrom,
} from '@woocommerce/base-utils/collapse';
import type { RefObject } from 'react';

type RowSnapshot = {
	row: HTMLTableRowElement;
	top: number;
	left: number;
	cellWidths: number[];
};
type Snapshot = {
	rows: Map< string, RowSnapshot >;
	// Page positions of what moves up when a row goes: the rows, the blocks after the table, and the totals below
	// it on narrow screens.
	tops: Map< Element, number >;
	tableWidth: number;
	cartHeight: number;
};

const pageTop = ( element: Element ) =>
	element.getBoundingClientRect().top + window.scrollY;

const takeSnapshot = (
	table: HTMLTableElement,
	cart: Element | null
): Snapshot => {
	const rows = new Map< string, RowSnapshot >();
	const tops = new Map< Element, number >();
	table
		.querySelectorAll< HTMLTableRowElement >(
			':scope > tbody > tr[data-cart-item-key]'
		)
		.forEach( ( row ) => {
			const rect = row.getBoundingClientRect();
			rows.set( row.dataset.cartItemKey as string, {
				row,
				top: rect.top + window.scrollY,
				left: rect.left + window.scrollX,
				cellWidths: Array.from( row.cells ).map(
					( cell ) => cell.getBoundingClientRect().width
				),
			} );
			tops.set( row, rect.top + window.scrollY );
		} );
	cart?.querySelectorAll(
		'.wp-block-woocommerce-cart-items-block > :not(table), .wp-block-woocommerce-cart-totals-block'
	).forEach( ( element ) => tops.set( element, pageTop( element ) ) );
	return {
		rows,
		tops,
		tableWidth: table.getBoundingClientRect().width,
		cartHeight: cart?.getBoundingClientRect().height ?? 0,
	};
};

// A copy of the removed row, in a copy of the table laid over where it was, fades out as the rows below slide up.
const fadeOutCopy = (
	table: HTMLTableElement,
	{ row, top, left, cellWidths }: RowSnapshot,
	tableWidth: number
) => {
	const shell = table.cloneNode( false ) as HTMLTableElement;
	const body = document.createElement( 'tbody' );
	const copy = row.cloneNode( true ) as HTMLTableRowElement;
	copy.removeAttribute( 'data-cart-item-key' );
	copy.querySelectorAll( '[id]' ).forEach( ( node ) =>
		node.removeAttribute( 'id' )
	);
	Array.from( copy.cells ).forEach( ( cell, index ) => {
		cell.style.width = `${ cellWidths[ index ] ?? 0 }px`;
	} );
	body.append( copy );
	shell.append( body );
	shell.removeAttribute( 'tabindex' );
	shell.setAttribute( 'aria-hidden', 'true' );
	shell.inert = true;
	Object.assign( shell.style, {
		position: 'absolute',
		margin: '0',
		width: `${ tableWidth }px`,
		tableLayout: 'fixed',
		pointerEvents: 'none',
	} );
	table.after( shell );
	// Positioned against its containing block: the nearest positioned ancestor, or the page.
	const container = shell.offsetParent;
	let originTop = 0;
	let originLeft = 0;
	if (
		container instanceof HTMLElement &&
		getComputedStyle( container ).position !== 'static'
	) {
		const rect = container.getBoundingClientRect();
		originTop =
			rect.top +
			window.scrollY +
			container.clientTop -
			container.scrollTop;
		originLeft =
			rect.left +
			window.scrollX +
			container.clientLeft -
			container.scrollLeft;
	}
	shell.style.top = `${ top - originTop }px`;
	shell.style.left = `${ left - originLeft }px`;
	const animation = fadeAway( copy );
	if ( ! animation ) {
		shell.remove();
		return;
	}
	animation.finished.then(
		() => shell.remove(),
		() => shell.remove()
	);
};

/**
 * When rows leave the cart table, a copy of each fades out where it was while the rows and blocks after it slide up
 * into its space (each starts where it was and moves to where it is now), and the cart's height eases down so the
 * page below follows. Under reduced motion, the copy only fades.
 */
export const useRowRemovalMotion = (
	tableRef: RefObject< HTMLTableElement >,
	keys: string[]
) => {
	const snapshot = useRef< Snapshot | null >( null );

	useLayoutEffect( () => {
		const table = tableRef.current;
		if ( ! table ) {
			return;
		}
		const cart = table.closest( '.wp-block-woocommerce-cart' );
		const previous = snapshot.current;
		const removed = previous
			? [ ...previous.rows.keys() ].filter(
					( key ) => ! keys.includes( key )
				)
			: [];
		if ( previous && removed.length ) {
			removed.forEach( ( key ) =>
				fadeOutCopy(
					table,
					previous.rows.get( key ) as RowSnapshot,
					previous.tableWidth
				)
			);
			previous.tops.forEach( ( top, element ) => {
				if ( element.isConnected ) {
					slideFrom( element, top - pageTop( element ) );
				}
			} );
			animateHeightChange( cart, previous.cartHeight );
		}
		snapshot.current = takeSnapshot( table, cart );
	} );
};
