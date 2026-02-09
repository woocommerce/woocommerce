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

		// Should render as div
		const wrapper = container.querySelector(
			'div.wc-block-components-product-details'
		);
		expect( wrapper ).toBeInTheDocument();

		// Should have 3 span items
		const items = container.querySelectorAll(
			'.wc-block-components-product-details > span'
		);
		expect( items ).toHaveLength( 3 );

		// First item should have name and value
		expect( screen.getByText( 'Lorem:' ) ).toBeInTheDocument();
		expect( screen.getAllByText( 'Ipsum' ) ).toHaveLength( 2 ); // First and third items

		// Second item should use display instead of value
		expect( screen.getByText( 'LOREM:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'IPSUM' ) ).toBeInTheDocument();

		// Third item should only have value (no name)
		const thirdItem = items[ 2 ];
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

		// Should render as div
		const wrapper = container.querySelector(
			'div.wc-block-components-product-details'
		);
		expect( wrapper ).toBeInTheDocument();

		// Should only have 2 items (hidden one filtered out)
		const items = container.querySelectorAll(
			'.wc-block-components-product-details > span'
		);
		expect( items ).toHaveLength( 2 );

		// Hidden item should not be rendered
		expect( screen.queryByText( 'Lorem:' ) ).not.toBeInTheDocument();

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

	test( 'should render separators between multiple details', () => {
		const details = [
			{ name: 'Color', value: 'Red' },
			{ name: 'Size', value: 'Large' },
			{ name: 'Material', value: 'Cotton' },
		];

		const { container } = render( <ProductDetails details={ details } /> );

		const wrapper = container.querySelector(
			'div.wc-block-components-product-details'
		);

		// Should have separators between items but not after last
		expect( wrapper.textContent ).toBe(
			'Color: Red / Size: Large / Material: Cotton'
		);

		// Separators should be hidden from screen readers
		const separators = container.querySelectorAll( '[aria-hidden="true"]' );
		expect( separators ).toHaveLength( 2 );
	} );

	test( 'should render single detail without separator', () => {
		const details = [ { name: 'LOREM', value: 'Ipsum', display: 'IPSUM' } ];

		const { container } = render( <ProductDetails details={ details } /> );

		// Should render as div
		const wrapper = container.querySelector(
			'div.wc-block-components-product-details'
		);
		expect( wrapper ).toBeInTheDocument();

		// Should have one span item
		const items = container.querySelectorAll(
			'.wc-block-components-product-details > span'
		);
		expect( items ).toHaveLength( 1 );

		// Should contain name and value
		expect( screen.getByText( 'LOREM:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'IPSUM' ) ).toBeInTheDocument();

		// Should not have separator (single item)
		expect( items[ 0 ].textContent ).toBe( 'LOREM: IPSUM' );

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

		const items = container.querySelectorAll(
			'.wc-block-components-product-details > span'
		);
		// First item has separator, last item does not
		expect( items[ 0 ].textContent ).toBe( 'Color: Red / ' );
		expect( items[ 1 ].textContent ).toBe( 'Size: L' );
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
