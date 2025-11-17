/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ProductDetails from '..';

describe( 'ProductDetails', () => {
	test( 'should render details', () => {
		const details = [
			{ name: 'Lorem', value: 'Ipsum' },
			{ name: 'LOREM', value: 'Ipsum', display: 'IPSUM' },
			{ value: 'Ipsum' },
		];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should render as ul since there are multiple details
		const list = container.querySelector(
			'ul.wc-block-components-product-details'
		);
		expect( list ).toBeInTheDocument();

		// Should have 3 list items
		const listItems = container.querySelectorAll( 'li' );
		expect( listItems ).toHaveLength( 3 );

		// First item should have name and value
		expect( screen.getByText( 'Lorem:' ) ).toBeInTheDocument();
		expect( screen.getAllByText( 'Ipsum' ) ).toHaveLength( 2 ); // First and third items

		// Second item should use display instead of value
		expect( screen.getByText( 'LOREM:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'IPSUM' ) ).toBeInTheDocument();

		// Third item should only have value (no name)
		const thirdItem = listItems[ 2 ];
		expect(
			thirdItem.querySelector(
				'.wc-block-components-product-details__name'
			)
		).toBeNull();
		expect(
			thirdItem.querySelector(
				'.wc-block-components-product-details__value'
			)
		).toBeInTheDocument();
	} );

	test( 'should not render hidden details', () => {
		const details = [
			{ name: 'Lorem', value: 'Ipsum', hidden: true },
			{ name: 'LOREM', value: 'Ipsum', display: 'IPSUM' },
			{ name: 'LOREM 2', value: 'Ipsum2', display: 'IPSUM 2' },
		];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should render as ul since there are multiple visible details
		const list = container.querySelector(
			'ul.wc-block-components-product-details'
		);
		expect( list ).toBeInTheDocument();

		// Should only have 2 items (hidden one filtered out)
		const listItems = container.querySelectorAll( 'li' );
		expect( listItems ).toHaveLength( 2 );

		// Hidden item should not be rendered
		expect( screen.queryByText( 'Lorem' ) ).not.toBeInTheDocument();

		// Visible items should be rendered
		expect( screen.getByText( 'LOREM:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'IPSUM' ) ).toBeInTheDocument();
		expect( screen.getByText( 'LOREM 2:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'IPSUM 2' ) ).toBeInTheDocument();
	} );

	test( 'should not render anything if all details are hidden', () => {
		const details = [
			{ name: 'Lorem', value: 'Ipsum', hidden: true },
			{ name: 'LOREM', value: 'Ipsum', display: 'IPSUM', hidden: true },
		];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should not render any product details container
		expect(
			container.querySelector( '.wc-block-components-product-details' )
		).not.toBeInTheDocument();
		expect( container.firstChild ).toBeNull();
	} );

	test( 'should not render anything if details is an empty array', () => {
		const details = [];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should not render any product details container
		expect(
			container.querySelector( '.wc-block-components-product-details' )
		).not.toBeInTheDocument();
		expect( container.firstChild ).toBeNull();
	} );

	test( 'should not render list if there is only one detail', () => {
		const details = [ { name: 'LOREM', value: 'Ipsum', display: 'IPSUM' } ];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should render as div (not ul) since there's only one detail
		const div = container.querySelector(
			'div.wc-block-components-product-details'
		);
		expect( div ).toBeInTheDocument();

		// Should not render as ul
		const list = container.querySelector(
			'ul.wc-block-components-product-details'
		);
		expect( list ).not.toBeInTheDocument();

		// Should have one child div
		const childDivs = container.querySelectorAll(
			'div.wc-block-components-product-details > div'
		);
		expect( childDivs ).toHaveLength( 1 );

		// Should contain name and value
		expect( screen.getByText( 'LOREM:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'IPSUM' ) ).toBeInTheDocument();

		// Should have proper CSS classes
		expect(
			container.querySelector(
				'.wc-block-components-product-details__name'
			)
		).toBeInTheDocument();
		expect(
			container.querySelector(
				'.wc-block-components-product-details__value'
			)
		).toBeInTheDocument();
	} );

	test( 'should handle details with key property instead of name', () => {
		const details = [
			{ key: 'Color', value: 'Red' },
			{ key: 'Size', value: 'Large', display: 'L' },
		];

		const { container } = render( <ProductDetails details={ details } /> );

		const listItems = container.querySelectorAll( 'li' );
		expect( listItems[ 0 ].textContent ).toBe( 'Color: Red' );
		expect( listItems[ 1 ].textContent ).toBe( 'Size: L' );
	} );

	test( 'should apply correct CSS classes', () => {
		const details = [ { name: 'Test <b>Name</b>', value: 'Test Value' } ];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should have kebab-case class name
		expect(
			container.querySelector(
				'.wc-block-components-product-details__test-name'
			)
		).toBeInTheDocument();

		// Should have name and value spans with correct classes
		expect(
			container.querySelector(
				'.wc-block-components-product-details__name'
			)
		).toBeInTheDocument();
		expect(
			container.querySelector(
				'.wc-block-components-product-details__value'
			)
		).toBeInTheDocument();
	} );

	test( 'should sanitize and render HTML content in name and value', () => {
		const details = [
			{
				name: 'Your <b>Gift Message</b>',
				value: '<a href="https://www.woocommerce.com" target="_blank" rel="noopener noreferrer" extra="do not display">Click & see</a>',
			},
		];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should render HTML in name (bold tag should be preserved)
		const nameSpan = container.querySelector(
			'.wc-block-components-product-details__name'
		);
		expect( nameSpan ).toBeInTheDocument();
		expect( nameSpan.querySelector( 'b' ) ).toBeInTheDocument();
		expect( nameSpan.textContent ).toBe( 'Your Gift Message:' );

		// Should render HTML in value (link should be preserved with allowed attributes)
		const valueSpan = container.querySelector(
			'.wc-block-components-product-details__value'
		);
		expect( valueSpan ).toBeInTheDocument();

		const link = valueSpan.querySelector( 'a' );
		expect( link ).toBeInTheDocument();
		expect( link ).toHaveAttribute( 'href', 'https://www.woocommerce.com' );
		expect( link ).toHaveAttribute( 'target', '_blank' );
		expect( link ).toHaveAttribute( 'rel', 'noopener noreferrer' );
		// Should not have the 'extra' attribute as it's not in allowed attributes
		expect( link ).not.toHaveAttribute( 'extra' );
		expect( link.textContent ).toBe( 'Click & see' );

		// Should have proper CSS class based on name with HTML tags stripped
		expect(
			container.querySelector(
				'.wc-block-components-product-details__your-gift-message'
			)
		).toBeInTheDocument();
	} );
} );
