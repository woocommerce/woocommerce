/**
 * Internal dependencies
 */
import * as publicApi from '../index';

describe( '@woocommerce/modern-settings-sdk public exports', () => {
	it( 'exports ReactSettingsPage component', () => {
		expect( publicApi.ReactSettingsPage ).toBeDefined();
		expect( typeof publicApi.ReactSettingsPage ).toBe( 'function' );
	} );

	it( 'exports useReactSettings hook', () => {
		expect( publicApi.useReactSettings ).toBeDefined();
		expect( typeof publicApi.useReactSettings ).toBe( 'function' );
	} );

	it( 'exports ErrorBoundary component', () => {
		expect( publicApi.ErrorBoundary ).toBeDefined();
		expect( typeof publicApi.ErrorBoundary ).toBe( 'function' );
	} );

	it.each( [
		'baseFieldTransformer',
		'registerFieldTypeTransformer',
		'getFieldTypeTransformer',
		'parseOptions',
		'createChildrenWithRows',
		'reorderGroupFields',
		'hideEmptyLabel',
	] as const )( 'exports %s helper as a function', ( name ) => {
		const value = ( publicApi as Record< string, unknown > )[ name ];
		expect( value ).toBeDefined();
		expect( typeof value ).toBe( 'function' );
	} );
} );
