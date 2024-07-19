/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { __, sprintf } from '@wordpress/i18n';
import { useCollection } from '@woocommerce/base-context/hooks';
import { AttributeTerm } from '@woocommerce/types';
import {
	PanelBody,
	RadioControl,
	Spinner,
	ExternalLink,
	RangeControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { Icon, settings, menu } from '@wordpress/icons';
import { filter, filterThreeLines } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';
import './editor.scss';

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
			heading: __( 'Attribute', 'woocommerce' ),
			attributeId: 0,
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

const addHighestProductCountAttributeToTemplate = (
	template: InnerBlockTemplate[],
	highestProductCountAttribute: AttributeTerm | null
): InnerBlockTemplate[] => {
	if ( highestProductCountAttribute === null ) return template;

	return template.map( ( block ) => {
		const blockNameIndex = 0;
		const blockAttributesIndex = 1;
		const blockName = block[ blockNameIndex ];
		const blockAttributes = block[ blockAttributesIndex ];
		if (
			blockName === 'woocommerce/product-filter' &&
			blockAttributes?.filterType === 'attribute-filter'
		) {
			return [
				blockName,
				{
					...blockAttributes,
					heading: highestProductCountAttribute.name,
					attributeId: highestProductCountAttribute.id,
					metadata: {
						name: sprintf(
							/* translators: %s is referring to the filter attribute name. For example: Color, Size, etc. */
							__( '%s (Experimental)', 'woocommerce' ),
							highestProductCountAttribute.name
						),
					},
				},
			];
		}

		return block;
	} );
};

export const Edit = ( {
	setAttributes,
	attributes,
}: BlockEditProps< BlockAttributes > ) => {
	const blockProps = useBlockProps();
	const { results: attributeTerms, isLoading } =
		useCollection< AttributeTerm >( {
			namespace: '/wc/store/v1',
			resourceName: 'products/attributes',
		} );

	const highestProductCountAttribute =
		attributeTerms.reduce< AttributeTerm | null >(
			( attributeWithMostProducts, attribute ) => {
				if ( attributeWithMostProducts === null ) {
					return attribute;
				}
				return attribute.count > attributeWithMostProducts.count
					? attribute
					: attributeWithMostProducts;
			},
			null
		);
	const updatedTemplate = addHighestProductCountAttributeToTemplate(
		TEMPLATE,
		highestProductCountAttribute
	);

	if ( isLoading ) {
		return <Spinner />;
	}

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
			<InnerBlocks templateLock={ false } template={ updatedTemplate } />
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
