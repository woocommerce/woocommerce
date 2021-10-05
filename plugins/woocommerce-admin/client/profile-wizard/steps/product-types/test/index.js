/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import { setSetting } from '@woocommerce/wc-admin-settings';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTypes } from '../';

describe( 'ProductTypes', () => {
	beforeEach( () => {
		setSetting( 'onboarding', {
			productTypes: {
				paidProduct: {
					description: 'Paid product type',
					label: 'Paid product',
					more_url: 'https://woocommerce.com/paid-product',
					product: 100,
					slug: 'paid-product',
					yearly_price: 120,
				},
				freeProduct: {
					label: 'Free product',
				},
			},
		} );
	} );

	afterEach( () => {
		setSetting( 'onboarding', {} );
		window.wcAdminFeatures.subscriptions = false;
	} );

	test( 'should render product types', () => {
		const { container } = render( <ProductTypes /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should show annual prices on toggle', () => {
		const { container } = render( <ProductTypes /> );

		const toggle = screen.getByLabelText( 'Display monthly prices', {
			selector: 'input',
		} );

		userEvent.click( toggle );

		expect( container ).toMatchSnapshot();
	} );

	test( 'should validate on continue', async () => {
		const mockCreateNotice = jest.fn();
		const mockGoToNextStep = jest.fn();
		const mockUpdateProfileItems = jest.fn().mockResolvedValue();

		render(
			<ProductTypes
				createNotice={ mockCreateNotice }
				goToNextStep={ mockGoToNextStep }
				updateProfileItems={ mockUpdateProfileItems }
			/>
		);

		const continueButton = screen.getByText( 'Continue', {
			selector: 'button',
		} );
		const productType = screen.getByText( 'Free product', {
			selector: 'label',
		} );

		// Validation should fail since no product types are selected.
		userEvent.click( continueButton );
		await waitFor( () => {
			expect( mockGoToNextStep ).not.toHaveBeenCalled();
			expect( mockUpdateProfileItems ).not.toHaveBeenCalled();
		} );

		// Click on a product type to pass validation.
		userEvent.click( productType );
		userEvent.click( continueButton );
		await waitFor( () => {
			expect( mockUpdateProfileItems ).toHaveBeenCalled();
			expect( mockGoToNextStep ).toHaveBeenCalled();
		} );
	} );
	test( 'should show a warning message at the bottom of the step', () => {
		setSetting( 'onboarding', {
			productTypes: {
				subscriptions: {
					label: 'Subscriptions',
				},
			},
		} );
		window.wcAdminFeatures.subscriptions = true;

		render( <ProductTypes /> );

		const subscription = screen.getByText( 'Subscriptions', {
			selector: 'label',
		} );
		userEvent.click( subscription );

		expect(
			screen.queryByText(
				'The following extensions will be added to your site for free: WooCommerce Payments. An account is required to use this feature.'
			)
		).toBeInTheDocument();
	} );
} );
