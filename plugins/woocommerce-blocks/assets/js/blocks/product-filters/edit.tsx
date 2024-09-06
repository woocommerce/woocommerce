/**
 * External dependencies
 */
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
import { useEffect } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';
import {
	ExternalLink,
	PanelBody,
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
import { type BlockAttributes } from './types';
import { BlockOverlayAttribute } from './constants';

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

	useEffect( () => {
		const overlayBlockIds = select( 'core/block-editor' ).getBlocksByName(
			'woocommerce/product-filters-overlay-navigation'
		);

		if ( attributes.overlay === 'never' ) {
			for ( const blockId of overlayBlockIds ) {
				dispatch( 'core/block-editor' ).removeBlock( blockId );
			}
		}
	}, [ attributes.overlay ] );

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
							value={ BlockOverlayAttribute.NEVER }
							label={ __( 'Never', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value={ BlockOverlayAttribute.MOBILE }
							label={ __( 'Mobile', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value={ BlockOverlayAttribute.ALWAYS }
							label={ __( 'Always', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
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
