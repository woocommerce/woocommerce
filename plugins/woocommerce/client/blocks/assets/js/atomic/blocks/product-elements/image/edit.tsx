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
import {
	createInterpolateElement,
	useEffect,
	useRef,
} from '@wordpress/element';
import { getAdminLink, getSettingWithCoercion } from '@woocommerce/settings';
import { useProduct } from '@woocommerce/entities';
import { isBoolean } from '@woocommerce/types';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import {
	PanelBody,
	ToggleControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { BlockAttributes, ImageSizing } from './types';
import { ImageSizeSettings } from './image-size-settings';

const TEMPLATE = [
	[
		'woocommerce/product-sale-badge',
		{
			align: 'right',
		},
	],
];

const Edit = ( {
	attributes,
	setAttributes,
	context,
	clientId,
}: BlockEditProps< BlockAttributes > & { context: Context } ): JSX.Element => {
	const { showProductLink, imageSizing, width, height, scale } = attributes;

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
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-expect-error method exists but not typed
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

	const isBlockTheme = getSettingWithCoercion(
		'isBlockTheme',
		false,
		isBoolean
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
					<PanelBody title={ __( 'Content', 'woocommerce' ) }>
						<ToggleControl
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
						<ToggleGroupControl
							label={ __( 'Resolution', 'woocommerce' ) }
							isBlock
							help={
								! isBlockTheme
									? createInterpolateElement(
											__(
												'Product image cropping can be modified in the <a>Customizer</a>.',
												'woocommerce'
											),
											{
												a: (
													// eslint-disable-next-line jsx-a11y/anchor-has-content
													<a
														href={ `${ getAdminLink(
															'customize.php'
														) }?autofocus[panel]=woocommerce&autofocus[section]=woocommerce_product_images` }
														target="_blank"
														rel="noopener noreferrer"
													/>
												),
											}
									  )
									: null
							}
							value={ imageSizing }
							onChange={ ( value: ImageSizing ) =>
								setAttributes( { imageSizing: value } )
							}
						>
							<ToggleGroupControlOption
								value={ ImageSizing.SINGLE }
								label={ __( 'Full Size', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value={ ImageSizing.THUMBNAIL }
								label={ __( 'Thumbnail', 'woocommerce' ) }
							/>
						</ToggleGroupControl>
					</PanelBody>
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
