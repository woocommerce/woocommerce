/**
 * External dependencies
 */
import {
	render,
	findByLabelText,
	queryByLabelText,
	act,
	screen,
	waitFor,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import { dispatch } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import * as actionCreators from '@woocommerce/block-data/validation/actions';
import FrontendBlock from '../frontend';

jest.mock( '@woocommerce/block-settings', () => ( {
	...jest.requireActual( '@woocommerce/block-settings' ),
	TERMS_URL: 'https://example.com/terms/',
	PRIVACY_URL: 'https://example.com/privacy/',
} ) );

jest.mock( '@woocommerce/block-data/validation/actions', () => {
	const actions = jest.requireActual(
		'@woocommerce/block-data/validation/actions'
	);
	return {
		...actions,
		clearValidationError: jest.fn().mockImplementation( ( errorId ) => {
			return actions.clearValidationError( errorId );
		} ),
	};
} );

describe( 'FrontendBlock', () => {
	it( 'Renders the default Terms and Privacy links without a checkbox', () => {
		render(
			<SlotFillProvider>
				<FrontendBlock
					checkbox={ false }
					text=""
					showSeparator={ false }
				/>
			</SlotFillProvider>
		);

		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'SPAN' &&
					element.textContent ===
						'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy'
			)
		).toBeVisible();
		expect(
			screen.getByRole( 'link', { name: 'Terms and Conditions' } )
		).toHaveAttribute( 'href', 'https://example.com/terms/' );
		expect(
			screen.getByRole( 'link', { name: 'Privacy Policy' } )
		).toHaveAttribute( 'href', 'https://example.com/privacy/' );
		expect( screen.queryByRole( 'checkbox' ) ).not.toBeInTheDocument();
	} );

	it( 'Renders a checkbox if the checkbox prop is true', () => {
		const { container } = render(
			<SlotFillProvider>
				<FrontendBlock
					checkbox={ true }
					text={ 'I agree to the terms and conditions' }
					showSeparator={ false }
				/>
			</SlotFillProvider>
		);

		const checkbox = queryByLabelText(
			container,
			'I agree to the terms and conditions'
		);

		expect( checkbox ).toBeInTheDocument();
	} );

	it( 'Does not render a checkbox if the checkbox prop is false', async () => {
		const { container } = render(
			<SlotFillProvider>
				<FrontendBlock
					checkbox={ false }
					text={ 'I agree to the terms and conditions' }
					showSeparator={ false }
				/>
			</SlotFillProvider>
		);

		const checkbox = queryByLabelText(
			container,
			'I agree to the terms and conditions'
		);

		expect( checkbox ).not.toBeInTheDocument();
	} );

	it( 'Clears any validation errors when the checkbox is checked', async () => {
		const user = userEvent.setup();
		actionCreators.clearValidationError.mockClear();
		const { container } = render(
			<SlotFillProvider>
				<FrontendBlock
					checkbox={ true }
					text={ 'I agree to the terms and conditions' }
					showSeparator={ false }
				/>
			</SlotFillProvider>
		);
		const checkbox = await findByLabelText(
			container,
			'I agree to the terms and conditions'
		);
		await act( async () => {
			await user.click( checkbox );
		} );
		expect( actionCreators.clearValidationError ).toHaveBeenCalledTimes(
			2
		);
		expect( actionCreators.clearValidationError ).toHaveBeenNthCalledWith(
			1,
			expect.stringMatching( /terms-and-conditions-\d/ )
		);
		expect( actionCreators.clearValidationError ).toHaveBeenNthCalledWith(
			2,
			expect.stringMatching( /terms-and-conditions-\d/ )
		);
	} );

	it( 'Renders and describes the validation error when the checkbox is required and unchecked', async () => {
		const { container } = render(
			<SlotFillProvider>
				<FrontendBlock
					checkbox={ true }
					text={ 'I agree to the terms and conditions' }
					showSeparator={ false }
				/>
			</SlotFillProvider>
		);
		const checkbox = await findByLabelText(
			container,
			'I agree to the terms and conditions'
		);

		await act( async () => {
			dispatch( validationStore ).showAllValidationErrors();
		} );

		const errorMessage = await screen.findByText(
			'Please read and accept the terms and conditions.'
		);

		await waitFor( () => {
			expect( checkbox ).toHaveAttribute(
				'aria-describedby',
				errorMessage.closest( 'p' ).id
			);
		} );
	} );
} );
