/**
 * External dependencies
 */
import {
	getBlockType,
	parse,
	registerBlockType,
	serialize,
} from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import deprecated from '../deprecated';
import { register } from '../../register';
import productMetadata from '../../featured-product/block.json';

beforeAll( () => {
	registerCoreBlocks();
	for ( const name of [
		'woocommerce/category-title',
		'woocommerce/category-description',
	] ) {
		registerBlockType( name, {
			apiVersion: 3,
			title: name,
			category: 'text',
			attributes: {
				level: { type: 'number' },
				isLink: { type: 'boolean' },
				textAlign: { type: 'string' },
				style: { type: 'object' },
			},
			save: () => null,
		} );
	}
	register( () => null, { attributes: {} }, metadata, {
		deprecated,
		supports: metadata.supports,
		category: 'text',
	} );
	register( () => null, { attributes: {} }, productMetadata, {
		category: 'text',
	} );
} );

describe( 'Featured Category automatic migration', () => {
	it( 'keeps category support overrides separate from Featured Product', () => {
		const category = getBlockType( metadata.name );
		expect( category.supports ).toMatchObject( metadata.supports );
		for ( const support of [
			'color',
			'spacing',
			'filter',
			'__experimentalBorder',
		] ) {
			expect( category.supports[ support ] ).toBeUndefined();
		}
		expect( getBlockType( productMetadata.name ).supports ).toMatchObject( {
			...productMetadata.supports,
			spacing: {
				...productMetadata.supports.spacing,
				__experimentalDefaultControls: {
					padding: { padding: true },
				},
			},
		} );
	} );

	it( 'preserves legacy styling through parsing, saving and reopening', () => {
		const block = parse(
			'<!-- wp:woocommerce/featured-category {"categoryId":42,"backgroundColor":"cyan-bluish-gray","textColor":"white","borderColor":"vivid-red","style":{"spacing":{"padding":"12px"},"border":{"radius":"8px"}}} --><!-- wp:woocommerce/category-title /--><!-- /wp:woocommerce/featured-category -->'
		)[ 0 ];
		expect( block.isValid ).toBe( true );
		expect( block.innerBlocks[ 0 ].attributes ).toMatchObject( {
			backgroundColor: 'cyan-bluish-gray',
			textColor: 'white',
			borderColor: 'vivid-red',
			style: {
				spacing: { padding: '12px' },
				border: { radius: '8px' },
			},
		} );
		const saved = serialize( block );
		expect( saved ).toContain( 'has-cyan-bluish-gray-background-color' );
		expect( saved ).toContain( 'has-vivid-red-border-color' );
		const reopened = parse( saved )[ 0 ];
		expect( reopened.isValid ).toBe( true );
		expect( reopened.innerBlocks[ 0 ].isValid ).toBe( true );
		expect( reopened.innerBlocks[ 0 ].innerBlocks ).toHaveLength( 1 );
		expect( serialize( reopened ) ).toBe( saved );
	} );

	it.each( [
		[ { height: 620 }, 620 ],
		[ { height: 620, editMode: false }, 620 ],
		[ { minHeight: 700 }, 700 ],
		[ { height: 620, minHeight: 500 }, 500 ],
		[ { height: 620, minHeight: 0 }, 0 ],
		[ {}, getSetting( 'defaultHeight', 500 ) ],
	] as const )(
		'preserves height when parsing %j',
		( savedAttributes, expected ) => {
			const block = parse(
				'<!-- wp:woocommerce/featured-category ' +
					JSON.stringify( { categoryId: 42, ...savedAttributes } ) +
					' --><!-- /wp:woocommerce/featured-category -->'
			)[ 0 ];
			expect( block.innerBlocks[ 0 ].attributes.minHeight ).toBe(
				expected
			);
			expect( block.innerBlocks[ 0 ].attributes.minHeightUnit ).toBe(
				'px'
			);
		}
	);

	it.each( [ true, false, undefined ] )(
		'migrates the oldest format directly, with showDesc=%s',
		( showDesc ) => {
			const saved =
				'<!-- wp:woocommerce/featured-category ' +
				JSON.stringify( {
					categoryId: 42,
					editMode: false,
					showDesc,
				} ) +
				' --><!-- wp:paragraph --><p>Custom content</p><!-- /wp:paragraph --><!-- /wp:woocommerce/featured-category -->';
			const block = parse( saved )[ 0 ];
			expect( block.attributes.layout ).toBe( 'cover' );
			const cover = block.innerBlocks[ 0 ];
			expect( cover.name ).toBe( 'core/cover' );
			expect( cover.innerBlocks.map( ( child ) => child.name ) ).toEqual(
				showDesc !== false
					? [
							'woocommerce/category-title',
							'woocommerce/category-description',
							'core/group',
					  ]
					: [ 'woocommerce/category-title', 'core/group' ]
			);
			expect(
				serialize( cover.innerBlocks.at( -1 )?.innerBlocks || [] )
			).toContain( '<p>Custom content</p>' );
			expect( serialize( parse( serialize( block ) ) ) ).toBe(
				serialize( block )
			);
		}
	);

	it( 'migrates the inner-block format without duplicating its title', () => {
		const block = parse(
			'<!-- wp:woocommerce/featured-category {"categoryId":42} --><!-- wp:woocommerce/category-title /--><!-- /wp:woocommerce/featured-category -->'
		)[ 0 ];
		expect(
			block.innerBlocks[ 0 ].innerBlocks[ 0 ].innerBlocks
		).toHaveLength( 1 );
		expect(
			block.innerBlocks[ 0 ].innerBlocks[ 0 ].innerBlocks[ 0 ].name
		).toBe( 'woocommerce/category-title' );
	} );
} );
