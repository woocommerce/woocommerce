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
	it( 'Renders a checkbox if the checkbox prop is true', async () => {
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
		expect( actionCreators.clearValidationError ).toHaveBeenLastCalledWith(
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

	it.each( [ true, false ] )(
		'Strips scripts but keeps formatting in the terms text (checkbox: %s)',
		( checkbox ) => {
			const { container } = render(
				<SlotFillProvider>
					<FrontendBlock
						checkbox={ checkbox }
						text={
							'I agree to the <a href="https://example.com/terms" target="_blank">terms</a> and <strong>policy</strong><img src="x" onerror="alert(1)"><script>alert(2)</script>'
						}
						showSeparator={ false }
					/>
				</SlotFillProvider>
			);

			const label = container.querySelector(
				'.wc-block-components-checkbox__label'
			);

			expect( label.querySelector( 'img' ) ).toBeNull();
			expect( label.querySelector( 'script' ) ).toBeNull();
			expect( label.querySelector( '[onerror]' ) ).toBeNull();
			expect( label.querySelector( 'strong' ) ).toHaveTextContent(
				'policy'
			);
			const link = label.querySelector( 'a' );
			expect( link ).toHaveAttribute(
				'href',
				'https://example.com/terms'
			);
			expect( link ).toHaveAttribute( 'target', '_blank' );
		}
	);
} );
