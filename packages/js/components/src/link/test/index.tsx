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
			<Link href="https://woo.com" type="external">
				Woo.com
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  data-link-type="external"
			  href="https://woo.com"
			>
			  Woo.com
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
				href="https://woo.com"
				type="external"
				className="foo"
				target="bar"
			>
				Woo.com
			</Link>
		);

		expect( container.firstChild ).toMatchInlineSnapshot( `
			<a
			  class="foo"
			  data-link-type="external"
			  href="https://woo.com"
			  target="bar"
			>
			  Woo.com
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
				href="https://woo.com"
				type="external"
				onClick={ clickHandler }
			>
				Woo.com
			</Link>
		);

		const testLink = screen.getByText( 'Woo.com' );

		fireEvent.click( testLink );

		expect( clickHandler ).toHaveBeenCalled();
	} );
} );
