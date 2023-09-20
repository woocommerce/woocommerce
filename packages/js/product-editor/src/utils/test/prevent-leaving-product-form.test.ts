/**
 * External dependencies
 */
import { Location } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { preventLeavingProductForm } from '../prevent-leaving-product-form';

describe( 'preventLeavingProductForm', () => {
	it( 'should allow leaving when the paths are identical', () => {
		const toUrl = new URL(
			'http://mysite.com/admin.php?page=wc-admin&path=/product/123&tab=general'
		);
		const fromUrl = {
			search: 'admin.php?page=wc-admin&path=/product/123&tab=general',
		} as Location;
		const shouldPrevent = preventLeavingProductForm()( toUrl, fromUrl );
		expect( shouldPrevent ).toBe( true );
	} );

	it( 'should prevent leaving when the paths are different', () => {
		const toUrl = new URL(
			'http://mysite.com/admin.php?page=wc-admin&path=/product/456&tab=general'
		);
		const fromUrl = {
			search: 'admin.php?page=wc-admin&path=/product/123&tab=general',
		} as Location;
		const shouldPrevent = preventLeavingProductForm()( toUrl, fromUrl );
		expect( shouldPrevent ).toBe( true );
	} );

	it( 'should allow leaving when the paths are the same but the tab is different', () => {
		const toUrl = new URL(
			'http://mysite.com/admin.php?page=wc-admin&path=/product/123&tab=general'
		);
		const fromUrl = {
			search: 'admin.php?page=wc-admin&path=/product/123&tab=shipping',
		} as Location;
		const shouldPrevent = preventLeavingProductForm()( toUrl, fromUrl );
		expect( shouldPrevent ).toBe( true );
	} );

	it( 'should allow leaving when moving from the add-product to the edit page with same product id', () => {
		const toUrl = new URL(
			'http://mysite.com/admin.php?page=wc-admin&path=/product/123&tab=general'
		);
		const fromUrl = {
			search: 'admin.php?page=wc-admin&path=/add-product',
		} as Location;
		const shouldPrevent = preventLeavingProductForm( 123 )(
			toUrl,
			fromUrl
		);
		expect( shouldPrevent ).toBe( false );
	} );

	it( 'should not allow leaving when moving from the add-product to the edit page with different product id', () => {
		const toUrl = new URL(
			'http://mysite.com/admin.php?page=wc-admin&path=/product/123&tab=general'
		);
		const fromUrl = {
			search: 'admin.php?page=wc-admin&path=/add-product',
		} as Location;
		const shouldPrevent = preventLeavingProductForm( 333 )(
			toUrl,
			fromUrl
		);
		expect( shouldPrevent ).toBe( true );
	} );

	it( 'should prevent leaving when non-tab params are different', () => {
		const toUrl = new URL(
			'http://mysite.com/admin.php?page=wc-admin&path=/product/123&tab=general&other_param=a'
		);
		const fromUrl = {
			search: 'admin.php?page=wc-admin&path=/product/123&tab=shipping&other_param=b',
		} as Location;
		const shouldPrevent = preventLeavingProductForm()( toUrl, fromUrl );
		expect( shouldPrevent ).toBe( true );
	} );
} );
