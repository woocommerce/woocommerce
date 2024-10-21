/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	useGetCountryStateAutofill,
	getStateFilter,
	StoreAddress,
} from '../store-address';

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
	{ key: 'US:AR', label: 'United States (US) — Arkansas' },
	{ key: 'US:KS', label: 'United States (US) — Kansas' },
	{ key: 'CN:CN2', label: 'China — Beijing / 北京' },
	{ key: 'IR:THR', label: 'Iran — Tehran (تهران)' },
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

	it( 'should set countryState value if a value is provided', () => {
		const onChange = jest.fn();
		render(
			<AutofillWrapper
				options={ [ ...DEFAULT_OPTIONS ] }
				value="US:KS"
				onChange={ onChange }
			/>
		);

		// check the most recent call values
		expect( onChange.mock.calls.pop() ).toEqual( [
			{ countryState: 'US:KS' },
		] );
	} );

	it( 'should set autocomplete fields if the countryState is not empty', () => {
		const { queryAllByRole } = render(
			<AutofillWrapper options={ [ ...DEFAULT_OPTIONS ] } value="CA:MB" />
		);
		const inputs = queryAllByRole( 'textbox' );

		expect( inputs.length ).toBe( 2 );
		expect( inputs[ 0 ].value ).toEqual( 'Canada' );
		expect( inputs[ 1 ].value ).toEqual( 'Manitoba' );
	} );

	it( 'should set countryState if auto complete fields are changed and abbreviation is used', () => {
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

	it( 'should set countryState if auto complete fields are changed and abbreviation is not used', () => {
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

	it( 'should update the countryState if the auto complete fields changed and countryState was already set', () => {
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

	it( 'should update the auto complete fields when countryState is changed and inputs already set', () => {
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

describe( 'getStateFilter', () => {
	test.each( [
		{
			isStateAbbreviation: false,
			normalizedAutofillState: 'britishcolumbia',
			expected: { key: 'CA:BC', label: 'Canada — British Columbia' },
		},
		{
			isStateAbbreviation: true,
			normalizedAutofillState: 'ks',
			expected: {
				key: 'US:KS',
				label: 'United States (US) — Kansas',
			},
		},
		{
			isStateAbbreviation: false,
			normalizedAutofillState: '北京',
			expected: { key: 'CN:CN2', label: 'China — Beijing / 北京' },
		},
		{
			isStateAbbreviation: false,
			normalizedAutofillState: 'beijing',
			expected: { key: 'CN:CN2', label: 'China — Beijing / 北京' },
		},
		{
			isStateAbbreviation: false,
			normalizedAutofillState: 'تهران',
			expected: { key: 'IR:THR', label: 'Iran — Tehran (تهران)' },
		},
		{
			isStateAbbreviation: false,
			normalizedAutofillState: 'tehran',
			expected: { key: 'IR:THR', label: 'Iran — Tehran (تهران)' },
		},
	] )(
		'should filter state matches with isStateAbbreviation=$isStateAbbreviation and normalizedAutofillState=$normalizedAutofillState',
		( { isStateAbbreviation, normalizedAutofillState, expected } ) => {
			expect(
				DEFAULT_OPTIONS.filter(
					getStateFilter(
						isStateAbbreviation,
						normalizedAutofillState
					)
				)
			).toEqual( [ expected ] );
		}
	);
} );

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true,
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {
			locale: 'en_US',
			countries: [],
			loadingCountries: false,
			hasFinishedResolution: true,
		} ),
	};
} );

describe( 'StoreAddress', () => {
	const mockedGetInputProps = jest.fn().mockReturnValue( '' );

	it( 'should render should in the order of Country / Region, Address, Post / Zip Code, City, Email Address.', () => {
		const { container } = render(
			<StoreAddress
				getInputProps={ mockedGetInputProps }
				setValue={ jest.fn() }
			/>
		);
		const labels = container.querySelectorAll( 'label' );
		const expectedLabelsInOrder = [
			'Country / Region *',
			'Address',
			'Post code',
			'City',
			'Email address',
		];

		[ ...labels ].forEach( ( label, index ) =>
			expect( label.textContent ).toEqual(
				expectedLabelsInOrder[ index ]
			)
		);
	} );
} );
