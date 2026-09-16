/**
 * Internal dependencies
 */
import { getProductCollectionQueryState } from '../get-product-collection-query-state';

const localProductCollectionQuery = {
	isProductCollectionBlock: true,
	inherit: false,
};

describe( 'getProductCollectionQueryState', () => {
	it.each( [
		undefined,
		null,
		'query',
		[],
		{},
		{ inherit: false },
		{ isProductCollectionBlock: true },
		{ isProductCollectionBlock: false, inherit: false },
		{ isProductCollectionBlock: true, inherit: true },
		{ postType: 'product', inherit: false },
	] )( 'returns null for an unrecognized query context', ( query ) => {
		expect( getProductCollectionQueryState( query ) ).toBeNull();
	} );

	it.each( [
		localProductCollectionQuery,
		{
			...localProductCollectionQuery,
			search: 'example',
			featured: false,
			orderBy: 'date',
		},
		{
			...localProductCollectionQuery,
			taxQuery: [],
			woocommerceAttributes: {},
		},
	] )(
		'returns an empty query state when no taxonomy boundary is safely representable',
		( query ) => {
			expect( getProductCollectionQueryState( query ) ).toStrictEqual(
				{}
			);
		}
	);

	it( 'maps built-in product taxonomies to their Store API parameters', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				product_cat: [ 8, 2, 8 ],
				product_tag: [ 5 ],
				product_brand: [ 13 ],
			},
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			brand: '13',
			category: '2,8',
			tag: '5',
			'taxonomy_include_children[product_brand]': 'false',
			'taxonomy_include_children[product_cat]': 'false',
			'taxonomy_include_children[product_tag]': 'false',
		} );
	} );

	it( 'maps custom taxonomies to unstable Store API taxonomy parameters', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				product_material: [ 21, 3 ],
			},
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_product_material: '3,21',
			'taxonomy_include_children[product_material]': 'false',
		} );
	} );

	it( 'ignores Product Collection taxQuery exclusions while retaining unrelated taxonomies', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				product_visibility: [ 2 ],
				product_shipping_class: [ 3 ],
				product_material: [ 4 ],
			},
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_product_material: '4',
			'taxonomy_include_children[product_material]': 'false',
		} );
	} );

	it( 'retains Product Collection taxQuery exclusions when supplied as attributes', () => {
		const query = {
			...localProductCollectionQuery,
			woocommerceAttributes: [
				{ taxonomy: 'product_visibility', termId: 2 },
				{ taxonomy: 'product_shipping_class', termId: 3 },
			],
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_product_shipping_class: '3',
			_unstable_tax_product_visibility: '2',
		} );
	} );

	it( 'ignores empty taxonomies, malformed sources, and invalid term IDs', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				'': [ 4 ],
				product_cat: [ -1, 0, 1.5, '2', null, 7 ],
				product_tag: '8',
			},
			woocommerceAttributes: [
				null,
				{ termId: 2 },
				{ taxonomy: '', termId: 3 },
				{ taxonomy: 'pa_color', termId: -5 },
				{ taxonomy: 'pa_color', termId: 2.5 },
				{ taxonomy: 'pa_color', termId: '6' },
				{ taxonomy: 'pa_color', termId: 9 },
			],
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_pa_color: '9',
			category: '7',
			'taxonomy_include_children[product_cat]': 'false',
		} );
	} );

	it( 'ignores invalid taxQuery taxonomy slugs while retaining valid lowercase slugs', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				'   ': [ 2 ],
				'product material': [ 3 ],
				'product/material': [ 4 ],
				Product_Material: [ 5 ],
				'product-material_2': [ 6 ],
			},
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			'_unstable_tax_product-material_2': '6',
			'taxonomy_include_children[product-material_2]': 'false',
		} );
	} );

	it( 'ignores invalid attribute taxonomy slugs while retaining valid lowercase slugs', () => {
		const query = {
			...localProductCollectionQuery,
			woocommerceAttributes: [
				{ taxonomy: '   ', termId: 7 },
				{ taxonomy: 'pa color', termId: 8 },
				{ taxonomy: 'pa/color', termId: 9 },
				{ taxonomy: 'Pa_Color', termId: 10 },
				{ taxonomy: 'pa_color-2', termId: 11 },
			],
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			'_unstable_tax_pa_color-2': '11',
		} );
	} );

	it( 'groups repeated attribute entries and sorts unique term IDs', () => {
		const query = {
			...localProductCollectionQuery,
			woocommerceAttributes: [
				{ taxonomy: 'pa_size', termId: 12 },
				{ taxonomy: 'pa_color', termId: 8 },
				{ taxonomy: 'pa_size', termId: 3 },
				{ taxonomy: 'pa_color', termId: 8 },
				{ taxonomy: 'pa_size', termId: 12 },
			],
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_pa_color: '8',
			_unstable_tax_pa_size: '3,12',
		} );
	} );

	it( 'keeps non-colliding taxonomies from both sources', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				product_cat: [ 4 ],
			},
			woocommerceAttributes: [
				{ taxonomy: 'pa_color', termId: 6 },
				{ taxonomy: 'pa_size', termId: 9 },
			],
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_pa_color: '6',
			_unstable_tax_pa_size: '9',
			category: '4',
			'taxonomy_include_children[product_cat]': 'false',
		} );
	} );

	it( 'omits a taxonomy with valid terms in both sources while retaining unrelated taxonomies', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				pa_color: [ 3 ],
				product_tag: [ 7 ],
				product_material: [ 11 ],
			},
			woocommerceAttributes: [
				{ taxonomy: 'pa_color', termId: 5 },
				{ taxonomy: 'pa_size', termId: 13 },
			],
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			_unstable_tax_pa_size: '13',
			_unstable_tax_product_material: '11',
			tag: '7',
			'taxonomy_include_children[product_material]': 'false',
			'taxonomy_include_children[product_tag]': 'false',
		} );
	} );

	it( 'does not emit unsupported Product Collection query fields', () => {
		const query = {
			...localProductCollectionQuery,
			taxQuery: {
				product_cat: [ 2 ],
			},
			search: 'example',
			featured: true,
			woocommerceOnSale: true,
			woocommerceStockStatus: [ 'instock' ],
			woocommerceHandPickedProducts: [ 10 ],
			priceRange: { min: 10, max: 20 },
		};

		expect( getProductCollectionQueryState( query ) ).toStrictEqual( {
			category: '2',
			'taxonomy_include_children[product_cat]': 'false',
		} );
	} );

	it.each( [
		{ taxQuery: { pa_operator: [ 9 ] } },
		{ woocommerceAttributes: [ { taxonomy: 'pa_operator', termId: 9 } ] },
	] )(
		'omits taxonomy parameters reserved for Store API operators',
		( source ) => {
			expect(
				getProductCollectionQueryState( {
					...localProductCollectionQuery,
					...source,
					taxQuery: { ...source.taxQuery, product_cat: [ 2 ] },
				} )
			).toStrictEqual( {
				category: '2',
				'taxonomy_include_children[product_cat]': 'false',
			} );
		}
	);

	it( 'uses built-in taxonomy parameters for attribute-sourced constraints', () => {
		expect(
			getProductCollectionQueryState( {
				...localProductCollectionQuery,
				woocommerceAttributes: [
					{ taxonomy: 'product_cat', termId: 2 },
					{ taxonomy: 'product_tag', termId: 3 },
					{ taxonomy: 'product_brand', termId: 4 },
				],
			} )
		).toStrictEqual( { category: '2', tag: '3', brand: '4' } );
	} );

	it( 'preserves identical constraints from both sources', () => {
		expect(
			getProductCollectionQueryState( {
				...localProductCollectionQuery,
				taxQuery: { pa_color: [ 5, 3, 5 ] },
				woocommerceAttributes: [
					{ taxonomy: 'pa_color', termId: 3 },
					{ taxonomy: 'pa_color', termId: 5 },
				],
			} )
		).toStrictEqual( {
			_unstable_tax_pa_color: '3,5',
			'taxonomy_include_children[pa_color]': 'false',
		} );
	} );
} );
