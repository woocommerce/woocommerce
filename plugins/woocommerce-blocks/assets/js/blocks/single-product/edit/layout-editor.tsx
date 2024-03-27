/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	InnerBlockLayoutContextProvider,
	ProductDataContextProvider,
} from '@woocommerce/shared-context';
import { createBlocksFromTemplate } from '@woocommerce/atomic-utils';
import { PanelBody, Button } from '@wordpress/components';
import { backup } from '@wordpress/icons';
import { ProductResponseItem } from '@woocommerce/types';
import {
	InnerBlocks,
	InspectorControls,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	BlockContextProvider,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { DEFAULT_INNER_BLOCKS, ALLOWED_INNER_BLOCKS } from '../constants';
import metadata from '../block.json';
import { VARIATION_NAME as PRODUCT_TITLE_VARIATION_NAME } from '../../product-query/variations/elements/product-title';
import { VARIATION_NAME as PRODUCT_SUMMARY_VARIATION_NAME } from '../../product-query/variations/elements/product-summary';

interface LayoutEditorProps {
	isLoading: boolean;
	product: ProductResponseItem;
	clientId: string;
}

const LayoutEditor = ( {
	isLoading,
	product,
	clientId,
}: LayoutEditorProps ) => {
	const baseClassName =
		'.wc-block-editor-single-product .wc-block-editor-layout';
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const resetInnerBlocks = useCallback( () => {
		replaceInnerBlocks(
			clientId,
			createBlocksFromTemplate( DEFAULT_INNER_BLOCKS ),
			false
		);
	}, [ clientId, replaceInnerBlocks ] );

	const defaultTemplate = () => {
		if ( ! isLoading && product ) {
			return [
				[
					'core/columns',
					{},
					[
						[
							'core/column',
							{},
							[
								[
									'core/image',
									{
										url: product?.images[ 0 ].src,
										metadata: {
											bindings: {
												url: {
													source: 'woo/data-binding',
													args: {
														key: 'product',
														postId: product.id,
													},
												},
											},
										},
									},
								],
							],
						],
						[
							'core/column',
							{},
							[
								[
									'core/post-title',
									{
										headingLevel: 2,
										isLink: true,
										__woocommerceNamespace:
											PRODUCT_TITLE_VARIATION_NAME,
									},
								],
								[
									'woocommerce/product-rating',
									{ isDescendentOfSingleProductBlock: true },
								],
								[
									'woocommerce/product-price',
									{ isDescendentOfSingleProductBlock: true },
								],
								[
									'core/post-excerpt',
									{
										__woocommerceNamespace:
											PRODUCT_SUMMARY_VARIATION_NAME,
									},
								],
								[ 'woocommerce/add-to-cart-form' ],
								[ 'woocommerce/product-meta' ],
							],
						],
					],
				],
			];
		}
	};

	return (
		<InnerBlockLayoutContextProvider
			parentName={ metadata.name }
			parentClassName={ baseClassName }
		>
			<ProductDataContextProvider
				product={ product }
				isLoading={ isLoading }
			>
				<InspectorControls>
					<PanelBody
						title={ __( 'Layout', 'woocommerce' ) }
						initialOpen={ true }
					>
						<Button
							label={ __(
								'Reset layout to default',
								'woocommerce'
							) }
							onClick={ resetInnerBlocks }
							variant="tertiary"
							className="wc-block-editor-single-product__reset-layout"
							icon={ backup }
						>
							{ __( 'Reset layout', 'woocommerce' ) }
						</Button>
					</PanelBody>
				</InspectorControls>
				<div className={ baseClassName }>
					<BlockContextProvider
						value={ { postId: product?.id, postType: 'product' } }
					>
						<InnerBlocks
							template={ defaultTemplate() }
							allowedBlocks={ ALLOWED_INNER_BLOCKS }
							templateLock={ false }
						/>
					</BlockContextProvider>
				</div>
			</ProductDataContextProvider>
		</InnerBlockLayoutContextProvider>
	);
};

export default LayoutEditor;
