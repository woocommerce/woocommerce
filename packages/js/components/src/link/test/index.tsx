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
			  data-link-type="external"
			  href="https://woocommerce.com"
			>
			  WooCommerce.com
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
				target="bar"
			>
				WooCommerce.com
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  class="foo"
			  data-link-type="external"
			  href="https://woocommerce.com"
			  target="bar"
			>
			  WooCommerce.com
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
