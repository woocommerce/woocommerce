/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlockTemplate } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	Warning,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductDetailsEditProps } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'woocommerce/accordion-group',
		{
			metadata: {
				isDescendantOfProductDetails: true,
			},
		},
		[
			[
				'woocommerce/accordion-item',
				{
					openByDefault: true,
				},
				[
					[
						'woocommerce/accordion-header',
						{ title: __( 'Description', 'woocommerce' ) },
						[],
					],
					[
						'woocommerce/accordion-panel',
						{},
						[ [ 'woocommerce/product-description', {}, [] ] ],
					],
				],
			],
			[
				'woocommerce/accordion-item',
				{},
				[
					[
						'woocommerce/accordion-header',
						{
							title: __(
								'Additional Information',
								'woocommerce'
							),
						},
						[],
					],
					[
						'woocommerce/accordion-panel',
						{},
						[ [ 'woocommerce/product-specifications', {} ] ],
					],
				],
			],
			[
				'woocommerce/accordion-item',
				{},
				[
					[
						'woocommerce/accordion-header',
						{ title: __( 'Reviews', 'woocommerce' ) },
						[],
					],
					[
						'woocommerce/accordion-panel',
						{},
						[ [ 'woocommerce/product-reviews', {} ] ],
					],
				],
			],
		],
	],
];

/**
 * Check if block is inside a Query Loop with non-product post type
 *
 * @param {string} clientId The block's client ID
 * @param {string} postType The current post type
 * @return {boolean} Whether the block is in an invalid Query Loop context
 */
const useIsInvalidQueryLoopContext = ( clientId: string, postType: string ) => {
	return useSelect(
		( select ) => {
			const blockParents = select(
				blockEditorStore
			).getBlockParentsByBlockName( clientId, 'core/post-template' );
			return blockParents.length > 0 && postType !== 'product';
		},
		[ clientId, postType ]
	);
};

const Edit = ( { clientId, context }: ProductDetailsEditProps ) => {
	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	const isInvalidQueryLoopContext = useIsInvalidQueryLoopContext(
		clientId,
		context.postType
	);
	if ( isInvalidQueryLoopContext ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'The Product Details block requires a product context. When used in a Query Loop, the Query Loop must be configured to display products.',
						'woocommerce'
					) }
				</Warning>
			</div>
		);
	}
	return <div { ...innerBlocksProps } />;
};

export default Edit;
