/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
import { BlockInstance, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import imagesBlock from '../components/images/block';
import nameBlock from '../components/name/block';
import sectionBlock from '../components/section/block';
import summaryBlock from '../components/summary/block';
import emptyBlock from '../components/empty/block';

export const parseProductToBlocks = ( product: Partial< Product > ) => {
	const blocks: BlockInstance[] = [];

	// blocks.push(
	// 	createBlock(
	// 		sectionBlock.name,
	// 		{
	// 			title: __( 'Product details', 'woocommerce' ),
	// 			description: __(
	// 				'This info will be displayed on the product page, category pages, social media, and search results.',
	// 				'woocommerce'
	// 			),
	// 		},
	// 		[
	// 			createBlock( nameBlock.name, {
	// 				name: product.name,
	// 			} ),
	// 			createBlock( summaryBlock.name, {
	// 				content: product.short_description,
	// 			} ),
	// 			createBlock( imagesBlock.name, {} ),
	// 		]
	// 	)
	// );

	// Case 2: Rendering 100 blocks with 1 level nesting.
	Array( 100 )
		.fill( 0 )
		.forEach( () => {
			blocks.push(
				createBlock( nameBlock.name, {
					name: product.name,
				} )
			);
		} );

	// Case 3: Rendering 100 blocks with 1 level nesting (20 normal blocks and 80 blocks returning null).
	// Array( 20 )
	// 	.fill( 0 )
	// 	.forEach( () => {
	// 		blocks.push(
	// 			createBlock( nameBlock.name, {
	// 				name: product.name,
	// 			} ),
	// 			createBlock( emptyBlock.name, {
	// 				name: emptyBlock.name,
	// 			} ),
	// 			createBlock( emptyBlock.name, {
	// 				name: emptyBlock.name,
	// 			} ),
	// 			createBlock( emptyBlock.name, {
	// 				name: emptyBlock.name,
	// 			} ),
	// 			createBlock( emptyBlock.name, {
	// 				name: emptyBlock.name,
	// 			} )
	// 		);
	// 	} );

	// Case 4: Rendering 100 blocks with 2 level nesting.
	// Array( 50 )
	// 	.fill( 0 )
	// 	.forEach( () => {
	// 		blocks.push(
	// 			createBlock(
	// 				sectionBlock.name,
	// 				{
	// 					title: __( 'Level 1', 'woocommerce' ),
	// 					description: __(
	// 						'This info will be displayed on the product page, category pages, social media, and search results.',
	// 						'woocommerce'
	// 					),
	// 				},
	// 				[
	// 					createBlock( nameBlock.name, {
	// 						name: "Level 2",
	// 					} ),
	// 				]
	// 			)
	// 		);
	// 	} );

	// Case 5: Rendering 100 blocks with 3 level nesting.
	// Array( 25 )
	// 	.fill( 0 )
	// 	.forEach( () => {
	// 		blocks.push(
	// 			createBlock(
	// 				sectionBlock.name,
	// 				{
	// 					title: __( 'Level 1', 'woocommerce' ),
	// 					description: __(
	// 						'This info will be displayed on the product page, category pages, social media, and search results.',
	// 						'woocommerce'
	// 					),
	// 				},
	// 				[
	// 					createBlock(
	// 						sectionBlock.name,
	// 						{
	// 							title: __( 'Level 2', 'woocommerce' ),
	// 							description: __(
	// 								'This info will be displayed on the product page, category pages, social media, and search results.',
	// 								'woocommerce'
	// 							),
	// 						},
	// 						[
	// 							createBlock( nameBlock.name, {
	// 								name: 'Level 3, Child 1',
	// 							} ),
	// 							createBlock( nameBlock.name, {
	// 								name: 'Level 3, Child 2',
	// 							} ),
	// 						]
	// 					),
	// 				]
	// 			)
	// 		);
	// 	} );

	// Case 6: Rendering 400 blocks with 4 level nesting.
	// Array( 100 )
	// 	.fill( 0 )
	// 	.forEach( () => {
	// 		blocks.push(
	// 			createBlock(
	// 				sectionBlock.name,
	// 				{
	// 					title: __( 'Level 1', 'woocommerce' ),
	// 					description: __(
	// 						'This info will be displayed on the product page, category pages, social media, and search results.',
	// 						'woocommerce'
	// 					),
	// 				},
	// 				[
	// 					createBlock(
	// 						sectionBlock.name,
	// 						{
	// 							title: __( 'Level 2', 'woocommerce' ),
	// 							description: __(
	// 								'This info will be displayed on the product page, category pages, social media, and search results.',
	// 								'woocommerce'
	// 							),
	// 						},
	// 						[
	// 							createBlock(
	// 								sectionBlock.name,
	// 								{
	// 									title: __( 'Level 3', 'woocommerce' ),
	// 									description: __(
	// 										'This info will be displayed on the product page, category pages, social media, and search results.',
	// 										'woocommerce'
	// 									),
	// 								},
	// 								[
	// 									createBlock( nameBlock.name, {
	// 										name: 'Level 4',
	// 									} ),
	// 								]
	// 							),
	// 						]
	// 					),
	// 				]
	// 			)
	// 		);
	// 	} );

	return blocks;
};

Object.defineProperty( parseProductToBlocks, 'name', {
	value: 'parseProductToBlocks',
	writable: false,
} );
