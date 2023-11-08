/**
 * Internal dependencies
 */
import { getDerivedProductType } from '../get-derived-product-type';

describe( 'getDerivedProductType', () => {
	it( 'should be simple when no attributes exist', () => {
		const type = getDerivedProductType( {
			id: 123,
			attributes: [],
		} );
		expect( type ).toBe( 'simple' );
	} );

	it( 'should be simple when no attributes used for variations exist', () => {
		const type = getDerivedProductType( {
			id: 123,
			attributes: [
				{
					id: 0,
					name: 'Color',
					options: [ 'Red', 'Blue' ],
					position: 0,
					variation: false,
					visible: true,
					slug: 'color',
				},
			],
		} );
		expect( type ).toBe( 'simple' );
	} );

	it( 'should be simple when no options exist for a variation', () => {
		const type = getDerivedProductType( {
			id: 123,
			attributes: [
				{
					id: 0,
					name: 'Color',
					options: [],
					position: 0,
					variation: true,
					visible: true,
					slug: 'color',
				},
			],
		} );
		expect( type ).toBe( 'simple' );
	} );

	it( 'should be variable when at least one attribute can be used for variations', () => {
		const type = getDerivedProductType( {
			id: 123,
			attributes: [
				{
					id: 0,
					name: 'Size',
					options: [ 'Small', 'Medium' ],
					position: 0,
					variation: false,
					visible: true,
					slug: 'size',
				},
				{
					id: 0,
					name: 'Color',
					options: [ 'Red', 'Blue' ],
					position: 1,
					variation: true,
					visible: true,
					slug: 'color',
				},
			],
		} );
		expect( type ).toBe( 'variable' );
	} );
} );
