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
	InspectorControls,
} from '@wordpress/block-editor';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductDetailsEditProps } from './types';
import { LegacyProductDetailsPreview } from './legacy-preview';
import './editor.scss';

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

const Edit = ( {
	clientId,
	context,
	attributes,
	setAttributes,
}: ProductDetailsEditProps ) => {
	const blockProps = useBlockProps();
	const { hideTabTitle } = attributes;

	const { hasInnerBlocks, wasBlockJustInserted } = useSelect(
		( select ) => {
			const blocks = select( blockEditorStore ).getBlocks( clientId );
			return {
				hasInnerBlocks: blocks.length > 0,
				wasBlockJustInserted:
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-expected-error method exists but not typed
					select( blockEditorStore ).wasBlockJustInserted( clientId ),
			};
		},
		[ clientId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: wasBlockJustInserted ? TEMPLATE : undefined,
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

	if ( hasInnerBlocks || wasBlockJustInserted ) {
		return <div { ...innerBlocksProps } />;
	}

	return (
		<div { ...blockProps }>
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __(
							'Show tab title in content',
							'woocommerce'
						) }
						checked={ ! hideTabTitle }
						onChange={ () =>
							setAttributes( {
								hideTabTitle: ! hideTabTitle,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<LegacyProductDetailsPreview hideTabTitle={ hideTabTitle } />
			</Disabled>
		</div>
	);
};

export default Edit;
