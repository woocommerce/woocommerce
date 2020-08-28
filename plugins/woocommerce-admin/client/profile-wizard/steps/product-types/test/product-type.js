/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { shallow } from 'enzyme';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ProductType from '../product-type';

const defaultProps = {
	annualPrice: 120,
	label: 'Product type label',
	description: 'Product type description',
	moreUrl: 'https://woocommerce.com/my-product-type',
	slug: 'my-product-type',
};

describe( 'ProductType', () => {
	test( 'should render the product type', () => {
		const productType = shallow(
			<ProductType { ...defaultProps } isMonthlyPricing={ false } />
		);
		expect( productType ).toMatchSnapshot();
	} );

	test( 'should render the product type with monthly prices', () => {
		const productType = shallow(
			<ProductType { ...defaultProps } isMonthlyPricing={ true } />
		);
		expect( productType ).toMatchSnapshot();
	} );

	test( 'should show Popover on click', () => {
		const { container } = render(
			<ProductType { ...defaultProps } isMonthlyPricing={ true } />
		);

		const infoButton = screen.getByLabelText(
			'Learn more about recommended free business features',
			{
				selector: 'button',
			}
		);

		userEvent.click( infoButton );

		const popover = container.querySelector( '.components-popover' );
		const learnMoreLink = popover.querySelector( 'a' );
		expect( popover ).not.toBeNull();
		expect( popover.textContent ).toBe(
			defaultProps.description + ' Learn more'
		);
		expect( learnMoreLink.href ).toBe( defaultProps.moreUrl );
	} );
} );
