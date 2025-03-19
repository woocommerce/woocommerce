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

const createAccordionItem = (
	title: string,
	content: InnerBlockTemplate[]
): InnerBlockTemplate => {
	return [
		'woocommerce/accordion-item',
		{},
		[
			[ 'woocommerce/accordion-header', { title }, [] ],
			[ 'woocommerce/accordion-panel', {}, content ],
		],
	];
};

const descriptionAccordion = createAccordionItem( 'Description', [
	[ 'woocommerce/product-description', {}, [] ],
] );

const additionalInformationAccordion = createAccordionItem(
	'Additional Information',
	[
		[
			'core/paragraph',
			{
				content:
					'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eget turpis eget nunc fermentum ultricies. Nullam nec sapien nec0',
			},
		],
	]
);

const reviewsAccordion = createAccordionItem( 'Reviews', [
	[ 'woocommerce/blockified-product-reviews', {} ],
] );

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'woocommerce/accordion-group',
		{},
		[
			descriptionAccordion,
			additionalInformationAccordion,
			reviewsAccordion,
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
