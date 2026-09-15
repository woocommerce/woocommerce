/**
 * External dependencies
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { migrateToCover } from '../migrate-to-cover';

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
} );

describe( 'Featured Category Cover migration helper', () => {
	it.each( [
		[ { height: 620 }, 620 ],
		[ { height: 620, editMode: false }, 620 ],
		[ { minHeight: 700 }, 700 ],
		[ { height: 620, minHeight: 500 }, 500 ],
		[ { height: 620, minHeight: 0 }, 0 ],
		[ {}, getSetting( 'defaultHeight', 500 ) ],
	] as const )( 'preserves height for %j', ( attributes, expected ) => {
		const [ , [ cover ] ] = migrateToCover( attributes, [] );
		expect( cover.attributes.minHeight ).toBe( expected );
		expect( cover.attributes.minHeightUnit ).toBe( 'px' );
	} );

	it.each( [ true, false, undefined ] )(
		'creates v0 content with showDesc=%s',
		( showDesc ) => {
			const child = createBlock( 'core/paragraph', {
				content: 'Custom content',
			} );
			const [ , [ cover ] ] = migrateToCover(
				{ categoryId: 42, editMode: false, showDesc },
				[ child ]
			);
			expect( cover.innerBlocks.map( ( block ) => block.name ) ).toEqual(
				showDesc !== false
					? [
							'woocommerce/category-title',
							'woocommerce/category-description',
							'core/group',
					  ]
					: [ 'woocommerce/category-title', 'core/group' ]
			);
			expect( cover.innerBlocks.at( -1 )?.innerBlocks[ 0 ] ).toBe(
				child
			);
		}
	);

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
			expect( cover.attributes.metadata.bindings ).toBeUndefined();
			expect(
				cover.attributes.metadata[
					'woocommerce/featured-category-image'
				]
			).toMatchObject( {
				size: 'large',
				noPlaceholder: true,
			} );
			expect( cover.innerBlocks[ 0 ].innerBlocks[ 0 ] ).toBe( child );
		}
	);
	it( 'keeps a custom attachment tied to its ID and preserves full sizing for wide cards', () => {
		const [ , [ cover ] ] = migrateToCover(
			{ categoryId: 42, mediaId: 99, align: 'wide', imageFit: 'cover' },
			[]
		);
		expect(
			cover.attributes.metadata[ 'woocommerce/featured-category-image' ]
		).toMatchObject( {
			attachmentId: 99,
			size: 'full',
			noPlaceholder: true,
		} );
		expect( cover.attributes.className ).not.toContain( 'natural-image' );
	} );
} );
