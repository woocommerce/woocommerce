/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { ProductTypes } from '../';

const testProps = {
	countryCode: 'US',
	invalidateResolution: jest.fn(),
	isProductTypesRequesting: false,
	productTypes: {
		paidProduct: {
			description: 'Paid product type',
			label: 'Paid product',
			more_url: 'https://woo.com/paid-product',
			product: 100,
			slug: 'paid-product',
			yearly_price: 120,
		},
		freeProduct: {
			label: 'Free product',
		},
	},
};

describe( 'ProductTypes', () => {
	afterEach( () => {
		window.wcAdminFeatures.subscriptions = false;
	} );

	test( 'should render product types', () => {
		const { container } = render( <ProductTypes { ...testProps } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should show annual prices on toggle', () => {
		const { container } = render( <ProductTypes { ...testProps } /> );

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
				{ ...testProps }
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
		} );
		expect( mockUpdateProfileItems ).not.toHaveBeenCalled();

		// Click on a product type to pass validation.
		userEvent.click( productType );
		userEvent.click( continueButton );
		await waitFor( () => {
			expect( mockUpdateProfileItems ).toHaveBeenCalled();
		} );
		expect( mockGoToNextStep ).toHaveBeenCalled();
	} );
	test( 'should show a warning message at the bottom of the step', () => {
		const productTypes = {
			subscriptions: {
				label: 'Subscriptions',
			},
		};
		window.wcAdminFeatures.subscriptions = true;

		render(
			<ProductTypes { ...testProps } productTypes={ productTypes } />
		);

		const subscription = screen.getByText( 'Subscriptions', {
			selector: 'label',
		} );
		userEvent.click( subscription );

		expect(
			screen.queryByText(
				'The following extensions will be added to your site for free: WooPayments. An account is required to use this feature.'
			)
		).toBeInTheDocument();
	} );
	test( 'should show the warning message only for US stores', () => {
		const productTypes = {
			subscriptions: {
				label: 'Subscriptions',
			},
		};
		const countryCode = 'FR';
		window.wcAdminFeatures.subscriptions = true;

		render(
			<ProductTypes
				{ ...testProps }
				productTypes={ productTypes }
				countryCode={ countryCode }
			/>
		);

		const subscription = screen.getByText( 'Subscriptions', {
			selector: 'label',
		} );
		userEvent.click( subscription );

		expect(
			screen.queryByText(
				'The following extensions will be added to your site for free: WooPayments. An account is required to use this feature.'
			)
		).not.toBeInTheDocument();
	} );
} );
