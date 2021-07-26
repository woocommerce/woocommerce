/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Form from '../';

// There is async code inside the callback handlers that we must flush
// before asserting they were called.
function flushPromises() {
	return new Promise( function ( resolve ) {
		setTimeout( resolve );
	} );
}

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

		await flushPromises();
		await expect( onSubmitCallback ).toHaveBeenCalledTimes( 1 );
		await expect( onSubmit ).not.toHaveBeenCalled();
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

		await flushPromises();
		await expect( mockOnChangeCallback ).toHaveBeenCalledTimes( 1 );
		await expect( mockOnChange ).not.toHaveBeenCalled();
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

		await flushPromises();
		await expect( mockOnSubmit ).toHaveBeenCalledTimes( 1 );
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

		await flushPromises();
		await expect( mockOnChange ).toHaveBeenCalledTimes( 1 );
	} );
} );
