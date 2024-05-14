jest.mock( '@wordpress/element', () => {
	const originalModule = jest.requireActual( '@wordpress/element' );
	return {
		...originalModule,
		useId: () => 1,
		useTransition: () => [ , ( cb: () => void ) => cb() ],
	};
} );

/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Combobox from '..';
import { OPTIONS } from './util';

describe( 'Combobox', () => {
	test( 'The search is rendered with the initial passed label as the search, and then clears on focus', async () => {
		const user = userEvent.setup();

		const { getByRole } = render(
			<Combobox
				options={ OPTIONS }
				value={ OPTIONS[ 0 ].value }
				label={ OPTIONS[ 0 ].label }
				onChange={ jest.fn() }
				errorId={ null }
			/>
		);

		const input = getByRole( 'combobox' );

		expect( input ).toHaveValue( OPTIONS[ 0 ].label );

		// Manually calling act is still required due to the useTransition hook.
		await act( () => user.click( input ) );

		expect( input ).toHaveValue( '' );
	} );

	test( 'Typing a search will show a list of matching search results', async () => {
		const user = userEvent.setup();

		const { getByRole, getAllByRole } = render(
			<Combobox
				options={ OPTIONS }
				value={ OPTIONS[ 0 ].value }
				label={ OPTIONS[ 0 ].label }
				onChange={ jest.fn() }
				errorId={ null }
			/>
		);

		const input = getByRole( 'combobox' );
		await act( () => user.type( input, 'Bou' ) );

		const options = getAllByRole( 'option' );
		expect( options ).toHaveLength( 4 );

		expect( options[ 0 ] ).toHaveTextContent( 'Bouira' );
		expect( options[ 1 ] ).toHaveTextContent( 'Boumerdès' );
		expect( options[ 2 ] ).toHaveTextContent( 'Bordj Bou Arréridj' );
		expect( options[ 3 ] ).toHaveTextContent( 'Oum El Bouaghi' );
	} );

	test( 'Typing a search that is an exact match will clear all results and set the value', async () => {
		const user = userEvent.setup();

		const onChange = jest.fn();

		const { getByRole, queryAllByRole } = render(
			<Combobox
				options={ OPTIONS }
				value={ OPTIONS[ 0 ].value }
				label={ OPTIONS[ 0 ].label }
				onChange={ onChange }
				errorId={ null }
			/>
		);

		const input = getByRole( 'combobox' );
		await act( () => user.type( input, 'Bouira' ) );

		const options = queryAllByRole( 'option' );
		expect( options ).toHaveLength( 0 );

		expect( onChange ).toHaveBeenCalledWith( 'DZ-10' );
	} );
} );
