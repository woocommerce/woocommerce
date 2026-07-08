/**
 * External dependencies
 */
import { BlockInstance, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import deprecated from '../deprecated';

const blockName = 'woocommerce/featured-product-title';

function registerMinimalBlock(
	name: string,
	attrs: Record< string, unknown > = {}
) {
	if ( ! window.wp?.blocks?.getBlockType( name ) ) {
		registerBlockType( name, {
			apiVersion: 3,
			name,
			title: name,
			category: 'text',
			attributes: attrs,
			edit: () => null,
			save: () => null,
		} );
	}
}

beforeAll( () => {
	// Register blocks used by v1/v2 migrate functions so createBlock works.
	registerMinimalBlock( 'woocommerce/featured-product-title', {
		isLink: { type: 'boolean', default: false },
		level: { type: 'number', default: 2 },
		linkTarget: { type: 'string', default: '_self' },
		rel: { type: 'string', default: '' },
		textAlign: { type: 'string' },
		content: { type: 'string' },
	} );
	registerMinimalBlock( 'woocommerce/product-price' );
	registerMinimalBlock( 'woocommerce/product-summary' );
} );

const [ v1, v2 ] = deprecated;

describe( 'featured-product deprecated block migrations', () => {
	describe( 'v1: legacy showDesc/showPrice to inner blocks', () => {
		it( 'replaces the legacy core/post-title inner block with woocommerce/featured-product-title', () => {
			const innerBlocks: BlockInstance[] = [
				// A user-created inner block that should be preserved.
				{
					name: 'woocommerce/product-summary',
					attributes: {},
					innerBlocks: [],
					innerContent: [],
				},
			];

			const [ , migratedInnerBlocks ] = v1.migrate(
				{ editMode: true, showDesc: true, showPrice: true },
				innerBlocks
			);

			const titleBlock = migratedInnerBlocks.find(
				( block ) => block.name === 'woocommerce/featured-product-title'
			);

			expect( titleBlock ).toBeDefined();
			expect( titleBlock?.attributes ).toMatchObject( {
				level: 2,
				isLink: false,
				textAlign: 'center',
			} );
			expect(
				migratedInnerBlocks.some(
					( block ) => block.name === 'core/post-title'
				)
			).toBe( false );
		} );
	} );

	describe( 'v2: core/post-title conversion', () => {
		it( 'is eligible when a core/post-title inner block is present', () => {
			const innerBlocks: BlockInstance[] = [
				{
					name: 'core/post-title',
					attributes: { level: 3, isLink: true, textAlign: 'left' },
					innerBlocks: [],
					innerContent: [],
				},
			];

			expect( v2.isEligible( {}, innerBlocks ) ).toBe( true );
		} );

		it( 'is not eligible when no core/post-title inner block exists', () => {
			const innerBlocks: BlockInstance[] = [
				{
					name: 'woocommerce/featured-product-title',
					attributes: {},
					innerBlocks: [],
					innerContent: [],
				},
			];

			expect( v2.isEligible( {}, innerBlocks ) ).toBe( false );
		} );

		it( 'converts a core/post-title inner block to woocommerce/featured-product-title preserving attributes', () => {
			const innerBlocks: BlockInstance[] = [
				{
					name: 'core/post-title',
					attributes: { level: 3, isLink: true, textAlign: 'left' },
					innerBlocks: [],
					innerContent: [],
				},
			];

			const [ attributes, migratedInnerBlocks ] = v2.migrate(
				{ productId: 5 },
				innerBlocks
			);

			expect( ( migratedInnerBlocks[ 0 ] as BlockInstance ).name ).toBe(
				'woocommerce/featured-product-title'
			);
			expect(
				( migratedInnerBlocks[ 0 ] as BlockInstance ).attributes
			).toMatchObject( {
				level: 3,
				isLink: true,
				textAlign: 'left',
			} );
			// Non-inner-block attributes must survive the migration.
			expect( attributes ).toEqual( { productId: 5 } );
		} );

		it( 'leaves non-core/post-title inner blocks untouched', () => {
			const innerBlocks: BlockInstance[] = [
				{
					name: 'woocommerce/product-summary',
					attributes: {},
					innerBlocks: [],
					innerContent: [],
				},
				{
					name: 'core/post-title',
					attributes: { level: 2 },
					innerBlocks: [],
					innerContent: [],
				},
			];

			const [ , migratedInnerBlocks ] = v2.migrate( {}, innerBlocks );

			expect( migratedInnerBlocks ).toHaveLength( 2 );
			expect( migratedInnerBlocks[ 0 ].name ).toBe(
				'woocommerce/product-summary'
			);
			expect( migratedInnerBlocks[ 1 ].name ).toBe(
				'woocommerce/featured-product-title'
			);
		} );
	} );
} );
