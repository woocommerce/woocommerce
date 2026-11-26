/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { NoticeType } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import SnackbarList from '../index';

const notice = {
	id: 'added',
	content: 'Item added to the cart.',
	status: 'success',
} as NoticeType;

// An exit that never reports its end.
const stuckExit = {
	playState: 'running',
	effect: { getComputedTiming: () => ( { endTime: 200 } ) },
	finished: new Promise( () => undefined ),
};

const snackbars = ( container: HTMLElement ) =>
	container.querySelectorAll( '.wc-block-components-notice-snackbar' );

describe( 'SnackbarList', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		Element.prototype.getAnimations = function ( this: Element ) {
			return ( this.classList.contains( 'notice-transition-exit' )
				? [ stuckExit ]
				: [] ) as unknown as Animation[];
		};
	} );

	afterEach( () => {
		jest.useRealTimers();
		// @ts-expect-error Not in jsdom.
		delete Element.prototype.getAnimations;
	} );

	it( 'keeps a snackbar that the timeout removes until its exit ends', () => {
		const { container, rerender } = render(
			<SnackbarList notices={ [ notice ] } onRemove={ jest.fn() } />
		);
		rerender( <SnackbarList notices={ [] } onRemove={ jest.fn() } /> );

		expect( snackbars( container ) ).toHaveLength( 1 );
	} );

	it( 'removes a snackbar that the shopper dismisses at once', () => {
		const onRemove = jest.fn();
		const { container, rerender } = render(
			<SnackbarList notices={ [ notice ] } onRemove={ onRemove } />
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Dismiss this notice' } )
		);
		expect( onRemove ).toHaveBeenCalledWith( notice.id );
		rerender( <SnackbarList notices={ [] } onRemove={ onRemove } /> );

		expect( snackbars( container ) ).toHaveLength( 0 );
	} );
} );
