/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useRowRemovalMotion } from '../use-row-removal-motion';

const Table = ( { keys }: { keys: string[] } ) => {
	const tableRef = useRef< HTMLTableElement >( null );
	useRowRemovalMotion( tableRef, keys );
	return (
		<div className="wp-block-woocommerce-cart">
			<table ref={ tableRef }>
				<tbody>
					{ keys.map( ( key ) => (
						<tr key={ key } data-cart-item-key={ key }>
							<td>{ key }</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
};

// A fade that never reports its end.
const stuckFade = {
	playState: 'running',
	effect: { getComputedTiming: () => ( { endTime: 200 } ) },
	finished: new Promise( () => undefined ),
};

const copies = ( container: HTMLElement ) =>
	container.querySelectorAll( '[data-wc-row-copy]' );

describe( 'useRowRemovalMotion', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		Element.prototype.getAnimations = function ( this: Element ) {
			return ( this.hasAttribute( 'data-wc-row-copy' )
				? [ stuckFade ]
				: [] ) as unknown as Animation[];
		};
	} );

	afterEach( () => {
		jest.useRealTimers();
		// @ts-expect-error Not in jsdom.
		delete Element.prototype.getAnimations;
	} );

	it( 'removes the copies of a removal that a second removal took over when the fades never end', () => {
		const { container, rerender } = render(
			<Table keys={ [ 'a', 'b', 'c' ] } />
		);
		rerender( <Table keys={ [ 'b', 'c' ] } /> );
		expect( copies( container ) ).toHaveLength( 1 );

		act( () => {
			jest.advanceTimersByTime( 500 );
		} );
		rerender( <Table keys={ [ 'c' ] } /> );
		expect( copies( container ) ).toHaveLength( 2 );

		act( () => {
			jest.advanceTimersByTime( 1000 );
		} );
		expect( copies( container ) ).toHaveLength( 0 );
	} );
} );
