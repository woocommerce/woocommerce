/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	BlockEditProps,
	BlockInstance,
	InnerBlockTemplate,
	createBlock,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';
import { useLocalStorageState } from '@woocommerce/base-hooks';
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

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 3,
			style: { typography: { fontSize: '24px' } },
			content: __( 'Filters', 'woocommerce' ),
		},
	],
	[ 'woocommerce/product-filter-active' ],
	[ 'woocommerce/product-filter-attribute' ],
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
	clientId,
}: BlockEditProps< BlockAttributes > ) => {
	const blockProps = useBlockProps();

	const templatePartEditUri = getSetting< string >(
		'templatePartProductFiltersOverlayEditUri',
		''
	);

	const [
		productFiltersOverlayNavigationAttributes,
		setProductFiltersOverlayNavigationAttributes,
	] = useLocalStorageState< Record< string, unknown > >(
		'product-filters-overlay-navigation-attributes',
		{}
	);

	useEffect( () => {
		const filtersClientIds = select( 'core/block-editor' ).getBlocksByName(
			'woocommerce/product-filters'
		);

		let overlayBlock:
			| BlockInstance< { [ k: string ]: unknown } >
			| undefined;

		for ( const filterClientId of filtersClientIds ) {
			const filterBlock =
				select( 'core/block-editor' ).getBlock( filterClientId );

			if ( filterBlock ) {
				for ( const innerBlock of filterBlock.innerBlocks ) {
					if (
						innerBlock.name ===
							'woocommerce/product-filters-overlay-navigation' &&
						innerBlock.attributes.triggerType === 'open-overlay'
					) {
						overlayBlock = innerBlock;
					}
				}
			}
		}

		if ( attributes.overlay === 'never' && overlayBlock ) {
			setProductFiltersOverlayNavigationAttributes(
				overlayBlock.attributes
			);

			dispatch( 'core/block-editor' ).updateBlockAttributes(
				overlayBlock.clientId,
				{
					lock: {},
				}
			);

			dispatch( 'core/block-editor' ).removeBlock(
				overlayBlock.clientId
			);
		} else if ( attributes.overlay !== 'never' && ! overlayBlock ) {
			if ( productFiltersOverlayNavigationAttributes ) {
				productFiltersOverlayNavigationAttributes.triggerType =
					'open-overlay';
			}

			dispatch( 'core/block-editor' ).insertBlock(
				createBlock(
					'woocommerce/product-filters-overlay-navigation',
					productFiltersOverlayNavigationAttributes
						? productFiltersOverlayNavigationAttributes
						: {
								align: 'left',
								triggerType: 'open-overlay',
								lock: { move: true, remove: true },
						  }
				),
				0,
				clientId,
				false
			);
		}
	}, [
		attributes.overlay,
		clientId,
		productFiltersOverlayNavigationAttributes,
		setProductFiltersOverlayNavigationAttributes,
	] );

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
