/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { render, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Form, useFormContext } from '../';
import type { FormContext } from '../';

const TestInputWithContext = () => {
	const formProps = useFormContext< { foo: string } >();
	const fieldValue = formProps.values.foo;

	return (
		<Fragment>
			<span>foo: { fieldValue }</span>
			<button
				onClick={ () => {
					formProps.setValue( 'foo', 'bar' );
				} }
			>
				Submit
			</button>
		</Fragment>
	);
};

describe( 'Form', () => {
	it( 'should default to call the deprecated onSubmitCallback if it is provided.', async () => {
		const onSubmitCallback = jest.fn().mockName( 'onSubmitCallback' );
		const onSubmit = jest.fn().mockName( 'onSubmit' );

		const { queryByText } = render(
			<Form< Record< string, string > >
				onSubmitCallback={ onSubmitCallback }
				onSubmit={ onSubmit }
				validate={ () => ( {} ) }
			>
				{ ( {
					handleSubmit,
				}: FormContext< Record< string, string > > ) => {
					return <button onClick={ handleSubmit }>Submit</button>;
				} }
			</Form>
		);

		const submitButton = queryByText( 'Submit' );
		if ( submitButton ) {
			userEvent.click( submitButton );
		}

		await waitFor( () =>
			expect( onSubmitCallback ).toHaveBeenCalledTimes( 1 )
		);
		await waitFor( () => expect( onSubmit ).not.toHaveBeenCalled() );
	} );

	it( 'should default to call the deprecated onChangeCallback prop if it is provided.', async () => {
		const mockOnChangeCallback = jest.fn();
		const mockOnChange = jest.fn();

		const { queryByText } = render(
			<Form
				onChangeCallback={ mockOnChangeCallback }
				onChange={ mockOnChange }
				validate={ () => ( {} ) }
			>
				{ ( { setValue }: FormContext< Record< string, string > > ) => {
					return (
						<button
							onClick={ () => {
								setValue( 'foo', 'bar' );
							} }
						>
							Change
						</button>
					);
				} }
			</Form>
		);

		const changeButton = queryByText( 'Change' );
		if ( changeButton ) {
			userEvent.click( changeButton );
		}

		await waitFor( () =>
			expect( mockOnChangeCallback ).toHaveBeenCalledTimes( 1 )
		);
		await waitFor( () => expect( mockOnChange ).not.toHaveBeenCalled() );
	} );

	it( 'should call onSubmit if it is the only prop provided', async () => {
		const mockOnSubmit = jest.fn();

		const { queryByText } = render(
			<Form onSubmit={ mockOnSubmit } validate={ () => ( {} ) }>
				{ ( {
					handleSubmit,
				}: FormContext< Record< string, string > > ) => {
					return <button onClick={ handleSubmit }>Submit</button>;
				} }
			</Form>
		);

		const submitButton = queryByText( 'Submit' );
		if ( submitButton ) {
			userEvent.click( submitButton );
		}

		await waitFor( () =>
			expect( mockOnSubmit ).toHaveBeenCalledTimes( 1 )
		);
	} );

	it( 'should call onChange if it is the only prop provided', async () => {
		const mockOnChange = jest.fn();

		const { queryByText } = render(
			<Form onChange={ mockOnChange } validate={ () => ( {} ) }>
				{ ( { setValue }: FormContext< Record< string, string > > ) => {
					return (
						<button
							onClick={ () => {
								setValue( 'foo', 'bar' );
							} }
						>
							Submit
						</button>
					);
				} }
			</Form>
		);

		const submitButton = queryByText( 'Submit' );
		if ( submitButton ) {
			userEvent.click( submitButton );
		}

		await waitFor( () =>
			expect( mockOnChange ).toHaveBeenCalledTimes( 1 )
		);
	} );

	it( 'should call onChange with latest changed values', () => {
		const mockOnChange = jest.fn();

		const { queryByLabelText } = render(
			<Form onChange={ mockOnChange } validate={ () => ( {} ) }>
				{ ( {
					setValue,
					getInputProps,
				}: FormContext< Record< string, string > > ) => {
					return (
						<TextControl
							label={ 'First Name' }
							{ ...getInputProps( 'firstName' ) }
						/>
					);
				} }
			</Form>
		);

		const firstNameInput = queryByLabelText( 'First Name' );
		if ( firstNameInput ) {
			fireEvent.change( firstNameInput, { target: { value: 'F' } } );
			expect( mockOnChange ).toHaveBeenCalledWith(
				{ name: 'firstName', value: 'F' },
				{ firstName: 'F' },
				true
			);

			fireEvent.change( firstNameInput, { target: { value: 'Fi' } } );
			expect( mockOnChange ).toHaveBeenCalledWith(
				{ name: 'firstName', value: 'Fi' },
				{ firstName: 'Fi' },
				true
			);
		}
	} );

	it( 'should call onChange with latest hasErrors', () => {
		const mockOnChange = jest.fn();

		type TestData = {
			firstName: string;
		};

		const validate = ( data: TestData ): Record< string, string > => {
			if ( data.firstName && data.firstName.length < 2 ) {
				return {
					firstName: 'Must be greater then 1',
				};
			}
			return {};
		};

		const { queryByLabelText } = render(
			<Form< TestData > onChange={ mockOnChange } validate={ validate }>
				{ ( {
					setValue,
					getInputProps,
				}: FormContext< Record< string, string > > ) => {
					return (
						<TextControl
							label={ 'First Name' }
							{ ...getInputProps( 'firstName' ) }
						/>
					);
				} }
			</Form>
		);

		const firstNameInput = queryByLabelText( 'First Name' );
		if ( firstNameInput ) {
			fireEvent.change( firstNameInput, { target: { value: 'F' } } );
			expect( mockOnChange ).toHaveBeenCalledWith(
				{ name: 'firstName', value: 'F' },
				{ firstName: 'F' },
				false
			);

			fireEvent.change( firstNameInput, { target: { value: 'Fi' } } );
			expect( mockOnChange ).toHaveBeenCalledWith(
				{ name: 'firstName', value: 'Fi' },
				{ firstName: 'Fi' },
				true
			);
		}
	} );

	it( 'should call onChanges with latest changed values with one change', () => {
		const mockOnChanges = jest.fn();

		const { queryByLabelText } = render(
			<Form onChanges={ mockOnChanges } validate={ () => ( {} ) }>
				{ ( {
					setValue,
					getInputProps,
				}: FormContext< Record< string, string > > ) => {
					return (
						<TextControl
							label={ 'First Name' }
							{ ...getInputProps( 'firstName' ) }
						/>
					);
				} }
			</Form>
		);

		const firstNameInput = queryByLabelText( 'First Name' );
		if ( firstNameInput ) {
			fireEvent.change( firstNameInput, { target: { value: 'F' } } );
			expect( mockOnChanges ).toHaveBeenCalledWith(
				[ { name: 'firstName', value: 'F' } ],
				{ firstName: 'F' },
				true
			);

			fireEvent.change( firstNameInput, { target: { value: 'Fi' } } );
			expect( mockOnChanges ).toHaveBeenCalledWith(
				[ { name: 'firstName', value: 'Fi' } ],
				{ firstName: 'Fi' },
				true
			);
		}
	} );

	it( 'should call onChanges with latest hasErrors with one change', () => {
		const mockOnChanges = jest.fn();

		type TestData = {
			firstName: string;
		};

		const validate = ( data: TestData ): Record< string, string > => {
			if ( data.firstName && data.firstName.length < 2 ) {
				return {
					firstName: 'Must be greater then 1',
				};
			}
			return {};
		};

		const { queryByLabelText } = render(
			<Form< TestData > onChanges={ mockOnChanges } validate={ validate }>
				{ ( {
					setValue,
					getInputProps,
				}: FormContext< Record< string, string > > ) => {
					return (
						<TextControl
							label={ 'First Name' }
							{ ...getInputProps( 'firstName' ) }
						/>
					);
				} }
			</Form>
		);

		const firstNameInput = queryByLabelText( 'First Name' );
		if ( firstNameInput ) {
			fireEvent.change( firstNameInput, { target: { value: 'F' } } );
			expect( mockOnChanges ).toHaveBeenCalledWith(
				[ { name: 'firstName', value: 'F' } ],
				{ firstName: 'F' },
				false
			);

			fireEvent.change( firstNameInput, { target: { value: 'Fi' } } );
			expect( mockOnChanges ).toHaveBeenCalledWith(
				[ { name: 'firstName', value: 'Fi' } ],
				{ firstName: 'Fi' },
				true
			);
		}
	} );

	it( 'should call onChanges with latest changed values with multiple changes', () => {
		const mockOnChanges = jest.fn();

		const { queryByText } = render(
			<Form onChanges={ mockOnChanges } validate={ () => ( {} ) }>
				{ ( {
					setValues,
				}: FormContext< Record< string, string > > ) => {
					return (
						<button
							onClick={ () => {
								setValues( { foo: 'bar', foo2: 'bar2' } );
							} }
						>
							Submit
						</button>
					);
				} }
			</Form>
		);

		const submitButton = queryByText( 'Submit' );
		if ( submitButton ) {
			userEvent.click( submitButton );
		}

		expect( mockOnChanges ).toHaveBeenCalledWith(
			[
				{ name: 'foo', value: 'bar' },
				{ name: 'foo2', value: 'bar2' },
			],
			{ foo: 'bar', foo2: 'bar2' },
			true
		);
	} );

	it( 'should call onChanges with latest hasErrors with multiple changes', () => {
		const mockOnChanges = jest.fn();

		type TestData = {
			foo: string;
			foo2: string;
		};

		const validate = ( data: TestData ): Record< string, string > => {
			if ( data.foo && data.foo.length < 2 ) {
				return {
					foo: 'Must be greater then 1',
				};
			}
			return {};
		};

		const { queryByText } = render(
			<Form onChanges={ mockOnChanges } validate={ validate }>
				{ ( {
					setValues,
				}: FormContext< Record< string, string > > ) => {
					return (
						<button
							onClick={ () => {
								setValues( { foo: 'b', foo2: 'bar2' } );
							} }
						>
							Submit
						</button>
					);
				} }
			</Form>
		);

		const submitButton = queryByText( 'Submit' );
		if ( submitButton ) {
			userEvent.click( submitButton );
		}

		expect( mockOnChanges ).toHaveBeenCalledWith(
			[
				{ name: 'foo', value: 'b' },
				{ name: 'foo2', value: 'bar2' },
			],
			{ foo: 'b', foo2: 'bar2' },
			false
		);
	} );

	describe( 'FormContext', () => {
		it( 'should allow nested field to use useFormContext to set field value', async () => {
			const mockOnChange = jest.fn();

			const { queryByText } = render(
				<Form onChange={ mockOnChange } validate={ () => ( {} ) }>
					<TestInputWithContext />
				</Form>
			);

			const submitButton = queryByText( 'Submit' );
			if ( submitButton ) {
				userEvent.click( submitButton );
			}
			expect( queryByText( 'foo: bar' ) ).toBeInTheDocument();

			await waitFor( () =>
				expect( mockOnChange ).toHaveBeenCalledTimes( 1 )
			);
		} );
	} );
} );
