/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useGetCountryStateAutofill } from '../store-address';

const AutofillWrapper = ( { options, value, onChange } ) => {
	const [ values, setValues ] = useState( { countryState: value || '' } );
	const setCountryState = useCallback( ( key, newValue ) => {
		setValues( {
			...values,
			[ key ]: newValue,
		} );
	}, [] );
	useEffect( () => {
		setValues( { countryState: value } );
	}, [ value ] );
	useEffect( () => {
		if ( onChange ) {
			onChange( values );
		}
	}, [ values ] );
	const countryStateAutofill = useGetCountryStateAutofill(
		options,
		values.countryState,
		setCountryState
	);

	return countryStateAutofill;
};

const DEFAULT_OPTIONS = [
	{ key: 'KH', label: 'Cambodia' },
	{ key: 'CM', label: 'Cameroon' },
	{ key: 'CA:AB', label: 'Canada — Alberta' },
	{ key: 'CA:BC', label: 'Canada — British Columbia' },
	{ key: 'CA:MB', label: 'Canada — Manitoba' },
	{ key: 'US:CA', label: 'United States - California' },
];
describe( 'useGetCountryStateAutofill', () => {
	it( 'should render a country and state inputs with autoComplete', () => {
		const { queryAllByRole } = render(
			<AutofillWrapper options={ [ ...DEFAULT_OPTIONS ] } />
		);
		const inputs = queryAllByRole( 'textbox' );

		expect( inputs.length ).toBe( 2 );
		expect( inputs[ 0 ].autocomplete ).toEqual( 'country' );
		expect( inputs[ 1 ].autocomplete ).toEqual( 'address-level1' );
	} );

	it( 'should set autocomplete fields if a value is selected', () => {
		const { queryAllByRole } = render(
			<AutofillWrapper options={ [ ...DEFAULT_OPTIONS ] } value="CA:MB" />
		);
		const inputs = queryAllByRole( 'textbox' );

		expect( inputs.length ).toBe( 2 );
		expect( inputs[ 0 ].value ).toEqual( 'Canada' );
		expect( inputs[ 1 ].value ).toEqual( 'Manitoba' );
	} );

	it( 'should select region by key if abbreviation is used', () => {
		const onChange = jest.fn();
		const { queryAllByRole } = render(
			<AutofillWrapper
				options={ [ ...DEFAULT_OPTIONS ] }
				onChange={ onChange }
			/>
		);
		const inputs = queryAllByRole( 'textbox' );
		fireEvent.change( inputs[ 0 ], { target: { value: 'United States' } } );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'CA' },
		} );
		expect( onChange ).toHaveBeenCalledWith( { countryState: 'US:CA' } );
	} );

	it( 'should update the value if the auto complete fields changed', () => {
		const onChange = jest.fn();
		const { queryAllByRole } = render(
			<AutofillWrapper
				options={ [ ...DEFAULT_OPTIONS ] }
				onChange={ onChange }
			/>
		);
		const inputs = queryAllByRole( 'textbox' );
		fireEvent.change( inputs[ 0 ], { target: { value: 'Canada' } } );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'British Columbia' },
		} );
		expect( onChange ).toHaveBeenCalledWith( { countryState: 'CA:BC' } );
	} );

	it( 'should update the value if the auto complete fields changed and value was already set', () => {
		const onChange = jest.fn();
		const { queryAllByRole } = render(
			<AutofillWrapper
				options={ [ ...DEFAULT_OPTIONS ] }
				onChange={ onChange }
				value="CM"
			/>
		);
		const inputs = queryAllByRole( 'textbox' );
		expect( inputs[ 0 ].value ).toEqual( 'Cameroon' );
		onChange.mockClear();
		fireEvent.change( inputs[ 0 ], { target: { value: 'Canada' } } );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'British Columbia' },
		} );
		expect( onChange ).toHaveBeenCalledWith( { countryState: 'CA:BC' } );
	} );

	it( 'should update the auto complete inputs when value changed and inputs already set', () => {
		const onChange = jest.fn();
		const options = [ ...DEFAULT_OPTIONS ];
		const { rerender, queryAllByRole } = render(
			<AutofillWrapper options={ options } onChange={ onChange } />
		);
		let inputs = queryAllByRole( 'textbox' );
		fireEvent.change( inputs[ 0 ], { target: { value: 'Canada' } } );
		fireEvent.change( inputs[ 1 ], {
			target: { value: 'British Columbia' },
		} );
		expect( onChange ).toHaveBeenCalledWith( { countryState: 'CA:BC' } );
		rerender(
			<AutofillWrapper
				options={ options }
				onChange={ onChange }
				value="KH"
			/>
		);
		inputs = queryAllByRole( 'textbox' );
		expect( inputs[ 0 ].value ).toEqual( 'Cambodia' );
		expect( inputs[ 1 ].value ).toEqual( '' );
	} );
} );
