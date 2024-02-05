/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductType from '../product-type';

const defaultProps = {
	annualPrice: 120,
	label: 'Product type label',
	description: 'Product type description',
	moreUrl: 'https://woo.com/my-product-type',
	slug: 'my-product-type',
};

describe( 'ProductType', () => {
	test( 'should render the product type', () => {
		const { container } = render(
			<ProductType { ...defaultProps } isMonthlyPricing={ false } />
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render the product type with monthly prices', () => {
		const { container } = render(
			<ProductType { ...defaultProps } isMonthlyPricing={ true } />
		);
		expect( container ).toMatchSnapshot();
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
