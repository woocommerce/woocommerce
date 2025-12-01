/**
 * External dependencies
 */
import {
	BlockInstance,
	createBlock,
	registerBlockType,
} from '@wordpress/blocks';
import type { BlockEditProps } from '@wordpress/blocks';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import {
	useBlockProps,
	BlockPreview,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Button, Placeholder, Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { box, Icon } from '@wordpress/icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import { useEntityRecord } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import './editor.scss';
import './style.scss';
import { BLOCK_SLUG, TEMPLATES, TYPES } from './constants';
import { getTemplateDetailsBySlug } from './utils';
import {
	blockifiedProductCatalogConfig,
	blockifiedProductTaxonomyConfig,
} from './archive-product';
import * as blockifiedSingleProduct from './single-product';
import * as blockifiedProductSearchResults from './product-search-results';
import * as blockifiedOrderConfirmation from './order-confirmation';

import type { BlockifiedTemplateConfig } from './types';

type Attributes = {
	template: string;
	align: string;
};

const blockifiedFallbackConfig = {
	getBlockifiedTemplate: () => [],
	getDescription: () => '',
	onClickCallback: () => void 0,
};

const conversionConfig: { [ key: string ]: BlockifiedTemplateConfig } = {
	[ TYPES.productCatalog ]: blockifiedProductCatalogConfig,
	[ TYPES.productTaxonomy ]: blockifiedProductTaxonomyConfig,
	[ TYPES.singleProduct ]: blockifiedSingleProduct,
	[ TYPES.productSearchResults ]: blockifiedProductSearchResults,
	[ TYPES.orderConfirmation ]: blockifiedOrderConfirmation,
	fallback: blockifiedFallbackConfig,
};

const pickBlockClientIds = ( blocks: Array< BlockInstance > ) =>
	blocks.reduce< Array< string > >( ( acc, block ) => {
		if ( block.name === 'core/template-part' ) {
			return acc;
		}

		return [ ...acc, block.clientId ];
	}, [] );

const ConvertTemplate = ( { blockifyConfig, clientId, attributes } ) => {
	const { getButtonLabel, onClickCallback, getBlockifiedTemplate } =
		blockifyConfig;

	const [ isPopoverOpen, setIsPopoverOpen ] = useState( false );
	const { replaceBlock, selectBlock, replaceBlocks } =
		useDispatch( blockEditorStore );

	const { getBlocks } = useSelect( ( sel ) => {
		return {
			getBlocks: sel( blockEditorStore ).getBlocks,
		};
	}, [] );

	const { createInfoNotice } = useDispatch( noticesStore );

	return (
		<div className="wp-block-woocommerce-classic-template__placeholder-migration-button-container">
			<Button
				variant="primary"
				onClick={ () => {
					onClickCallback( {
						clientId,
						getBlocks,
						attributes,
						replaceBlock,
						selectBlock,
					} );
					createInfoNotice(
						__(
							'Template transformed into blocks!',
							'woocommerce'
						),
						{
							actions: [
								{
									label: __( 'Undo', 'woocommerce' ),
									onClick: () => {
										const clientIds = pickBlockClientIds(
											getBlocks()
										);

										replaceBlocks(
											clientIds,
											createBlock(
												'core/group',
												{
													layout: {
														inherit: true,
														type: 'constrained',
													},
												},
												[
													createBlock(
														'woocommerce/legacy-template',
														{
															template:
																attributes.template,
														}
													),
												]
											)
										);
									},
								},
							],
							type: 'snackbar',
						}
					);
				} }
				onMouseEnter={ () => setIsPopoverOpen( true ) }
				onMouseLeave={ () => setIsPopoverOpen( false ) }
				text={ getButtonLabel ? getButtonLabel() : '' }
			>
				{ isPopoverOpen && (
					<Popover resize={ false } placement="right-end">
						<div
							style={ {
								minWidth: '250px',
								width: '250px',
								maxWidth: '250px',
								minHeight: '300px',
								height: '300px',
								maxHeight: '300px',
								cursor: 'pointer',
							} }
						>
							<BlockPreview
								blocks={ getBlockifiedTemplate( {
									...attributes,
									isPreview: true,
								} ) }
								viewportWidth={ 1200 }
								additionalStyles={ [
									{
										css: 'body { padding: 20px !important; height: fit-content !important; overflow:hidden}',
									},
								] }
							/>
						</div>
					</Popover>
				) }
			</Button>
		</div>
	);
};

const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: BlockEditProps< Attributes > ) => {
	const blockProps = useBlockProps();
	const { currentPostId } = useSelect( ( sel ) => {
		return {
			// @ts-expect-error getCurrentPostId is not typed
			currentPostId: sel( editorStore ).getCurrentPostId(),
		};
	}, [] );

	const template = useEntityRecord(
		'postType',
		'wp_template',
		currentPostId
	);

	const templateSlug = template.record?.slug as string;

	const templateDetails = getTemplateDetailsBySlug( templateSlug, TEMPLATES );

	const templateTitle =
		template.record?.title.rendered?.toLowerCase() ?? attributes.template;
	const templatePlaceholder = templateDetails?.placeholder ?? 'fallback';
	const templateType = templateDetails?.type ?? 'fallback';

	useEffect(
		() =>
			setAttributes( {
				template: templateSlug ?? attributes.template,
				align: attributes.align ?? 'wide',
			} ),
		[ attributes.align, attributes.template, setAttributes ]
	);

	const { getDescription, getSkeleton, blockifyConfig } =
		conversionConfig[ templateType ];

	const skeleton = getSkeleton ? (
		getSkeleton()
	) : (
		<img
			className="wp-block-woocommerce-classic-template__placeholder-image"
			src={ `${ WC_BLOCKS_IMAGE_URL }template-placeholders/${ templatePlaceholder }.svg` }
			alt={ templateTitle }
		/>
	);

	const canConvert = !! templateDetails?.type;
	const placeholderDescription = getDescription( templateTitle );

	return (
		<div { ...blockProps }>
			<Placeholder className="wp-block-woocommerce-classic-template__placeholder">
				<div className="wp-block-woocommerce-classic-template__placeholder-wireframe">
					{ skeleton }
				</div>
				<div className="wp-block-woocommerce-classic-template__placeholder-copy">
					<div className="wp-block-woocommerce-classic-template__placeholder-copy__icon-container">
						<h1>{ __( 'WooCommerce', 'woocommerce' ) }</h1>
						<span>
							{ __(
								'Classic Template Placeholder',
								'woocommerce'
							) }
						</span>
					</div>
					{ canConvert && (
						<p
							dangerouslySetInnerHTML={ {
								__html: placeholderDescription,
							} }
						/>
					) }
					<p>
						{ __(
							'You cannot edit the content of this block. However, you can move it and place other blocks around it.',
							'woocommerce'
						) }
					</p>
					{ canConvert && blockifyConfig && (
						<ConvertTemplate
							clientId={ clientId }
							blockifyConfig={ blockifyConfig }
							attributes={ attributes }
						/>
					) }
				</div>
			</Placeholder>
		</div>
	);
};

registerBlockType( BLOCK_SLUG, {
	title: __( 'WooCommerce Classic Template', 'woocommerce' ),
	icon: (
		<Icon icon={ box } className="wc-block-editor-components-block-icon" />
	),
	category: 'woocommerce',
	apiVersion: 3,
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Renders classic WooCommerce PHP template.',
		'woocommerce'
	),
	supports: {
		interactivity: {
			clientNavigation: false,
		},
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
	},
	attributes: {
		/**
		 * Template attribute is used to determine which core PHP template gets rendered.
		 */
		template: {
			type: 'string',
			default: 'any',
		},
		align: {
			type: 'string',
			default: 'wide',
		},
	},
	edit: ( {
		attributes,
		clientId,
		setAttributes,
	}: BlockEditProps< Attributes > ) => {
		return (
			<Edit
				attributes={ attributes }
				setAttributes={ setAttributes }
				clientId={ clientId }
			/>
		);
	},
	save: () => null,
} );
