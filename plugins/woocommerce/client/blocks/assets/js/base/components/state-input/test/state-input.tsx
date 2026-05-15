/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, within } from '@testing-library/react';

/**
 * Mock @woocommerce/block-settings to expose a deterministic STATE_ORDER so
 * the test does not depend on the global wcSettings shape. We preserve all
 * other exports from the module so transitive dependencies (cart data store,
 * etc.) still find ADDRESS_FORM_KEYS and friends.
 */
jest.mock( '@woocommerce/block-settings', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/block-settings' ),
	STATE_ORDER: {
		// Insertion order differs from alphabetical order, and includes a
		// numeric-like key ("99") that JavaScript would otherwise reorder
		// numerically when iterating with Object.keys.
		XX: [ '99', 'CA', 'AL' ],
	},
} ) );

/**
 * Internal dependencies
 */
import StateInput from '../state-input';

describe( 'StateInput', () => {
	it( 'renders state options in the PHP-defined order, not the order JavaScript imposes on integer-like keys', () => {
		render(
			<StateInput
				id="state"
				label="State"
				country="XX"
				value=""
				onChange={ () => undefined }
				states={ {
					// Object literal: JS reorders integer-like keys first.
					// Without STATE_ORDER, the dropdown would render "99" first,
					// then "AL", "CA" — losing the merchant-defined order.
					XX: {
						AL: 'Army Last',
						CA: 'California',
						'99': 'Zone 99',
					},
				} }
			/>
		);

		const select = screen.getByLabelText( 'State' ) as HTMLSelectElement;
		const optionLabels = within( select )
			.getAllByRole( 'option' )
			.map( ( option ) => option.textContent );

		// The first option is the placeholder "Select a state". Subsequent
		// options should match the PHP-defined order from STATE_ORDER.
		expect( optionLabels.slice( 1 ) ).toEqual( [
			'Zone 99',
			'California',
			'Army Last',
		] );
	} );

	it( 'falls back to Object.keys when STATE_ORDER is not available for the country', () => {
		render(
			<StateInput
				id="state"
				label="State"
				country="ZZ"
				value=""
				onChange={ () => undefined }
				states={ {
					ZZ: {
						AA: 'Alpha',
						BB: 'Beta',
					},
				} }
			/>
		);

		const select = screen.getByLabelText( 'State' ) as HTMLSelectElement;
		const optionLabels = within( select )
			.getAllByRole( 'option' )
			.map( ( option ) => option.textContent );

		expect( optionLabels.slice( 1 ) ).toEqual( [ 'Alpha', 'Beta' ] );
	} );
} );
