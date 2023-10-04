/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Link } from '..';

describe( 'Link', () => {
	it( 'should render `external` links', () => {
		const { container } = render(
			<Link href="https://woocommerce.com" type="external">
				WooCommerce.com
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  class="components-external-link"
			  data-link-type="external"
			  href="https://woocommerce.com"
			  rel="external noreferrer noopener"
			  target="_blank"
			>
			  WooCommerce.com
			  <span
			    class="components-visually-hidden css-1mm2cvy-View em57xhy0"
			    data-wp-c16t="true"
			    data-wp-component="VisuallyHidden"
			    style="border: 0px; clip: rect(1px, 1px, 1px, 1px); clip-path: inset( 50% ); height: 1px; margin: -1px; overflow: hidden; padding: 0px; position: absolute; width: 1px; word-wrap: normal;"
			  >
			    (opens in a new tab)
			  </span>
			  <svg
			    aria-hidden="true"
			    class="components-external-link__icon css-16iaek2-StyledIcon etxm6pv0"
			    focusable="false"
			    height="24"
			    viewBox="0 0 24 24"
			    width="24"
			    xmlns="http://www.w3.org/2000/svg"
			  >
			    <path
			      d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
			    />
			  </svg>
			</a>
		` );
	} );

	it( 'should render `wp-admin` links', () => {
		const { container } = render(
			<Link href="post-new.php?post_type=product" type="wp-admin">
				New Post
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  data-link-type="wp-admin"
			  href="post-new.php?post_type=product"
			>
			  New Post
			</a>
		` );
	} );

	it( 'should render `wc-admin` links', () => {
		const { container } = render(
			<Link
				href="admin.php?page=wc-admin&path=%2Fanalytics%2Forders"
				type="wc-admin"
			>
				Analytics: Orders
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  data-link-type="wc-admin"
			  href="admin.php?page=wc-admin&path=%2Fanalytics%2Forders"
			>
			  Analytics: Orders
			</a>
		` );
	} );

	it( 'should render links without a type as `wc-admin`', () => {
		const { container } = render(
			<Link href="admin.php?page=wc-admin&path=%2Fanalytics%2Forders">
				Analytics: Orders
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  data-link-type="wc-admin"
			  href="admin.php?page=wc-admin&path=%2Fanalytics%2Forders"
			>
			  Analytics: Orders
			</a>
		` );
	} );

	it( 'should allow custom props to be passed through', () => {
		const { container } = render(
			<Link
				href="https://woocommerce.com"
				type="external"
				className="foo"
				title="bar"
			>
				WooCommerce.com
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  class="components-external-link foo"
			  data-link-type="external"
			  href="https://woocommerce.com"
			  rel="external noreferrer noopener"
			  target="_blank"
			  title="bar"
			>
			  WooCommerce.com
			  <span
			    class="components-visually-hidden css-1mm2cvy-View em57xhy0"
			    data-wp-c16t="true"
			    data-wp-component="VisuallyHidden"
			    style="border: 0px; clip: rect(1px, 1px, 1px, 1px); clip-path: inset( 50% ); height: 1px; margin: -1px; overflow: hidden; padding: 0px; position: absolute; width: 1px; word-wrap: normal;"
			  >
			    (opens in a new tab)
			  </span>
			  <svg
			    aria-hidden="true"
			    class="components-external-link__icon css-16iaek2-StyledIcon etxm6pv0"
			    focusable="false"
			    height="24"
			    viewBox="0 0 24 24"
			    width="24"
			    xmlns="http://www.w3.org/2000/svg"
			  >
			    <path
			      d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
			    />
			  </svg>
			</a>
		` );
	} );

	it( 'should support `onClick`', () => {
		// Prevent jsdom "Error: Not implemented: navigation" in test output
		const clickHandler = jest.fn( ( event ) => {
			event.preventDefault();
			return false;
		} );

		render(
			<Link
				href="https://woocommerce.com"
				type="external"
				onClick={ clickHandler }
			>
				WooCommerce.com
			</Link>
		);

		const testLink = screen.getByText( 'WooCommerce.com' );

		fireEvent.click( testLink );

		expect( clickHandler ).toHaveBeenCalled();
	} );
} );
