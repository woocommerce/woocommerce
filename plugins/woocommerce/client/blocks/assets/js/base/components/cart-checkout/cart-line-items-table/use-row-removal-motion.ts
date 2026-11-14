/**
 * External dependencies
 */
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import {
	inertCopy,
	whenAnimationsEnd,
	whenAllAnimationsEnd,
} from '@woocommerce/base-utils';
import type { MouseEvent, RefObject } from 'react';

// The styles in `style.scss` use these attributes.
const MOVING = 'data-wc-row-removal';
const COPY = 'data-wc-row-copy';

// The fade takes 200ms and the slide 300ms. After this time, the motion ends even if the browser never reports the end
// of an animation.
const SAFETY_TIMEOUT = 1000;

const ROW = 'tr[data-cart-item-key]';
const ROWS = `:scope > tbody > ${ ROW }`;

type RemovedRow = {
	row: HTMLTableRowElement;
	rect: DOMRect;
	cellWidths: number[];
};

type Snapshot = {
	removed: RemovedRow[];
	// Where the rows that stay are on the screen, including a slide that still runs.
	tops: Map< string, number >;
	height: number;
};

// Only the Cart block animates. The Mini Cart shares this table, and changes immediately.
const canAnimate = (
	table: HTMLTableElement | null
): table is HTMLTableElement =>
	typeof table?.getAnimations === 'function' &&
	table.closest( '.wp-block-woocommerce-cart' ) !== null;

const keyOf = ( row: HTMLTableRowElement ) => row.dataset.cartItemKey as string;

const clearStyle = ( element: HTMLElement, property: string ) => {
	element.style.removeProperty( property );
	if ( element.style.length === 0 ) {
		element.removeAttribute( 'style' );
	}
};

const measure = (
	table: HTMLTableElement,
	keys: string[]
): Snapshot | null => {
	const removed: RemovedRow[] = [];
	const tops = new Map< string, number >();
	table.querySelectorAll< HTMLTableRowElement >( ROWS ).forEach( ( row ) => {
		if ( keys.includes( keyOf( row ) ) ) {
			tops.set( keyOf( row ), row.getBoundingClientRect().top );
			return;
		}
		removed.push( {
			row,
			rect: row.getBoundingClientRect(),
			cellWidths: Array.from(
				row.cells,
				( cell ) => cell.getBoundingClientRect().width
			),
		} );
	} );
	return removed.length
		? { removed, tops, height: table.getBoundingClientRect().height }
		: null;
};

// An inert copy of the row, in its own table box laid over the place where the row was. Table rows can't be
// positioned, so the copy's body is the table, with the cell widths of the row.
const placeCopy = (
	table: HTMLTableElement,
	{ row, rect, cellWidths }: RemovedRow
) => {
	const copy = inertCopy( row ) as HTMLTableRowElement;
	// Without its key, the copy is not a row of the cart.
	copy.removeAttribute( 'data-cart-item-key' );
	Array.from( copy.cells ).forEach( ( cell, index ) => {
		cell.style.boxSizing = 'border-box';
		cell.style.width = `${ cellWidths[ index ] ?? 0 }px`;
	} );
	const body = document.createElement( 'tbody' );
	body.setAttribute( COPY, '' );
	body.setAttribute( 'aria-hidden', 'true' );
	Object.assign( body.style, {
		position: 'absolute',
		display: 'table',
		tableLayout: 'fixed',
		width: `${ rect.width }px`,
		top: '0',
		left: '0',
	} );
	body.append( copy );
	table.append( body );
	// At 0, the copy is at the corner of whichever element it is positioned against.
	const origin = body.getBoundingClientRect();
	body.style.top = `${ rect.top - origin.top }px`;
	body.style.left = `${ rect.left - origin.left }px`;
	return body;
};

/**
 * Copies of the removed rows fade out where they were. The rows below and the space of the table hold their place
 * until the copies have gone. Then the rows slide up, and the space at the bottom of the table closes in the real
 * layout, so the content below moves one time. With reduced motion, the copies only fade.
 *
 * @return A function that ends the motion at once. Pass `true` to also remove the copies.
 */
const playRemoval = (
	table: HTMLTableElement,
	{ removed, tops, height }: Snapshot
) => {
	let isStopped = false;
	const cancels: Array< () => void > = [];
	const copies = removed.map( ( item ) => placeCopy( table, item ) );
	// Not cancelled when a new removal takes over, so each copy finishes its fade.
	copies.forEach( ( copy ) =>
		whenAnimationsEnd( copy, () => copy.remove() )
	);

	// Padding is the space at the bottom of the table, but a table with collapsed borders has none.
	const space =
		getComputedStyle( table ).borderCollapse === 'collapse'
			? 'margin-bottom'
			: 'padding-bottom';
	const lost = height - table.getBoundingClientRect().height;
	// A new row has no old place, so its offset is NaN, and it does not move.
	const offsets = Array.from(
		table.querySelectorAll< HTMLTableRowElement >( ROWS ),
		( row ) => ( {
			row,
			offset:
				( tops.get( keyOf( row ) ) ?? NaN ) -
				row.getBoundingClientRect().top,
		} )
	).filter( ( { offset } ) => Math.abs( offset ) >= 1 );
	const moved = offsets.map( ( { row } ) => row );
	offsets.forEach( ( { row, offset } ) => {
		row.style.translate = `0 ${ offset }px`;
	} );
	if ( lost >= 1 ) {
		const current = getComputedStyle( table ).getPropertyValue( space );
		table.style.setProperty( space, `${ parseFloat( current ) + lost }px` );
	}

	const stop = ( removeCopies: boolean ) => {
		if ( isStopped ) {
			return;
		}
		isStopped = true;
		cancels.forEach( ( cancel ) => cancel() );
		table.removeAttribute( MOVING );
		clearStyle( table, space );
		moved.forEach( ( row ) => clearStyle( row, 'translate' ) );
		if ( removeCopies ) {
			copies.forEach( ( copy ) => copy.remove() );
		}
	};

	const release = () => {
		table.setAttribute( MOVING, '' );
		clearStyle( table, space );
		moved.forEach( ( row ) => clearStyle( row, 'translate' ) );
		cancels.push(
			whenAllAnimationsEnd( [ table, ...moved ], () => stop( false ) )
		);
	};

	cancels.push( whenAllAnimationsEnd( copies, release ) );
	const safety = window.setTimeout( () => stop( true ), SAFETY_TIMEOUT );
	cancels.push( () => window.clearTimeout( safety ) );
	return stop;
};

/**
 * Animates the rows that leave the cart table. The real rows unmount as before, and only inert copies animate. A row
 * that the shopper removed with the keyboard goes immediately.
 *
 * @param tableRef The cart table.
 * @param keys     The keys of the rows to render.
 * @return A click handler for the table, which notes how each row was last clicked.
 */
export const useRowRemovalMotion = (
	tableRef: RefObject< HTMLTableElement >,
	keys: string[]
) => {
	const keyList = keys.join();
	const lastKeyList = useRef( keyList );
	const snapshot = useRef< Snapshot | null >( null );
	const stop = useRef< ( ( removeCopies: boolean ) => void ) | null >( null );
	const keyboardKeys = useRef( new Set< string >() );

	// Measure while the removed rows are still in the page: React removes them before the layout effect runs.
	if ( lastKeyList.current !== keyList ) {
		lastKeyList.current = keyList;
		snapshot.current = canAnimate( tableRef.current )
			? measure( tableRef.current, keys )
			: null;
	}

	useLayoutEffect( () => {
		const table = tableRef.current;
		const removal = snapshot.current;
		snapshot.current = null;
		if ( ! removal || ! table ) {
			return;
		}
		const removedKeys = removal.removed.map( ( { row } ) => keyOf( row ) );
		const isKeyboard = removedKeys.some( ( key ) =>
			keyboardKeys.current.has( key )
		);
		removedKeys.forEach( ( key ) => keyboardKeys.current.delete( key ) );
		stop.current?.( isKeyboard );
		stop.current = isKeyboard ? null : playRemoval( table, removal );
	} );

	useEffect( () => () => stop.current?.( true ), [] );

	// A keyboard click has no click count.
	return ( event: MouseEvent ) => {
		const row =
			event.target instanceof Element
				? event.target.closest< HTMLTableRowElement >( ROW )
				: null;
		if ( ! row ) {
			return;
		}
		if ( event.detail === 0 ) {
			keyboardKeys.current.add( keyOf( row ) );
		} else {
			keyboardKeys.current.delete( keyOf( row ) );
		}
	};
};
