/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';
import type { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import {
	RangeControl,
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import {
	BLOCK_TITLE as label,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';
import './editor.scss';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';
import type { Attributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< Attributes > & { context: Context } ): JSX.Element => {
	const blockProps = useBlockProps();

	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );
	const { isDescendentOfSingleProductBlock } =
		useIsDescendentOfSingleProductBlock( { blockClientId: blockProps.id } );

	let { isDescendentOfSingleProductTemplate } =
		useIsDescendentOfSingleProductTemplate();

	if ( isDescendentOfQueryLoop ) {
		isDescendentOfSingleProductTemplate = false;
	}

	useEffect(
		() =>
			setAttributes( {
				isDescendentOfQueryLoop,
				isDescendentOfSingleProductTemplate,
				isDescendentOfSingleProductBlock,
			} ),
		[
			setAttributes,
			isDescendentOfQueryLoop,
			isDescendentOfSingleProductTemplate,
			isDescendentOfSingleProductBlock,
		]
	);

	return (
		<div { ...blockProps }>
			<Block { ...attributes } />
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Settings', 'woocommerce' ) }
					resetAll={ () => {
						const defaultSettings = {};
						setAttributes( defaultSettings );
					} }
				>
					<ToolsPanelItem
						label={ __(
							'Fallback to product description',
							'woocommerce'
						) }
						hasValue={ () => true }
						isShownByDefault
					>
						<ToggleControl
							label={ __(
								'Fallback to product description',
								'woocommerce'
							) }
							checked={ false }
							onChange={ ( showPostContent ) => {
								setAttributes( {
									showPostContent,
								} );
							} }
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __( 'Show link on new line', 'woocommerce' ) }
						hasValue={ () => true }
						isShownByDefault
					>
						<ToggleControl
							label={ __(
								'Show link on new line',
								'woocommerce'
							) }
							checked={ false }
							onChange={ ( showLink ) => {
								setAttributes( {
									showLink,
								} );
							} }
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __( 'Max word count', 'woocommerce' ) }
						hasValue={ () => true }
						isShownByDefault
					>
						<RangeControl
							label={ __( 'Max word count', 'woocommerce' ) }
							value={ 100 }
							onChange={ ( summaryLength ) => {
								setAttributes( { summaryLength } );
							} }
							min={ 0 }
							max={ 1000 }
							step={ 5 }
							help={ __(
								'Set to 0 to remove the limit completely',
								'woocommerce'
							) }
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
		</div>
	);
};

// @todo: Refactor this to remove the HOC 'withProductSelector()' component as users will not see this block in the inserter. Therefore, we can export the Edit component by default. The HOC 'withProductSelector()' component should also be removed from other `product-elements` components. See also https://github.com/woocommerce/woocommerce-blocks/pull/7566#pullrequestreview-1168635469.
export default withProductSelector( { icon, label, description } )( Edit );
