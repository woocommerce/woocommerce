/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Form from '../';

describe( 'Form', () => {
	it( 'should default to call the deprecated onSubmitCallback if it is provided.', async () => {
		const onSubmitCallback = jest.fn().mockName( 'onSubmitCallback' );
		const onSubmit = jest.fn().mockName( 'onSubmit' );

		const { queryByText } = render(
			<Form
				onSubmitCallback={ onSubmitCallback }
				onSubmit={ onSubmit }
				validate={ () => ( {} ) }
			>
				{ ( { handleSubmit } ) => {
					return <button onClick={ handleSubmit }>Submit</button>;
				} }
			</Form>
		);

		userEvent.click( queryByText( 'Submit' ) );

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
				{ ( { setValue } ) => {
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

		userEvent.click( queryByText( 'Change' ) );

		await waitFor( () =>
			expect( mockOnChangeCallback ).toHaveBeenCalledTimes( 1 )
		);
		await waitFor( () => expect( mockOnChange ).not.toHaveBeenCalled() );
	} );

	it( 'should call onSubmit if it is the only prop provided', async () => {
		const mockOnSubmit = jest.fn();

		const { queryByText } = render(
			<Form onSubmit={ mockOnSubmit } validate={ () => ( {} ) }>
				{ ( { handleSubmit } ) => {
					return <button onClick={ handleSubmit }>Submit</button>;
				} }
			</Form>
		);

		userEvent.click( queryByText( 'Submit' ) );

		await waitFor( () =>
			expect( mockOnSubmit ).toHaveBeenCalledTimes( 1 )
		);
	} );

	it( 'should call onChange if it is the only prop provided', async () => {
		const mockOnChange = jest.fn();

		const { queryByText } = render(
			<Form onChange={ mockOnChange } validate={ () => ( {} ) }>
				{ ( { setValue } ) => {
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

		userEvent.click( queryByText( 'Submit' ) );

		await waitFor( () =>
			expect( mockOnChange ).toHaveBeenCalledTimes( 1 )
		);
	} );
} );
