/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ChangeEvent, ReactNode, startTransition, StrictMode } from 'react';

/**
 * Internal dependencies
 */
import { Form } from '../';
import { FormContextType } from '../types';

type NameValues = {
	firstName: string;
	lastName: string;
	email: string;
};

const initialNameValues = (): NameValues => ( {
	firstName: 'Initial',
	lastName: 'Person',
	email: 'initial@example.com',
} );

/**
 * Renders a Form with mocked callbacks, an output of the current values, and the
 * mount-time validation call already cleared.
 */
function renderForm< Values extends Record< string, unknown > >(
	initialValues: Values,
	children: ( context: FormContextType< Values > ) => ReactNode,
	{ strict = false } = {}
) {
	const validate = jest.fn( () => ( {} ) );
	const onChange = jest.fn();
	const onChanges = jest.fn();
	const form = (
		<Form< Values >
			initialValues={ initialValues }
			validate={ validate }
			onChange={ onChange }
			onChanges={ onChanges }
		>
			{ ( context ) => (
				<>
					{ children( context ) }
					<output aria-label="Form values">
						{ JSON.stringify( context.values ) }
					</output>
				</>
			) }
		</Form>
	);

	render( strict ? <StrictMode>{ form }</StrictMode> : form );
	validate.mockClear();

	return { validate, onChange, onChanges };
}

const renderedValues = () =>
	screen.getByRole( 'status', { name: 'Form values' } ).textContent;

const validatedValues = ( validate: jest.Mock ) =>
	validate.mock.calls.map( ( [ values ] ) => values );

describe( 'Form state updates', () => {
	it( 'reports one entry per setValue with the complete next values', () => {
		const { onChange, onChanges } = renderForm(
			initialNameValues(),
			( { setValue } ) => (
				<button onClick={ () => setValue( 'firstName', 'Updated' ) }>
					Update first name
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update first name' } )
		);

		const nextValues = { ...initialNameValues(), firstName: 'Updated' };
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'firstName', value: 'Updated' }, nextValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'firstName', value: 'Updated' } ], nextValues, true ],
		] );
	} );

	it( 'applies three same-stack writes in order and reports each with its own snapshot', () => {
		const { validate, onChange, onChanges } = renderForm(
			{ firstName: '', lastName: '', email: '' },
			( { setValue } ) => (
				<button
					onClick={ () => {
						setValue( 'firstName', 'A' );
						setValue( 'lastName', 'B' );
						setValue( 'email', 'C' );
					} }
				>
					Update all fields
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update all fields' } )
		);

		const snapshots = [
			{ firstName: 'A', lastName: '', email: '' },
			{ firstName: 'A', lastName: 'B', email: '' },
			{ firstName: 'A', lastName: 'B', email: 'C' },
		];
		expect( renderedValues() ).toBe( JSON.stringify( snapshots[ 2 ] ) );
		expect( validatedValues( validate ) ).toEqual( snapshots );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'firstName', value: 'A' }, snapshots[ 0 ], true ],
			[ { name: 'lastName', value: 'B' }, snapshots[ 1 ], true ],
			[ { name: 'email', value: 'C' }, snapshots[ 2 ], true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'firstName', value: 'A' } ], snapshots[ 0 ], true ],
			[ [ { name: 'lastName', value: 'B' } ], snapshots[ 1 ], true ],
			[ [ { name: 'email', value: 'C' } ], snapshots[ 2 ], true ],
		] );
	} );

	it( 'reports repeated and same-value writes to one field without deduplicating', () => {
		const { validate, onChange, onChanges } = renderForm(
			initialNameValues(),
			( { setValue } ) => (
				<button
					onClick={ () => {
						setValue( 'firstName', 'First' );
						setValue( 'firstName', 'Second' );
						setValue( 'firstName', 'Second' );
					} }
				>
					Rewrite first name
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Rewrite first name' } )
		);

		const snapshots = [ 'First', 'Second', 'Second' ].map(
			( firstName ) => ( { ...initialNameValues(), firstName } )
		);
		expect( renderedValues() ).toBe( JSON.stringify( snapshots[ 2 ] ) );
		expect( validatedValues( validate ) ).toEqual( snapshots );
		expect( onChange.mock.calls ).toEqual(
			snapshots.map( ( snapshot ) => [
				{ name: 'firstName', value: snapshot.firstName },
				snapshot,
				true,
			] )
		);
		expect( onChanges.mock.calls ).toEqual(
			snapshots.map( ( snapshot ) => [
				[ { name: 'firstName', value: snapshot.firstName } ],
				snapshot,
				true,
			] )
		);
	} );

	it( 'reports a nested write under its top-level key and keeps its siblings', () => {
		const { onChange, onChanges } = renderForm(
			{
				profile: { firstName: 'Initial', lastName: 'Person' },
				status: 'active',
			},
			( { setValue } ) => (
				<button
					onClick={ () => setValue( 'profile.firstName', 'Updated' ) }
				>
					Update profile
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update profile' } )
		);

		const nextValues = {
			profile: { firstName: 'Updated', lastName: 'Person' },
			status: 'active',
		};
		expect( renderedValues() ).toBe( JSON.stringify( nextValues ) );
		expect( onChange.mock.calls ).toEqual( [
			[
				{ name: 'profile', value: nextValues.profile },
				nextValues,
				true,
			],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[
				[ { name: 'profile', value: nextValues.profile } ],
				nextValues,
				true,
			],
		] );
	} );

	it( 'merges a setValues batch onto a same-stack write and reports its keys in order', () => {
		const patch = {
			email: 'updated@example.com',
			firstName: 'Updated',
		} as NameValues;
		const { validate, onChange, onChanges } = renderForm(
			initialNameValues(),
			( { setValue, setValues } ) => (
				<button
					onClick={ () => {
						setValue( 'lastName', 'Same stack' );
						setValues( patch );
					} }
				>
					Apply values
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Apply values' } )
		);

		const firstValues = { ...initialNameValues(), lastName: 'Same stack' };
		const nextValues = {
			firstName: 'Updated',
			lastName: 'Same stack',
			email: 'updated@example.com',
		};
		expect( renderedValues() ).toBe( JSON.stringify( nextValues ) );
		expect( validatedValues( validate ) ).toEqual( [
			firstValues,
			nextValues,
		] );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'lastName', value: 'Same stack' }, firstValues, true ],
			[
				{ name: 'email', value: 'updated@example.com' },
				nextValues,
				true,
			],
			[ { name: 'firstName', value: 'Updated' }, nextValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[
				[ { name: 'lastName', value: 'Same stack' } ],
				firstValues,
				true,
			],
			[
				[
					{ name: 'email', value: 'updated@example.com' },
					{ name: 'firstName', value: 'Updated' },
				],
				nextValues,
				true,
			],
		] );
	} );

	it( 'builds a same-stack write on reset values and does not report the reset', () => {
		const resetValues: NameValues = {
			firstName: 'Reset',
			lastName: 'Values',
			email: 'reset@example.com',
		};
		const { validate, onChange, onChanges } = renderForm(
			initialNameValues(),
			( { resetForm, setValue } ) => (
				<button
					onClick={ () => {
						resetForm( resetValues );
						setValue( 'firstName', 'After reset' );
					} }
				>
					Reset and update
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Reset and update' } )
		);

		const nextValues = { ...resetValues, firstName: 'After reset' };
		expect( renderedValues() ).toBe( JSON.stringify( nextValues ) );
		expect( validatedValues( validate ) ).toEqual( [ nextValues ] );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'firstName', value: 'After reset' }, nextValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[
				[ { name: 'firstName', value: 'After reset' } ],
				nextValues,
				true,
			],
		] );
	} );

	it( 'applies a write issued from inside onChange on top of the outer write and notifies depth-first', () => {
		const notifications: string[] = [];
		let writeFromCallback = () => {};
		const { validate, onChange, onChanges } = renderForm(
			initialNameValues(),
			( { setValue } ) => {
				writeFromCallback = () => setValue( 'lastName', 'Nested' );
				return (
					<button onClick={ () => setValue( 'firstName', 'Outer' ) }>
						Update with nested change
					</button>
				);
			}
		);
		onChange.mockImplementation(
			( change: { name: string; value: string } ) => {
				notifications.push( `onChange:${ change.name }` );
				if ( change.name === 'firstName' && change.value === 'Outer' ) {
					writeFromCallback();
				}
			}
		);
		onChanges.mockImplementation( ( changes: { name: string }[] ) => {
			notifications.push( `onChanges:${ changes[ 0 ].name }` );
		} );

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update with nested change' } )
		);

		const outerValues = { ...initialNameValues(), firstName: 'Outer' };
		const nestedValues = { ...outerValues, lastName: 'Nested' };
		expect( renderedValues() ).toBe( JSON.stringify( nestedValues ) );
		expect( validatedValues( validate ) ).toEqual( [
			outerValues,
			nestedValues,
		] );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'firstName', value: 'Outer' }, outerValues, true ],
			[ { name: 'lastName', value: 'Nested' }, nestedValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'lastName', value: 'Nested' } ], nestedValues, true ],
			[ [ { name: 'firstName', value: 'Outer' } ], outerValues, true ],
		] );
		expect( notifications ).toEqual( [
			'onChange:firstName',
			'onChange:lastName',
			'onChanges:lastName',
			'onChanges:firstName',
		] );
	} );

	it( 'reports each write once under StrictMode', () => {
		const { validate, onChange, onChanges } = renderForm(
			initialNameValues(),
			( { setValue } ) => (
				<button onClick={ () => setValue( 'firstName', 'Updated' ) }>
					Update in StrictMode
				</button>
			),
			{ strict: true }
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update in StrictMode' } )
		);

		const nextValues = { ...initialNameValues(), firstName: 'Updated' };
		expect( validatedValues( validate ) ).toEqual( [ nextValues ] );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'firstName', value: 'Updated' }, nextValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'firstName', value: 'Updated' } ], nextValues, true ],
		] );
	} );

	it( 'applies same-stack writes issued inside startTransition', async () => {
		const { onChange, onChanges } = renderForm(
			initialNameValues(),
			( { setValue } ) => (
				<button
					onClick={ () =>
						startTransition( () => {
							setValue( 'firstName', 'Transition' );
							setValue( 'lastName', 'Action' );
						} )
					}
				>
					Update in transition
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update in transition' } )
		);

		const firstValues = { ...initialNameValues(), firstName: 'Transition' };
		const secondValues = { ...firstValues, lastName: 'Action' };
		await waitFor( () =>
			expect( renderedValues() ).toBe( JSON.stringify( secondValues ) )
		);
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'firstName', value: 'Transition' }, firstValues, true ],
			[ { name: 'lastName', value: 'Action' }, secondValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[
				[ { name: 'firstName', value: 'Transition' } ],
				firstValues,
				true,
			],
			[ [ { name: 'lastName', value: 'Action' } ], secondValues, true ],
		] );
	} );

	it( 'applies two same-stack checkbox toggles in order', () => {
		const checkboxEvent = {
			target: { type: 'checkbox' },
		} as unknown as ChangeEvent< HTMLInputElement >;
		const { validate, onChange, onChanges } = renderForm(
			{ enabled: false, label: 'Stable' },
			( { getCheckboxControlProps } ) => {
				const checkboxProps = getCheckboxControlProps( 'enabled' );
				return (
					<>
						<input
							type="checkbox"
							aria-label="Enabled"
							{ ...checkboxProps }
						/>
						<button
							onClick={ () => {
								checkboxProps.onChange( checkboxEvent );
								checkboxProps.onChange( checkboxEvent );
							} }
						>
							Toggle twice
						</button>
					</>
				);
			}
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Toggle twice' } )
		);

		const enabledValues = { enabled: true, label: 'Stable' };
		const disabledValues = { enabled: false, label: 'Stable' };
		expect(
			screen.getByRole( 'checkbox', { name: 'Enabled' } )
		).not.toBeChecked();
		expect( renderedValues() ).toBe( JSON.stringify( disabledValues ) );
		expect( validatedValues( validate ) ).toEqual( [
			enabledValues,
			disabledValues,
		] );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'enabled', value: true }, enabledValues, true ],
			[ { name: 'enabled', value: false }, disabledValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'enabled', value: true } ], enabledValues, true ],
			[ [ { name: 'enabled', value: false } ], disabledValues, true ],
		] );
	} );

	it( 'reports a literal dotted key as written rather than as a path', () => {
		const { onChange, onChanges } = renderForm(
			{ 'a.b': 1, other: 2 },
			( { setValue } ) => (
				<button onClick={ () => setValue( 'a.b', 2 ) }>
					Update dotted key
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update dotted key' } )
		);

		const nextValues = { 'a.b': 2, other: 2 };
		expect( renderedValues() ).toBe( JSON.stringify( nextValues ) );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'a.b', value: 2 }, nextValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'a.b', value: 2 } ], nextValues, true ],
		] );
	} );

	it( 'writes a literal key holding a segment lodash refuses in a path', () => {
		const { onChange, onChanges } = renderForm(
			{ 'a.constructor': 1, other: 2 },
			( { setValue } ) => (
				<button onClick={ () => setValue( 'a.constructor', 2 ) }>
					Update literal key
				</button>
			)
		);

		userEvent.click(
			screen.getByRole( 'button', { name: 'Update literal key' } )
		);

		const nextValues = { 'a.constructor': 2, other: 2 };
		expect( renderedValues() ).toBe( JSON.stringify( nextValues ) );
		expect( onChange.mock.calls ).toEqual( [
			[ { name: 'a.constructor', value: 2 }, nextValues, true ],
		] );
		expect( onChanges.mock.calls ).toEqual( [
			[ [ { name: 'a.constructor', value: 2 } ], nextValues, true ],
		] );
	} );

	it.each( [ 'constructor', 'prototype', '__proto__', 'a.constructor' ] )(
		'drops a %s write that lodash refuses to make',
		( name ) => {
			const initialValues: Record< string, unknown > = {
				a: { b: 1 },
				other: 2,
			};
			const { validate, onChange, onChanges } = renderForm(
				initialValues,
				( { setValue } ) => (
					<button onClick={ () => setValue( name, 'Updated' ) }>
						Write refused key
					</button>
				)
			);

			userEvent.click(
				screen.getByRole( 'button', { name: 'Write refused key' } )
			);

			expect( renderedValues() ).toBe( JSON.stringify( initialValues ) );
			expect( validate ).not.toHaveBeenCalled();
			expect( onChange ).not.toHaveBeenCalled();
			expect( onChanges ).not.toHaveBeenCalled();
		}
	);
} );
