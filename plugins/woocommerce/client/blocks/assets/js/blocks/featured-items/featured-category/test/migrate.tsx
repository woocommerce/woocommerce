/**
 * External dependencies
 */
import {
	createBlock,
	parse,
	registerBlockType,
	serialize,
} from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import deprecated from '../deprecated';
import { migrateToCover } from '../migrate';

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
	registerBlockType( metadata.name, {
		...metadata,
		category: 'text',
		deprecated,
		save: () => <InnerBlocks.Content />,
	} );
} );

describe( 'Featured Category automatic migration', () => {
	it.each( [ 'left', 'center', 'right' ] )(
		'preserves %s positioning, natural image sizing and appearance',
		( position ) => {
			const child = createBlock( 'core/paragraph', {
				content: 'Custom content',
			} );
			const [ attributes, [ cover ] ] = migrateToCover(
				{
					categoryId: 42,
					contentAlign: position,
					focalPoint: { x: 0.2, y: 0.8 },
					dimRatio: 30,
					minHeight: 620,
					backgroundColor: 'cyan-bluish-gray',
					borderColor: 'vivid-red',
					style: {
						typography: { lineHeight: '1.8' },
						spacing: { padding: '12px' },
					},
				},
				[ child ]
			);
			expect( attributes ).toMatchObject( {
				categoryId: 42,
				layout: 'cover',
			} );
			expect( attributes ).not.toHaveProperty( 'focalPoint' );
			expect( cover.attributes ).toMatchObject( {
				contentPosition: `center ${ position }`,
				focalPoint: { x: 0.2, y: 0.8 },
				dimRatio: 30,
				minHeight: 620,
				backgroundColor: 'cyan-bluish-gray',
				borderColor: 'vivid-red',
				style: {
					typography: { lineHeight: '1.8' },
					spacing: { padding: '12px' },
				},
			} );
			expect( cover.attributes.className ).toContain( 'natural-image' );
			expect( cover.attributes.metadata.bindings.url.args ).toEqual( {
				size: 'large',
				noPlaceholder: true,
			} );
			expect( cover.innerBlocks[ 0 ].innerBlocks[ 0 ] ).toBe( child );
			const saved = serialize(
				createBlock( metadata.name, attributes, [ cover ] )
			);
			expect( saved ).toContain(
				'has-cyan-bluish-gray-background-color'
			);
			expect( saved ).toContain( 'has-vivid-red-border-color' );
			const parsed = parse( saved )[ 0 ];
			expect( parsed.isValid ).toBe( true );
			expect( parsed.innerBlocks[ 0 ].isValid ).toBe( true );
			expect( parsed.innerBlocks[ 0 ].innerBlocks ).toHaveLength( 1 );
		}
	);

	it.each( [ true, false ] )(
		'migrates the oldest format directly, with showDesc=%s',
		( showDesc ) => {
			const saved =
				'<!-- wp:woocommerce/featured-category {"categoryId":42,"editMode":false,"showDesc":' +
				showDesc +
				'} --><!-- wp:paragraph --><p>Custom content</p><!-- /wp:paragraph --><!-- /wp:woocommerce/featured-category -->';
			const block = parse( saved )[ 0 ];
			expect( block.attributes.layout ).toBe( 'cover' );
			const cover = block.innerBlocks[ 0 ];
			expect( cover.name ).toBe( 'core/cover' );
			expect( cover.innerBlocks.map( ( child ) => child.name ) ).toEqual(
				showDesc
					? [
							'woocommerce/category-title',
							'woocommerce/category-description',
							'core/group',
					  ]
					: [ 'woocommerce/category-title', 'core/group' ]
			);
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

	it( 'keeps a custom attachment tied to its ID and preserves full sizing for wide cards', () => {
		const [ , [ cover ] ] = migrateToCover(
			{ categoryId: 42, mediaId: 99, align: 'wide', imageFit: 'cover' },
			[]
		);
		expect( cover.attributes.metadata.bindings.url.args ).toEqual( {
			attachmentId: 99,
			size: 'full',
			noPlaceholder: true,
		} );
		expect( cover.attributes.className ).not.toContain( 'natural-image' );
	} );
} );
