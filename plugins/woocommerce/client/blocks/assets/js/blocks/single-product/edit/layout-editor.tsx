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
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error Type definitions for this function are missing in Gutenberg
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { DEFAULT_INNER_BLOCKS, ALLOWED_INNER_BLOCKS } from '../constants';
import metadata from '../block.json';

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
	const baseClassName = 'wc-block-editor-single-product';
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const resetInnerBlocks = useCallback( () => {
		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate( DEFAULT_INNER_BLOCKS ),
			false
		);
	}, [ clientId, replaceInnerBlocks ] );

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
							template={ DEFAULT_INNER_BLOCKS }
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
