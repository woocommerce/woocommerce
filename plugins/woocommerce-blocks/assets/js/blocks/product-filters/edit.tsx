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
import { __ } from '@wordpress/i18n';
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
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { ProductFiltersBlockAttributes } from './types';
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
				},
			];
		}

		return block;
	} );
};

export const Edit = ( {
	setAttributes,
	attributes,
}: BlockEditProps< ProductFiltersBlockAttributes > ) => {
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
						onChange={ ( value: 'never' | 'mobile' | 'always' ) => {
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
					{ ( attributes.overlay === 'always' ||
						attributes.overlay === 'mobile' ) && (
						<p className="wc-block-editor-product-filters__overlay-link">
							<ExternalLink href={ templatePartEditUri }>
								{ __( 'Edit overlay', 'woocommerce' ) }
							</ExternalLink>
						</p>
					) }
					{ ( attributes.overlay !== 'never' ) && (
						<>
							<RadioControl
								className="wc-block-editor-product-filters__overlay-button-style-toggle"
								label={ __( 'BUTTON', 'woocommerce' ) }
								selected={ attributes.overlayButtonStyle }
								onChange={ (
									value: 'label-icon' | 'label' | 'icon'
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
											value:
												| 'filter-icon-1'
												| 'filter-icon-2'
												| 'filter-icon-3'
												| 'filter-icon-4'
										) => {
											setAttributes( {
												overlayIcon: value,
											} );
										} }
									>
										<ToggleGroupControlOption
											value={ 'filter-icon-1' }
											label={
												<Icon
													size={ 32 }
													icon={
														<svg
															xmlns="http://www.w3.org/2000/svg"
															viewBox="0 0 24 24"
															width="32"
															height="32"
															aria-hidden="true"
														>
															<path d="M10.541 4.20007H5.20245C4.27908 4.20007 3.84904 5.34461 4.54394 5.95265L10.541 11.2001V16.2001L10.541 17.9428C10.541 18.1042 10.619 18.2558 10.7504 18.3496L13.2504 20.1353C13.5813 20.3717 14.041 20.1352 14.041 19.7285V11.2001L19.3339 5.90718C19.9639 5.27722 19.5177 4.20007 18.6268 4.20007H13.041H10.541Z" />
														</svg>
													}
												/>
											}
										/>
										<ToggleGroupControlOption
											value={ 'filter-icon-2' }
											label={
												<Icon
													size={ 32 }
													icon={
														<svg
															xmlns="http://www.w3.org/2000/svg"
															viewBox="0 0 24 24"
															width="32"
															height="32"
															aria-hidden="true"
														>
															<path
																d="M4 11.5H8V10H4V11.5ZM0 0V1.5H12V0H0ZM2 6.5H10V5H2V6.5Z"
																transform="translate(6,6)"
															/>
														</svg>
													}
												/>
											}
										/>
										<ToggleGroupControlOption
											value={ 'filter-icon-3' }
											label={
												<Icon
													size={ 32 }
													icon={ menu }
												/>
											}
										/>
										<ToggleGroupControlOption
											value={ 'filter-icon-4' }
											label={
												<Icon
													size={ 32 }
													icon={ settings }
												/>
											}
										/>
									</ToggleGroupControl>
									<div className="wc-block-editor-product-filters__overlay-button-size">
										<RangeControl
											label={ __(
												'ICON SIZE',
												'woocommerce'
											) }
											value={ attributes.overlayIconSize }
											onChange={ ( value ) =>
												setAttributes( {
													overlayIconSize: value,
												} )
											}
											min={ 20 }
											max={ 80 }
										/>
									</div>
								</>
							) }
						</>
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
