/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { getAdminLink, getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean } from '@woocommerce/types';
import {
	Disabled,
	PanelBody,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';

const Edit = ( { attributes, setAttributes, context } ) => {
	const { showProductLink, imageSizing, showSaleBadge, saleBadgeAlign } =
		attributes;

	const blockProps = useBlockProps();

	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );

	useEffect(
		() => setAttributes( { isDescendentOfQueryLoop } ),
		[ setAttributes, isDescendentOfQueryLoop ]
	);

	const isBlockThemeEnabled = getSettingWithCoercion(
		'is_block_theme_enabled',
		false,
		isBoolean
	);

	useEffect( () => {
		if ( isBlockThemeEnabled && attributes.imageSizing !== 'full-size' ) {
			setAttributes( { imageSizing: 'full-size' } );
		}
	}, [ attributes.imageSizing, isBlockThemeEnabled, setAttributes ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				>
					<ToggleControl
						label={ __(
							'Link to Product Page',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Links the image to the single product listing.',
							'woo-gutenberg-products-block'
						) }
						checked={ showProductLink }
						onChange={ () =>
							setAttributes( {
								showProductLink: ! showProductLink,
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Show On-Sale Badge',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Display a “sale” badge if the product is on-sale.',
							'woo-gutenberg-products-block'
						) }
						checked={ showSaleBadge }
						onChange={ () =>
							setAttributes( {
								showSaleBadge: ! showSaleBadge,
							} )
						}
					/>
					{ showSaleBadge && (
						<ToggleGroupControl
							label={ __(
								'Sale Badge Alignment',
								'woo-gutenberg-products-block'
							) }
							value={ saleBadgeAlign }
							onChange={ ( value ) =>
								setAttributes( { saleBadgeAlign: value } )
							}
						>
							<ToggleGroupControlOption
								value="left"
								label={ __(
									'Left',
									'woo-gutenberg-products-block'
								) }
							/>
							<ToggleGroupControlOption
								value="center"
								label={ __(
									'Center',
									'woo-gutenberg-products-block'
								) }
							/>
							<ToggleGroupControlOption
								value="right"
								label={ __(
									'Right',
									'woo-gutenberg-products-block'
								) }
							/>
						</ToggleGroupControl>
					) }
					{ ! isBlockThemeEnabled && (
						<ToggleGroupControl
							label={ __(
								'Image Sizing',
								'woo-gutenberg-products-block'
							) }
							help={ createInterpolateElement(
								__(
									'Product image cropping can be modified in the <a>Customizer</a>.',
									'woo-gutenberg-products-block'
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
							) }
							value={ imageSizing }
							onChange={ ( value ) =>
								setAttributes( { imageSizing: value } )
							}
						>
							<ToggleGroupControlOption
								value="full-size"
								label={ __(
									'Full Size',
									'woo-gutenberg-products-block'
								) }
							/>
							<ToggleGroupControlOption
								value="cropped"
								label={ __(
									'Cropped',
									'woo-gutenberg-products-block'
								) }
							/>
						</ToggleGroupControl>
					) }
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<Block { ...{ ...attributes, ...context } } />
			</Disabled>
		</div>
	);
};

export default Edit;
