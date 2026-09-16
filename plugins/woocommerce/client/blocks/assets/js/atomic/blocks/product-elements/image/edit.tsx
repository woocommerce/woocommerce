/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';
import { useProduct } from '@woocommerce/entities';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import {
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { BlockAttributes } from './types';
import { ImageSizeSettings } from './image-size-settings';

const TEMPLATE = [
	[
		'woocommerce/product-sale-badge',
		{
			align: 'right',
		},
	],
];

const DEFAULT_ATTRIBUTES = {
	showProductLink: true,
};

const Edit = ( {
	attributes,
	setAttributes,
	context,
	clientId,
}: BlockEditProps< BlockAttributes > & { context: Context } ): JSX.Element => {
	const { showProductLink, width, height, scale } = attributes;

	const ref = useRef< HTMLDivElement >( null );

	const blockProps = useBlockProps();
	const { wasBlockJustInserted, isInProductGallery } = useSelect(
		( select ) => {
			return {
				wasBlockJustInserted:
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-expect-error method exists but not typed
					select( blockEditorStore ).wasBlockJustInserted( clientId ),
				isInProductGallery:
					select( blockEditorStore ).getBlockParentsByBlockName(
						clientId,
						'woocommerce/product-gallery'
					).length > 0,
			};
		},
		[ clientId ]
	);

	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );
	const { isDescendentOfSingleProductBlock } =
		useIsDescendentOfSingleProductBlock( {
			blockClientId: blockProps?.id,
		} );

	useEffect( () => {
		if ( isDescendentOfQueryLoop || isDescendentOfSingleProductBlock ) {
			setAttributes( {
				isDescendentOfQueryLoop,
				isDescendentOfSingleProductBlock,
				showSaleBadge: false,
			} );
		} else {
			setAttributes( {
				isDescendentOfQueryLoop,
				isDescendentOfSingleProductBlock,
			} );
		}
	}, [
		isDescendentOfQueryLoop,
		isDescendentOfSingleProductBlock,
		setAttributes,
	] );

	const showAllControls =
		isDescendentOfQueryLoop || isDescendentOfSingleProductBlock;

	const innerBlockProps = useInnerBlocksProps(
		{
			className: 'wc-block-components-product-image__inner-container',
		},
		{
			dropZoneElement: ref.current,
			template: wasBlockJustInserted ? TEMPLATE : undefined,
		}
	);

	const { product, isResolving } = useProduct( context.postId );

	return (
		<div { ...blockProps }>
			{ /* Don't show controls in product gallery as we rely on
			core supports API (aspect ratio setting) */ }
			{ showAllControls && ! isInProductGallery && (
				<InspectorControls>
					<ImageSizeSettings
						scale={ scale }
						width={ width }
						height={ height }
						setAttributes={ setAttributes }
					/>
					<ToolsPanel
						label={ __( 'Content', 'woocommerce' ) }
						resetAll={ () =>
							setAttributes( {
								showProductLink:
									DEFAULT_ATTRIBUTES.showProductLink,
							} )
						}
					>
						<ToolsPanelItem
							label={ __(
								'Link to Product Page',
								'woocommerce'
							) }
							hasValue={ () =>
								showProductLink !==
								DEFAULT_ATTRIBUTES.showProductLink
							}
							onDeselect={ () =>
								setAttributes( {
									showProductLink:
										DEFAULT_ATTRIBUTES.showProductLink,
								} )
							}
							isShownByDefault
						>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Link to Product Page',
									'woocommerce'
								) }
								help={ __(
									'Links the image to the single product listing.',
									'woocommerce'
								) }
								checked={ showProductLink }
								onChange={ () =>
									setAttributes( {
										showProductLink: ! showProductLink,
									} )
								}
							/>
						</ToolsPanelItem>
					</ToolsPanel>
				</InspectorControls>
			) }
			<Block
				{ ...{ ...attributes, ...context } }
				isAdmin={ true }
				product={ product }
				isResolving={ isResolving }
			>
				{ showAllControls && <div { ...innerBlockProps } /> }
			</Block>
		</div>
	);
};

export default Edit;
