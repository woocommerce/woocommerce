/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

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
