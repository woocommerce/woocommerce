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
	it( 'should render `external` links using WordPress ExternalLink', () => {
		render(
			<Link href="https://woocommerce.com" type="external">
				WooCommerce.com
			</Link>
		);

		const testLink = screen.getByRole( 'link', {
			name: 'WooCommerce.com (opens in a new tab)',
		} );

		expect( testLink.className ).toContain( 'components-external-link' );
		expect( testLink.getAttribute( 'data-link-type' ) ).toBe( 'external' );
		expect( testLink.getAttribute( 'href' ) ).toBe(
			'https://woocommerce.com'
		);
		expect( testLink.getAttribute( 'target' ) ).toBe( '_blank' );
		expect( testLink.getAttribute( 'rel' ) ).toContain( 'noopener' );
		expect( testLink.getAttribute( 'rel' ) ).toContain( 'noreferrer' );
		expect(
			testLink.querySelector( '.components-external-link__icon' )
		).not.toBeNull();
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
		render(
			<Link
				href="https://woocommerce.com"
				type="external"
				className="foo"
				title="bar"
			>
				WooCommerce.com
			</Link>
		);

		const testLink = screen.getByRole( 'link', {
			name: 'WooCommerce.com (opens in a new tab)',
		} );

		expect( testLink.className ).toContain( 'components-external-link' );
		expect( testLink.className ).toContain( 'foo' );
		expect( testLink.getAttribute( 'data-link-type' ) ).toBe( 'external' );
		expect( testLink.getAttribute( 'title' ) ).toBe( 'bar' );
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
