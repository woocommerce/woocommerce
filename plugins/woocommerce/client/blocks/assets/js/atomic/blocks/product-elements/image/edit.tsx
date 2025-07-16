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
import withProductSelector from '../shared/with-product-selector';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { BLOCK_ICON as icon } from './constants';
import { title, description } from './block.json';
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
	const wasBlockJustInserted = useSelect(
		( select ) =>
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore method exists but not typed
			select( blockEditorStore ).wasBlockJustInserted( clientId ),
		[ clientId ]
	);
	const innerBlockProps = useInnerBlocksProps(
		{
			className: 'wc-block-components-product-image__inner-container',
		},
		{
			dropZoneElement: ref.current,
			template: wasBlockJustInserted ? TEMPLATE : undefined,
		}
	);
	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );
	const { isDescendentOfSingleProductBlock } =
		useIsDescendentOfSingleProductBlock( {
			blockClientId: blockProps?.id,
		} );
	const isBlockTheme = getSettingWithCoercion(
		'isBlockTheme',
		false,
		isBoolean
	);

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

	return (
		<div { ...blockProps }>
			<InspectorControls>
				{ showAllControls && (
					<ImageSizeSettings
						scale={ scale }
						width={ width }
						height={ height }
						setAttributes={ setAttributes }
					/>
				) }
				<PanelBody title={ __( 'Content', 'woocommerce' ) }>
					{ showAllControls && (
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
					) }
					<ToggleGroupControl
						label={ __( 'Image Sizing', 'woocommerce' ) }
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
							label={ __( 'Cropped', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<Block { ...{ ...attributes, ...context } }>
				{ showAllControls && <div { ...innerBlockProps } /> }
			</Block>
		</div>
	);
};

export default withProductSelector( { icon, title, description } )( Edit );
