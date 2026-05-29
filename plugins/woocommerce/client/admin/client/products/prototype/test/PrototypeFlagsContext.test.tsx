/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useContext } from 'react';

/**
 * Internal dependencies
 */
import {
	PrototypeFlagsContext,
	PrototypeFlagsProvider,
} from '../PrototypeFlagsContext';

function TestConsumer() {
	const { flags, flagDefinitions, toggleFlag } = useContext(
		PrototypeFlagsContext
	);
	return (
		<div>
			<span data-testid="flag-count">{ flagDefinitions.length }</span>
			{ flagDefinitions.map( ( { key, label } ) => (
				<div key={ key }>
					<span data-testid={ `label-${ key }` }>{ label }</span>
					<span data-testid={ `value-${ key }` }>
						{ String( flags[ key ] ) }
					</span>
					<button
						onClick={ () => toggleFlag( key ) }
						data-testid={ `toggle-${ key }` }
					>
						toggle
					</button>
				</div>
			) ) }
		</div>
	);
}

describe( 'PrototypeFlagsProvider', () => {
	beforeEach( () => {
		localStorage.clear();
	} );

	it( 'provides flag definitions and default values', () => {
		render(
			<PrototypeFlagsProvider>
				<TestConsumer />
			</PrototypeFlagsProvider>
		);

		// At least one flag definition exists
		expect(
			parseInt(
				screen.getByTestId( 'flag-count' ).textContent || '0',
				10
			)
		).toBeGreaterThanOrEqual( 1 );
	} );

	it( 'applies defaultValue: false when localStorage is empty', () => {
		render(
			<PrototypeFlagsProvider>
				<TestConsumer />
			</PrototypeFlagsProvider>
		);

		// exampleFeature defaults to false
		expect( screen.getByTestId( 'value-exampleFeature' ).textContent ).toBe(
			'false'
		);
	} );

	it( 'toggleFlag flips the flag value', async () => {
		render(
			<PrototypeFlagsProvider>
				<TestConsumer />
			</PrototypeFlagsProvider>
		);

		await userEvent.click( screen.getByTestId( 'toggle-exampleFeature' ) );

		expect( screen.getByTestId( 'value-exampleFeature' ).textContent ).toBe(
			'true'
		);
	} );

	it( 'persists toggled value to localStorage', async () => {
		render(
			<PrototypeFlagsProvider>
				<TestConsumer />
			</PrototypeFlagsProvider>
		);

		await userEvent.click( screen.getByTestId( 'toggle-exampleFeature' ) );

		const stored = JSON.parse(
			localStorage.getItem( 'wc_prototype_flags' ) || '{}'
		);
		expect( stored.exampleFeature ).toBe( true );
	} );

	it( 'reads initial state from localStorage', () => {
		localStorage.setItem(
			'wc_prototype_flags',
			JSON.stringify( { exampleFeature: true } )
		);

		render(
			<PrototypeFlagsProvider>
				<TestConsumer />
			</PrototypeFlagsProvider>
		);

		expect( screen.getByTestId( 'value-exampleFeature' ).textContent ).toBe(
			'true'
		);
	} );

	it( 'falls back to defaults when localStorage contains invalid JSON', () => {
		localStorage.setItem( 'wc_prototype_flags', 'not-json' );

		render(
			<PrototypeFlagsProvider>
				<TestConsumer />
			</PrototypeFlagsProvider>
		);

		expect( screen.getByTestId( 'value-exampleFeature' ).textContent ).toBe(
			'false'
		);
	} );
} );
