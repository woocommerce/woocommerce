/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import { parseVisibility } from './adapter';

describe( 'parseVisibility', () => {
	const buildItem = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 1,
			manage_stock: false,
			downloadable: false,
			virtual: false,
			...overrides,
		} as unknown as ProductEntityRecord );

	it( 'returns undefined when no patterns match', () => {
		expect( parseVisibility( 'some-random-class' ) ).toBeUndefined();
	} );

	it( 'returns undefined for empty string', () => {
		expect( parseVisibility( '' ) ).toBeUndefined();
	} );

	it( 'handles single show_if_variation_manage_stock', () => {
		const check = parseVisibility( 'show_if_variation_manage_stock' );
		expect( check ).toBeDefined();
		expect( check!( buildItem( { manage_stock: true } ) ) ).toBe( true );
		expect( check!( buildItem( { manage_stock: false } ) ) ).toBe(
			false
		);
	} );

	it( 'handles single hide_if_variation_virtual', () => {
		const check = parseVisibility( 'hide_if_variation_virtual' );
		expect( check ).toBeDefined();
		expect( check!( buildItem( { virtual: false } ) ) ).toBe( true );
		expect( check!( buildItem( { virtual: true } ) ) ).toBe( false );
	} );

	it( 'ANDs compound classes: show_if_manage_stock AND hide_if_virtual', () => {
		const check = parseVisibility(
			'show_if_variation_manage_stock hide_if_variation_virtual'
		);
		expect( check ).toBeDefined();

		expect(
			check!( buildItem( { manage_stock: true, virtual: false } ) )
		).toBe( true );

		expect(
			check!( buildItem( { manage_stock: true, virtual: true } ) )
		).toBe( false );

		expect(
			check!( buildItem( { manage_stock: false, virtual: false } ) )
		).toBe( false );

		expect(
			check!( buildItem( { manage_stock: false, virtual: true } ) )
		).toBe( false );
	} );
} );
