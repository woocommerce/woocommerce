/**
 * External dependencies
 */
import {
	parse,
	registerBlockType,
	serialize,
	unregisterBlockType,
} from '@wordpress/blocks';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import deprecated from '../deprecated';
import Save from '../save';

let mockDefaultAttributeId = '5';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn( ( key: string, defaultValue: unknown ) =>
		key === 'defaultProductFilterAttribute'
			? { attribute_id: mockDefaultAttributeId }
			: defaultValue
	),
} ) );

const BLOCK = 'woocommerce/product-filter-attribute';
const { isEligible, migrate } = deprecated[ 0 ];

describe( 'Attribute filter deprecation', () => {
	beforeEach( () => {
		mockDefaultAttributeId = '5';
		registerBlockType(
			{ ...metadata, category: 'widgets' } as never,
			{
				edit: () => null,
				save: Save,
				deprecated,
			} as never
		);
		registerBlockType( 'core/paragraph', {
			apiVersion: 3,
			title: 'Paragraph',
			category: 'text',
			attributes: { content: { type: 'string' } },
			edit: () => null,
			save: () => createElement( 'p', null, 'Inner' ),
		} as never );
	} );

	afterEach( () => {
		unregisterBlockType( BLOCK );
		unregisterBlockType( 'core/paragraph' );
	} );

	describe( 'isEligible', () => {
		it.each( [ [ {} ], [ { attributeId: 0 } ] ] )(
			'is eligible when attributeId is missing or 0: %j',
			( attributes ) => {
				expect( isEligible( attributes ) ).toBe( true );
			}
		);

		it( 'is not eligible when attributeId is set', () => {
			expect( isEligible( { attributeId: 3 } ) ).toBe( false );
		} );

		it( 'is not eligible when the store has no default attribute', () => {
			mockDefaultAttributeId = '0';
			expect( isEligible( {} ) ).toBe( false );
		} );
	} );

	describe( 'migrate', () => {
		it( 'pins the default attribute and keeps other attributes', () => {
			expect(
				migrate( { showCounts: true, sortOrder: 'name-asc' } )
			).toEqual( {
				attributeId: 5,
				showCounts: true,
				sortOrder: 'name-asc',
			} );
		} );
	} );

	describe( 'parsing and serialization', () => {
		it( 'pins a legacy block without attributeId, preserving attributes, supports and inner blocks', () => {
			const [ block ] = parse(
				`<!-- wp:woocommerce/product-filter-attribute {"showCounts":true,"queryType":"and","sortOrder":"name-asc","hideEmpty":false,"style":{"spacing":{"padding":{"top":"10px"}}},"textColor":"vivid-red"} -->
<div class="wp-block-woocommerce-product-filter-attribute has-vivid-red-color has-text-color" style="padding-top:10px"><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --></div>
<!-- /wp:woocommerce/product-filter-attribute -->`
			);

			expect( block.isValid ).toBe( true );
			expect( block.attributes ).toMatchObject( {
				attributeId: 5,
				showCounts: true,
				queryType: 'and',
				sortOrder: 'name-asc',
				hideEmpty: false,
				style: { spacing: { padding: { top: '10px' } } },
				textColor: 'vivid-red',
			} );
			expect( block.innerBlocks ).toHaveLength( 1 );
			expect( block.innerBlocks[ 0 ].name ).toBe( 'core/paragraph' );
		} );

		it( 'does not change a block with an explicit attributeId', () => {
			const [ block ] = parse(
				'<!-- wp:woocommerce/product-filter-attribute {"attributeId":3} -->\n<div class="wp-block-woocommerce-product-filter-attribute"></div>\n<!-- /wp:woocommerce/product-filter-attribute -->'
			);

			expect( block.attributes.attributeId ).toBe( 3 );
		} );

		it( 'serializes the attributeId even when it equals the store default', () => {
			const [ block ] = parse(
				'<!-- wp:woocommerce/product-filter-attribute {"attributeId":5} -->\n<div class="wp-block-woocommerce-product-filter-attribute"></div>\n<!-- /wp:woocommerce/product-filter-attribute -->'
			);

			expect( serialize( [ block ] ) ).toContain( '"attributeId":5' );
		} );

		it( 'serializes the pinned attributeId after migrating a legacy block', () => {
			const [ block ] = parse(
				'<!-- wp:woocommerce/product-filter-attribute -->\n<div class="wp-block-woocommerce-product-filter-attribute"></div>\n<!-- /wp:woocommerce/product-filter-attribute -->'
			);

			expect( serialize( [ block ] ) ).toContain( '"attributeId":5' );
		} );
	} );
} );
