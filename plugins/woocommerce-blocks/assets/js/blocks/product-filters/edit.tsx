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
	InnerBlockTemplate,
	createBlock,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
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
import { getInnerBlockBy, getInnerBlockByName } from './utils';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/template-part',

		{
			slug: 'product-filter-blocks',
			theme: 'woocommerce/woocommerce',
			lock: { remove: true },
		},
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

	const [ overlayNavigationAttributes, setOverlayNavigationAttributes ] =
		useLocalStorageState< Record< string, unknown > >(
			'product-filters-overlay-navigation-attributes',
			{
				lock: { remove: true },
				triggerType: 'open-overlay',
			}
		);

	const { updateBlockAttributes, insertBlock, removeBlock } =
		dispatch( 'core/block-editor' );

	const currentBlock = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlock( clientId );
	} );

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
							const navigationBlock = getInnerBlockByName(
								currentBlock,
								'woocommerce/product-filters-overlay-navigation'
							);
							const filterBlocksPart = getInnerBlockBy(
								currentBlock,
								function ( innerBlock ) {
									return (
										innerBlock.name ===
											'core/template-part' &&
										innerBlock.attributes.slug ===
											'product-filter-blocks'
									);
								}
							);

							if ( navigationBlock && value === 'never' ) {
								setOverlayNavigationAttributes(
									navigationBlock.attributes
								);
								updateBlockAttributes(
									navigationBlock.clientId,
									{
										lock: {},
									}
								);
								removeBlock( navigationBlock.clientId, false );
							}
							if ( ! navigationBlock && value !== 'never' ) {
								insertBlock(
									createBlock(
										'woocommerce/product-filters-overlay-navigation',
										{
											...overlayNavigationAttributes,
											lock: { remove: true },
											triggerType: 'open-overlay',
										}
									),
									0,
									currentBlock?.clientId,
									false
								);
							}

							if ( filterBlocksPart && value === 'always' ) {
								updateBlockAttributes(
									filterBlocksPart.clientId,
									{
										lock: {},
									}
								);
								removeBlock( filterBlocksPart.clientId, false );
							}
							if ( ! filterBlocksPart && value !== 'always' ) {
								insertBlock(
									createBlock( 'core/template-part', {
										slug: 'product-filter-blocks',
										theme: 'woocommerce/woocommerce',
										lock: { remove: true },
									} ),
									currentBlock?.innerBlocks.length,
									currentBlock?.clientId,
									false
								);
							}
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
