/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Textarea from '..';

describe( 'Textarea', () => {
	it( 'preserves the default rows and merges custom class names', () => {
		const { getByRole } = render(
			<Textarea
				className="custom-textarea"
				onTextChange={ jest.fn() }
				value="Initial value"
			/>
		);

		const textarea = getByRole( 'textbox' );
		expect( textarea ).toHaveClass(
			'wc-block-components-textarea',
			'custom-textarea'
		);
		expect( textarea ).toHaveAttribute( 'rows', '2' );
		expect( textarea ).toHaveValue( 'Initial value' );
		expect( textarea ).toBeEnabled();
	} );

	it( 'forwards native, accessibility, and data attributes', () => {
		const { getByRole } = render(
			<Textarea
				onTextChange={ jest.fn() }
				value=""
				id="order-notes"
				name="order_notes"
				rows={ 5 }
				maxLength={ 100 }
				required
				aria-label="Order notes"
				data-custom="notes"
			/>
		);

		const textarea = getByRole( 'textbox', { name: 'Order notes' } );
		expect( textarea ).toHaveAttribute( 'id', 'order-notes' );
		expect( textarea ).toHaveAttribute( 'name', 'order_notes' );
		expect( textarea ).toHaveAttribute( 'rows', '5' );
		expect( textarea ).toHaveAttribute( 'maxlength', '100' );
		expect( textarea ).toBeRequired();
		expect( textarea ).toHaveAttribute( 'data-custom', 'notes' );
	} );

	it( 'forwards focus, blur, and keyboard events', async () => {
		const user = userEvent.setup();
		const onFocus = jest.fn();
		const onBlur = jest.fn();
		const onKeyDown = jest.fn();
		const { getByRole } = render(
			<Textarea
				onTextChange={ jest.fn() }
				value=""
				onFocus={ onFocus }
				onBlur={ onBlur }
				onKeyDown={ onKeyDown }
			/>
		);

		const textarea = getByRole( 'textbox' );
		await user.click( textarea );
		expect( onFocus ).toHaveBeenCalledWith(
			expect.objectContaining( { target: textarea, type: 'focus' } )
		);

		await user.keyboard( '{Escape}' );
		expect( onKeyDown ).toHaveBeenCalledWith(
			expect.objectContaining( { target: textarea, key: 'Escape' } )
		);

		await user.tab();
		expect( onBlur ).toHaveBeenCalledWith(
			expect.objectContaining( { target: textarea, type: 'blur' } )
		);
	} );

	it( 'calls onTextChange with the new text', async () => {
		const user = userEvent.setup();
		const onTextChange = jest.fn();
		const { getByRole } = render(
			<Textarea onTextChange={ onTextChange } value="Initial notes" />
		);

		await user.type( getByRole( 'textbox' ), '!' );

		expect( onTextChange ).toHaveBeenCalledTimes( 1 );
		expect( onTextChange ).toHaveBeenCalledWith( 'Initial notes!' );
	} );

	it( 'calls native onChange without replacing onTextChange', async () => {
		const user = userEvent.setup();
		const onTextChange = jest.fn();
		const onChange = jest.fn();
		const { getByRole } = render(
			<Textarea
				onTextChange={ onTextChange }
				onChange={ onChange }
				value="Initial notes"
			/>
		);

		const textarea = getByRole( 'textbox' );
		await user.type( textarea, '!' );

		expect( onTextChange ).toHaveBeenCalledTimes( 1 );
		expect( onTextChange ).toHaveBeenCalledWith( 'Initial notes!' );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenCalledWith(
			expect.objectContaining( { target: textarea, type: 'change' } )
		);
	} );

	it( 'preserves disabled and placeholder props', () => {
		const { getByPlaceholderText } = render(
			<Textarea
				onTextChange={ jest.fn() }
				value=""
				disabled
				placeholder="Enter notes"
			/>
		);

		expect( getByPlaceholderText( 'Enter notes' ) ).toBeDisabled();
	} );
} );
