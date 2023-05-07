/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TreeSelectControl from '../index';

const options = [
	{
		value: 'EU',
		label: 'Europe',
		children: [
			{ value: 'ES', label: 'Spain' },
			{ value: 'FR', label: 'France' },
			{ value: 'IT', label: 'Italy' },
		],
	},
	{
		value: 'AS',
		label: 'Asia',
	},
];

describe( 'TreeSelectControl Component', () => {
	it( 'Expands and collapse the Tree', () => {
		const { queryByRole } = render(
			<TreeSelectControl options={ options } value={ [] } />
		);

		const control = queryByRole( 'combobox' );
		expect( queryByRole( 'tree' ) ).toBeFalsy();
		fireEvent.click( control );
		expect( queryByRole( 'tree' ) ).toBeTruthy();
	} );

	it( 'Calls onChange property with the selected values', () => {
		const onChange = jest.fn().mockName( 'onChange' );

		const { queryByLabelText, queryByRole, rerender } = render(
			<TreeSelectControl
				options={ options }
				value={ [] }
				onChange={ onChange }
			/>
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		let checkbox = queryByLabelText( 'Europe' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'FR', 'IT' ] );

		checkbox = queryByLabelText( 'Asia' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'AS' ] );

		rerender(
			<TreeSelectControl
				options={ options }
				value={ [ 'ES' ] }
				onChange={ onChange }
			/>
		);

		checkbox = queryByLabelText( 'Asia' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'AS' ] );
	} );

	it( 'Should include parent in onChange value when includeParent is truthy', () => {
		const onChange = jest.fn().mockName( 'onChange' );

		const { queryByLabelText, queryByRole } = render(
			<TreeSelectControl
				options={ options }
				value={ [] }
				onChange={ onChange }
				includeParent={ true }
			/>
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		const checkbox = queryByLabelText( 'Europe' );
		fireEvent.click( checkbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'FR', 'IT', 'EU' ] );
	} );

	it( 'Renders the label', () => {
		const { queryByLabelText } = render(
			<TreeSelectControl options={ options } label="Select" />
		);

		expect( queryByLabelText( 'Select' ) ).toBeTruthy();
	} );

	it( 'Renders the All Options', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		const { queryByLabelText, queryByRole, rerender } = render(
			<TreeSelectControl options={ options } onChange={ onChange } />
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		const allCheckbox = queryByLabelText( 'All' );

		expect( allCheckbox ).toBeTruthy();

		fireEvent.click( allCheckbox );
		expect( onChange ).toHaveBeenCalledWith( [ 'ES', 'FR', 'IT', 'AS' ] );

		rerender(
			<TreeSelectControl
				value={ [ 'ES', 'FR', 'IT', 'AS' ] }
				options={ options }
				onChange={ onChange }
			/>
		);
		fireEvent.click( allCheckbox );
		expect( onChange ).toHaveBeenCalledWith( [] );
	} );

	it( 'Renders the All Options custom Label', () => {
		const { queryByLabelText, queryByRole } = render(
			<TreeSelectControl
				options={ options }
				selectAllLabel="All countries"
			/>
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		const allCheckbox = queryByLabelText( 'All countries' );

		expect( allCheckbox ).toBeTruthy();
	} );

	it( 'Filters Options on Search', () => {
		const { queryByLabelText, queryByRole } = render(
			<TreeSelectControl options={ options } />
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		expect( queryByLabelText( 'Europe' ) ).toBeTruthy();
		expect( queryByLabelText( 'Asia' ) ).toBeTruthy();

		fireEvent.change( control, { target: { value: 'Asi' } } );

		expect( queryByLabelText( 'Europe' ) ).toBeFalsy(); // none of its children match Asi
		expect( queryByLabelText( 'Asia' ) ).toBeTruthy(); // match Asi

		fireEvent.change( control, { target: { value: 'As' } } ); // doesnt trigger if length < 3

		expect( queryByLabelText( 'Europe' ) ).toBeTruthy();
		expect( queryByLabelText( 'Asia' ) ).toBeTruthy();
		expect( queryByLabelText( 'Spain' ) ).toBeFalsy(); // not expanded

		fireEvent.change( control, { target: { value: 'pain' } } );

		expect( queryByLabelText( 'Europe' ) ).toBeTruthy(); // contains Spain
		expect( queryByLabelText( 'Spain' ) ).toBeTruthy(); // match pain
		expect( queryByLabelText( 'France' ) ).toBeFalsy(); // doesn't match pain
		expect( queryByLabelText( 'Asia' ) ).toBeFalsy(); // doesn't match pain
	} );

	it( 'should call onInputChange when input field changed', () => {
		const onInputChangeMock = jest.fn();
		const { queryByRole } = render(
			<TreeSelectControl
				options={ options }
				onInputChange={ onInputChangeMock }
			/>
		);

		const control = queryByRole( 'combobox' );
		fireEvent.click( control );
		fireEvent.change( control, { target: { value: 'Asi' } } );
		expect( onInputChangeMock ).toHaveBeenCalledWith( 'Asi' );

		fireEvent.change( control, { target: { value: 'As' } } );
		expect( onInputChangeMock ).toHaveBeenCalledWith( 'As' );

		fireEvent.change( control, { target: { value: 'pain' } } );
		expect( onInputChangeMock ).toHaveBeenCalledWith( 'pain' );
	} );
} );
