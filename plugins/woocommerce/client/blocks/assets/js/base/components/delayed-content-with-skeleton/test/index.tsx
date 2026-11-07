/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { DelayedContentWithSkeleton } from '..';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useReducedMotion: () => false,
} ) );

const Item = ( { id, isLoading }: { id: string; isLoading: boolean } ) => (
	<DelayedContentWithSkeleton
		isLoading={ isLoading }
		skeleton={ <span>{ `${ id } skeleton` }</span> }
	>
		<span>{ `${ id } content` }</span>
	</DelayedContentWithSkeleton>
);

describe( 'DelayedContentWithSkeleton', () => {
	beforeEach( () => jest.useFakeTimers() );
	afterEach( () => jest.useRealTimers() );

	it( 'ends a skeleton that mounts partway through loading with the others', () => {
		const { rerender } = render( <Item id="subtotal" isLoading /> );

		act( () => jest.advanceTimersByTime( 100 ) );
		rerender(
			<>
				<Item id="subtotal" isLoading />
				<Item id="tax" isLoading />
			</>
		);
		rerender(
			<>
				<Item id="subtotal" isLoading={ false } />
				<Item id="tax" isLoading={ false } />
			</>
		);

		act( () => jest.advanceTimersByTime( 199 ) );
		expect( screen.getByText( 'subtotal skeleton' ) ).toBeInTheDocument();
		expect( screen.getByText( 'tax skeleton' ) ).toBeInTheDocument();

		act( () => jest.advanceTimersByTime( 1 ) );
		expect( screen.getByText( 'subtotal content' ) ).toBeInTheDocument();
		expect( screen.getByText( 'tax content' ) ).toBeInTheDocument();
	} );
} );
