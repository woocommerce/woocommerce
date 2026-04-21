/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { fireEvent, render, screen } from '@testing-library/react';

// Mock the @wordpress/components widgets that JSDOM cannot fully exercise.
// We stub them with deterministic test inputs so we can assert OUR wiring
// (initial value → render, change → onChange wrapper) without depending on
// the upstream component's interactive behaviour. We only re-export the
// specific symbols the transformer actually imports — spreading the real
// module triggers eager evaluation of unrelated lazy proxies (e.g. v2
// custom-select internals) and crashes the suite.
jest.mock( '@wordpress/components', () => {
	// Pull individual components from their submodules to avoid evaluating
	// the lazy proxy index which crashes when iterating unrelated exports
	// in the JSDOM environment.
	const InputControl = jest.requireActual(
		'@wordpress/components/build/input-control'
	).default;
	const TextareaControl = jest.requireActual(
		'@wordpress/components/build/textarea-control'
	).default;
	// Use the real BaseControl so its label/htmlFor wiring is exercised by
	// `getByLabelText` assertions for the wrapped widget Edits below.
	const BaseControl = jest.requireActual(
		'@wordpress/components/build/base-control'
	).default;

	type StubProps = {
		value?: string | null;
		color?: string | null;
		currentDate?: string | null;
		onChange?: ( next: string ) => void;
	};

	const ColorPickerStub = ( props: StubProps ) => (
		<input
			data-testid="color-picker-stub"
			value={ props.color ?? '' }
			onChange={ ( event ) =>
				props.onChange && props.onChange( event.target.value )
			}
			readOnly={ ! props.onChange }
		/>
	);

	const DatePickerStub = ( props: StubProps ) => (
		<input
			data-testid="date-picker-stub"
			value={ props.currentDate ?? '' }
			onChange={ ( event ) =>
				props.onChange && props.onChange( event.target.value )
			}
			readOnly={ ! props.onChange }
		/>
	);

	const DateTimePickerStub = ( props: StubProps ) => (
		<input
			data-testid="date-time-picker-stub"
			value={ props.currentDate ?? '' }
			onChange={ ( event ) =>
				props.onChange && props.onChange( event.target.value )
			}
			readOnly={ ! props.onChange }
		/>
	);

	type ComboboxStubProps = StubProps & {
		options?: Array< { label: string; value: string } >;
	};

	const ComboboxControlStub = ( props: ComboboxStubProps ) => (
		<select
			data-testid="combobox-stub"
			value={ props.value ?? '' }
			onChange={ ( event ) =>
				props.onChange && props.onChange( event.target.value )
			}
		>
			{ ( props.options ?? [] ).map( ( option ) => (
				<option key={ option.value } value={ option.value }>
					{ option.label }
				</option>
			) ) }
		</select>
	);

	return {
		// Real components used by passing tests.
		__experimentalInputControl: InputControl,
		TextareaControl,
		BaseControl,
		// Stubs for components whose interactive behaviour is unreliable in JSDOM.
		ColorPicker: ColorPickerStub,
		DatePicker: DatePickerStub,
		DateTimePicker: DateTimePickerStub,
		ComboboxControl: ComboboxControlStub,
	};
} );

/**
 * Internal dependencies
 */
import {
	baseFieldTransformer,
	createChildrenWithRows,
	parseOptions,
	reorderGroupFields,
	registerFieldTypeTransformer,
} from '../field-transformers';
import type { ReactSettingsField } from '../types';

type EditProps = {
	data: Record< string, unknown >;
	field: {
		id: string;
		label: string;
		getValue: ( args: { item: Record< string, unknown > } ) => unknown;
		setValue: ( args: {
			item: Record< string, unknown >;
			value: unknown;
		} ) => Record< string, unknown >;
	};
	onChange: ( value: Record< string, unknown > ) => void;
};

const buildField = (
	transformed: Record< string, unknown >,
	settingId: string
): EditProps[ 'field' ] => ( {
	id: settingId,
	label:
		typeof transformed.label === 'string'
			? ( transformed.label as string )
			: settingId,
	getValue:
		typeof transformed.getValue === 'function'
			? ( transformed.getValue as EditProps[ 'field' ][ 'getValue' ] )
			: ( { item } ) => item[ settingId ],
	setValue:
		typeof transformed.setValue === 'function'
			? ( transformed.setValue as EditProps[ 'field' ][ 'setValue' ] )
			: ( { item, value } ) => ( { ...item, [ settingId ]: value } ),
} );

describe( 'field transformers', () => {
	it( 'parses options from a record', () => {
		const options = parseOptions( { usd: 'US Dollar', eur: 'Euro' } );

		expect( options ).toEqual( [
			{ label: 'US Dollar', value: 'usd' },
			{ label: 'Euro', value: 'eur' },
		] );
	} );

	it( 'parses options from an array', () => {
		const options = parseOptions( [
			{ label: 'One', value: '1', desc: 'First' },
			{ label: 'Two', value: '2' },
		] );

		expect( options ).toEqual( [
			{ label: 'One', value: '1', description: 'First' },
			{ label: 'Two', value: '2', description: undefined },
		] );
	} );

	it( 'reorders group fields using provided order', () => {
		const fieldIds = [ 'third', 'first', 'second' ];
		const orderConfig = {
			general: [ 'first', 'second' ],
		};

		expect(
			reorderGroupFields( fieldIds, 'general', orderConfig )
		).toEqual( [ 'first', 'second', 'third' ] );
	} );

	it( 'creates row group children for configured fields', () => {
		const children = createChildrenWithRows(
			[ 'a', 'b', 'c' ],
			[ { id: 'row1', fields: [ 'a', 'b' ] } ]
		);

		expect( children ).toEqual( [
			{
				id: 'row1',
				layout: { type: 'row' },
				children: [ 'a', 'b' ],
			},
			{ id: 'c' },
		] );
	} );

	it( 'transforms checkbox fields with get/set handlers', () => {
		const field: ReactSettingsField = {
			id: 'checkbox_field',
			label: 'Checkbox field',
			type: 'checkbox',
		};

		const transformed = baseFieldTransformer( field );

		expect( transformed.type ).toBe( 'boolean' );
		expect( typeof transformed.getValue ).toBe( 'function' );
		expect( typeof transformed.setValue ).toBe( 'function' );

		const getValue = transformed.getValue as ( input: {
			item: Record< string, unknown >;
		} ) => boolean;
		const setValue = transformed.setValue as ( input: {
			item: Record< string, unknown >;
			value: boolean;
		} ) => Record< string, unknown >;

		expect( getValue( { item: { checkbox_field: 'yes' } } ) ).toBe( true );
		expect( setValue( { item: {}, value: true } ) ).toEqual( {
			checkbox_field: 'yes',
		} );
	} );

	it( 'renders unsupported field message safely', () => {
		const field: ReactSettingsField = {
			id: 'unsupported',
			label: 'Unsupported',
			type: 'unsupported_field_type',
		};

		const transformed = baseFieldTransformer( field );
		const Edit = transformed.Edit as React.ComponentType;

		const { getByText } = render( <Edit /> );

		expect(
			getByText( 'This setting is not available yet.' )
		).toBeInTheDocument();
	} );

	it( 'uses custom field type transformers when registered', () => {
		registerFieldTypeTransformer(
			'custom_transformer_test',
			( setting, baseField ) => ( {
				...baseField,
				type: 'text',
				Edit: () => <div>{ setting.label }</div>,
			} )
		);

		const field: ReactSettingsField = {
			id: 'custom_field',
			label: 'Custom field',
			type: 'custom_transformer_test',
		};

		const transformed = baseFieldTransformer( field );
		const Edit = transformed.Edit as React.ComponentType;

		const { getByText } = render( <Edit /> );

		expect( getByText( 'Custom field' ) ).toBeInTheDocument();
	} );

	it( 'transforms email fields to native DataForm email type', () => {
		const transformed = baseFieldTransformer( {
			id: 'email_field',
			label: 'Email',
			type: 'email',
		} );

		expect( transformed.type ).toBe( 'email' );
		expect( transformed.Edit ).toBeUndefined();
	} );

	it( 'validates URL values via isValid on url fields', () => {
		const transformed = baseFieldTransformer( {
			id: 'url_field',
			label: 'URL',
			type: 'url',
		} );

		expect( transformed.type ).toBe( 'text' );
		const isValid = transformed.isValid as ( v: unknown ) => boolean;
		expect( isValid( '' ) ).toBe( true );
		expect( isValid( 'https://example.com' ) ).toBe( true );
		expect( isValid( 'not a url' ) ).toBe( false );
	} );

	it( 'renders password input and writes back via onChange', () => {
		const transformed = baseFieldTransformer( {
			id: 'password_field',
			label: 'Password',
			type: 'password',
		} );

		expect( transformed.type ).toBe( 'text' );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'password_field' );
		render(
			<Edit
				data={ { password_field: 'old-secret' } }
				field={ field }
				onChange={ onChange }
			/>
		);

		const input = document.querySelector(
			'input[type="password"]'
		) as HTMLInputElement | null;
		expect( input ).not.toBeNull();
		expect( input?.getAttribute( 'autocomplete' ) ).toBe( 'new-password' );
		expect( input?.value ).toBe( 'old-secret' );

		if ( input ) {
			fireEvent.change( input, { target: { value: 'new-secret' } } );
		}

		expect( onChange ).toHaveBeenCalledWith( {
			password_field: 'new-secret',
		} );
	} );

	it( 'renders telephone input and writes back via onChange', () => {
		const transformed = baseFieldTransformer( {
			id: 'phone_field',
			label: 'Phone',
			type: 'tel',
		} );

		expect( transformed.type ).toBe( 'text' );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'phone_field' );
		render(
			<Edit
				data={ { phone_field: '555-1234' } }
				field={ field }
				onChange={ onChange }
			/>
		);

		const input = document.querySelector(
			'input[type="tel"]'
		) as HTMLInputElement | null;
		expect( input ).not.toBeNull();
		expect( input?.value ).toBe( '555-1234' );

		if ( input ) {
			fireEvent.change( input, { target: { value: '555-5555' } } );
		}

		expect( onChange ).toHaveBeenCalledWith( {
			phone_field: '555-5555',
		} );
	} );

	it( 'renders color picker and writes back via onChange', () => {
		const transformed = baseFieldTransformer( {
			id: 'color_field',
			label: 'Brand color',
			type: 'color',
		} );

		expect( transformed.type ).toBe( 'text' );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'color_field' );
		render(
			<Edit
				data={ { color_field: '#ff0000' } }
				field={ field }
				onChange={ onChange }
			/>
		);

		// BaseControl renders the label as a heading (no input to associate
		// with for ColorPicker), so assert via getByText rather than getByLabelText.
		expect( screen.getByText( 'Brand color' ) ).toBeInTheDocument();

		const stub = screen.getByTestId(
			'color-picker-stub'
		) as HTMLInputElement;
		expect( stub.value ).toBe( '#ff0000' );

		fireEvent.change( stub, { target: { value: '#00ff00' } } );

		expect( onChange ).toHaveBeenCalledWith( {
			color_field: '#00ff00',
		} );
	} );

	it( 'renders textarea control and writes back via onChange', () => {
		const transformed = baseFieldTransformer( {
			id: 'notes_field',
			label: 'Notes',
			type: 'textarea',
		} );

		expect( transformed.type ).toBe( 'text' );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'notes_field' );
		render(
			<Edit
				data={ { notes_field: 'hello' } }
				field={ field }
				onChange={ onChange }
			/>
		);

		const textarea = screen.getByLabelText(
			'Notes'
		) as HTMLTextAreaElement;
		expect( textarea.tagName ).toBe( 'TEXTAREA' );
		expect( textarea.value ).toBe( 'hello' );

		fireEvent.change( textarea, { target: { value: 'updated notes' } } );

		expect( onChange ).toHaveBeenCalledWith( {
			notes_field: 'updated notes',
		} );
	} );

	it( 'renders InputControl with associated label for month/week/time field types', () => {
		const cases: Array< {
			type: 'month' | 'week' | 'time';
			initial: string;
			next: string;
		} > = [
			{ type: 'month', initial: '2026-04', next: '2026-05' },
			{ type: 'week', initial: '2026-W17', next: '2026-W18' },
			{ type: 'time', initial: '10:30', next: '11:45' },
		];

		cases.forEach( ( { type, initial, next } ) => {
			const label = `${ type } field`;
			const transformed = baseFieldTransformer( {
				id: `${ type }_field`,
				label,
				type,
			} );

			expect( transformed.type ).toBe( 'text' );

			const Edit = transformed.Edit as React.ComponentType< EditProps >;
			const onChange = jest.fn();
			const field = buildField( transformed, `${ type }_field` );
			const { unmount } = render(
				<Edit
					data={ { [ `${ type }_field` ]: initial } }
					field={ field }
					onChange={ onChange }
				/>
			);

			// InputControl wires up the label-to-input htmlFor association.
			const input = screen.getByLabelText( label ) as HTMLInputElement;
			expect( input.getAttribute( 'type' ) ).toBe( type );
			expect( input.value ).toBe( initial );

			fireEvent.change( input, { target: { value: next } } );

			expect( onChange ).toHaveBeenCalledWith( {
				[ `${ type }_field` ]: next,
			} );

			unmount();
		} );
	} );

	it( 'renders date picker and emits ISO date string on change', () => {
		const transformed = baseFieldTransformer( {
			id: 'date_field',
			label: 'Date',
			type: 'date',
		} );

		expect( transformed.type ).toBe( 'text' );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'date_field' );
		render(
			<Edit
				data={ { date_field: '2026-04-21' } }
				field={ field }
				onChange={ onChange }
			/>
		);

		// The BaseControl wrapper renders a visible label.
		expect( screen.getByText( 'Date' ) ).toBeInTheDocument();

		const stub = screen.getByTestId(
			'date-picker-stub'
		) as HTMLInputElement;
		expect( stub.value ).toBe( '2026-04-21' );

		fireEvent.change( stub, { target: { value: '2026-05-01' } } );

		expect( onChange ).toHaveBeenCalledWith( { date_field: '2026-05-01' } );
	} );

	it( 'renders date picker safely when stored value is unparseable', () => {
		const transformed = baseFieldTransformer( {
			id: 'date_field',
			label: 'Date',
			type: 'date',
		} );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'date_field' );

		expect( () =>
			render(
				<Edit
					data={ { date_field: 'not-a-date' } }
					field={ field }
					onChange={ onChange }
				/>
			)
		).not.toThrow();

		const stub = screen.getByTestId(
			'date-picker-stub'
		) as HTMLInputElement;
		// The guard normalises the unparseable value to an empty string so the
		// underlying picker doesn't blow up.
		expect( stub.value ).toBe( '' );
	} );

	it( 'maps datetime and datetime-local to a DateTimePicker Edit that round-trips values', () => {
		( [ 'datetime', 'datetime-local' ] as const ).forEach( ( type ) => {
			const label = `${ type } field`;
			const transformed = baseFieldTransformer( {
				id: `${ type }_field`,
				label,
				type,
			} );

			expect( transformed.type ).toBe( 'text' );

			const Edit = transformed.Edit as React.ComponentType< EditProps >;
			const onChange = jest.fn();
			const field = buildField( transformed, `${ type }_field` );
			const { unmount } = render(
				<Edit
					data={ {
						[ `${ type }_field` ]: '2026-04-21T10:30:00',
					} }
					field={ field }
					onChange={ onChange }
				/>
			);

			// The BaseControl wrapper renders a visible label.
			expect( screen.getByText( label ) ).toBeInTheDocument();

			const stub = screen.getByTestId(
				'date-time-picker-stub'
			) as HTMLInputElement;
			expect( stub.value ).toBe( '2026-04-21T10:30:00' );

			fireEvent.change( stub, {
				target: { value: '2026-05-01T12:00:00' },
			} );

			expect( onChange ).toHaveBeenCalledWith( {
				[ `${ type }_field` ]: '2026-05-01T12:00:00',
			} );

			unmount();
		} );
	} );

	it( 'renders datetime picker safely when stored value is unparseable', () => {
		const transformed = baseFieldTransformer( {
			id: 'datetime_field',
			label: 'When',
			type: 'datetime',
		} );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'datetime_field' );

		expect( () =>
			render(
				<Edit
					data={ { datetime_field: 'definitely-not-a-date' } }
					field={ field }
					onChange={ onChange }
				/>
			)
		).not.toThrow();

		const stub = screen.getByTestId(
			'date-time-picker-stub'
		) as HTMLInputElement;
		expect( stub.value ).toBe( '' );
	} );

	it( 'renders combobox for single_select_page_with_search and writes selection', () => {
		const transformed = baseFieldTransformer( {
			id: 'page_field',
			label: 'Page',
			type: 'single_select_page_with_search',
			options: { '1': 'Home', '2': 'Shop' },
		} );

		expect( transformed.type ).toBe( 'text' );
		expect( Array.isArray( transformed.elements ) ).toBe( true );
		expect( transformed.elements ).toEqual( [
			{ label: 'Home', value: '1' },
			{ label: 'Shop', value: '2' },
		] );

		const Edit = transformed.Edit as React.ComponentType< EditProps >;
		const onChange = jest.fn();
		const field = buildField( transformed, 'page_field' );
		render(
			<Edit
				data={ { page_field: '1' } }
				field={ field }
				onChange={ onChange }
			/>
		);

		const stub = screen.getByTestId( 'combobox-stub' ) as HTMLSelectElement;
		expect( stub.value ).toBe( '1' );
		expect( stub.querySelectorAll( 'option' ) ).toHaveLength( 2 );

		fireEvent.change( stub, { target: { value: '2' } } );

		expect( onChange ).toHaveBeenCalledWith( { page_field: '2' } );
	} );

	it( 'renders info field as a description-only row that does not write to formData', () => {
		const transformed = baseFieldTransformer( {
			id: 'info_field',
			label: 'Heads up',
			type: 'info',
			desc: 'Informational text shown to merchants.',
		} );

		expect( transformed.type ).toBe( 'text' );

		const getValue = transformed.getValue as ( args: {
			item: Record< string, unknown >;
		} ) => unknown;
		expect( getValue( { item: { info_field: 'ignored' } } ) ).toBe( '' );

		const setValue = transformed.setValue as ( args: {
			item: Record< string, unknown >;
			value: unknown;
		} ) => Record< string, unknown >;
		expect(
			setValue( { item: { foo: 'bar' }, value: 'anything' } )
		).toEqual( { foo: 'bar' } );

		const Edit = transformed.Edit as React.ComponentType;
		const { container, queryByRole } = render( <Edit /> );

		expect( container.textContent ).toContain(
			'Informational text shown to merchants.'
		);
		expect( queryByRole( 'textbox' ) ).toBeNull();
		expect(
			container.querySelector( 'input, textarea, select' )
		).toBeNull();
	} );
} );
