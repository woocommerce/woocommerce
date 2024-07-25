/**
 * External dependencies
 */
import { filter, filterThreeLines } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, menu, settings } from '@wordpress/icons';
import {
	ExternalLink,
	PanelBody,
	RadioControl,
	RangeControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';
import type { BlockAttributes } from './types';

const defaultAttribute = getSetting< AttributeSetting >(
	'defaultProductFilterAttribute'
);

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 3,
			style: { typography: { fontSize: '24px' } },
			content: __( 'Filters', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'active-filters',
			heading: __( 'Active', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'price-filter',
			heading: __( 'Price', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'stock-filter',
			heading: __( 'Status', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'attribute-filter',
			heading: defaultAttribute.attribute_label,
			attributeId: parseInt( defaultAttribute.attribute_id, 10 ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'rating-filter',
			heading: __( 'Rating', 'woocommerce' ),
		},
	],
	[
		'core/buttons',
		{ layout: { type: 'flex' } },
		[
			[
				'core/button',
				{
					text: __( 'Apply', 'woocommerce' ),
					className: 'wc-block-product-filters__apply-button',
					style: {
						border: {
							width: '0px',
							style: 'none',
						},
						typography: {
							textDecoration: 'none',
						},
						outline: 'none',
						fontSize: 'medium',
					},
				},
			],
		],
	],
];

export const Edit = ( {
	setAttributes,
	attributes,
}: BlockEditProps< BlockAttributes > ) => {
	const blockProps = useBlockProps();

	const templatePartEditUri = getSetting< string >(
		'templatePartProductFiltersOverlayEditUri',
		''
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Overlay', 'woocommerce' ) }>
					<ToggleGroupControl
						className="wc-block-editor-product-filters__overlay-toggle"
						isBlock={ true }
						value={ attributes.overlay }
						onChange={ ( value: BlockAttributes[ 'overlay' ] ) => {
							setAttributes( { overlay: value } );
						} }
					>
						<ToggleGroupControlOption
							value={ 'never' }
							label={ __( 'Never', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value={ 'mobile' }
							label={ __( 'Mobile', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value={ 'always' }
							label={ __( 'Always', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
					{ attributes.overlay === 'mobile' && (
						<>
							<RadioControl
								className="wc-block-editor-product-filters__overlay-button-style-toggle"
								label={ __( 'Button', 'woocommerce' ) }
								selected={ attributes.overlayButtonStyle }
								onChange={ (
									value: BlockAttributes[ 'overlayButtonStyle' ]
								) => {
									setAttributes( {
										overlayButtonStyle: value,
									} );
								} }
								options={ [
									{
										value: 'label-icon',
										label: __(
											'Label and icon',
											'woocommerce'
										),
									},
									{
										value: 'label',
										label: __(
											'Label only',
											'woocommerce'
										),
									},
									{
										value: 'icon',
										label: __( 'Icon only', 'woocommerce' ),
									},
								] }
							/>
							{ attributes.overlayButtonStyle !== 'label' && (
								<>
									<ToggleGroupControl
										className="wc-block-editor-product-filters__overlay-button-toggle"
										isBlock={ true }
										value={ attributes.overlayIcon }
										onChange={ (
											value: BlockAttributes[ 'overlayIcon' ]
										) => {
											setAttributes( {
												overlayIcon: value,
											} );
										} }
									>
										<ToggleGroupControlOption
											value={ 'filter-icon-1' }
											aria-label={ __(
												'Filter icon 1',
												'woocommerce'
											) }
											label={
												<Icon
													size={ 32 }
													icon={ filter }
												/>
											}
										/>
										<ToggleGroupControlOption
											value={ 'filter-icon-2' }
											aria-label={ __(
												'Filter icon 2',
												'woocommerce'
											) }
											label={
												<Icon
													size={ 32 }
													icon={ filterThreeLines }
												/>
											}
										/>
										<ToggleGroupControlOption
											value={ 'filter-icon-3' }
											aria-label={ __(
												'Filter icon 3',
												'woocommerce'
											) }
											label={
												<Icon
													size={ 32 }
													icon={ menu }
												/>
											}
										/>
										<ToggleGroupControlOption
											value={ 'filter-icon-4' }
											aria-label={ __(
												'Filter icon 4',
												'woocommerce'
											) }
											label={
												<Icon
													size={ 32 }
													icon={ settings }
												/>
											}
										/>
									</ToggleGroupControl>
									<RangeControl
										label={ __(
											'Icon size',
											'woocommerce'
										) }
										className="wc-block-editor-product-filters__overlay-button-size"
										value={ attributes.overlayIconSize }
										onChange={ ( value: number ) =>
											setAttributes( {
												overlayIconSize: value,
											} )
										}
										min={ 20 }
										max={ 80 }
									/>
								</>
							) }
						</>
					) }
					{ attributes.overlay !== 'never' && (
						<ExternalLink
							href={ templatePartEditUri }
							className="wc-block-editor-product-filters__overlay-link"
						>
							{ __( 'Edit overlay', 'woocommerce' ) }
						</ExternalLink>
					) }
				</PanelBody>
			</InspectorControls>
			<InnerBlocks templateLock={ false } template={ TEMPLATE } />
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
