/**
 * External dependencies
 */
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { subscribe, select, useSelect, useDispatch } from '@wordpress/data';
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useMemo } from '@wordpress/element';
import { useEntityRecord } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { isSiteEditorPage } from '@woocommerce/utils';
import { isString } from '@woocommerce/types';

// eslint-disable-next-line @woocommerce/dependency-group
import {
	registerPlugin,
	unregisterPlugin,
	getPlugin,
} from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import './style.scss';

const hasLegacyTemplateBlock = ( blocks: Array< BlockInstance > ): boolean => {
	return blocks.some( ( block ) => {
		return (
			block.name === 'woocommerce/legacy-template' ||
			hasLegacyTemplateBlock( block.innerBlocks )
		);
	} );
};

const pickBlockClientIds = ( blocks: Array< BlockInstance > ) =>
	blocks.reduce< Array< string > >( ( acc, block ) => {
		if ( block.name === 'core/template-part' ) {
			return acc;
		}

		return [ ...acc, block.clientId ];
	}, [] );

const RevertClassicTemplateButton = () => {
	const { blocks, editedPostId } = useSelect( ( sel ) => {
		return {
			blocks: sel( blockEditorStore ).getBlocks(),
			editedPostId: sel( 'core/edit-site' ).getEditedPostId(),
		};
	}, [] );

	const { replaceBlocks } = useDispatch( blockEditorStore );

	const template = useEntityRecord< {
		slug: string;
		title: {
			rendered?: string;
			row: string;
		};
	} >( 'postType', 'wp_template', editedPostId );

	const isLegacyTemplateBlockAdded = useMemo(
		() => hasLegacyTemplateBlock( blocks ),
		[ blocks ]
	);

	const clientIds = useMemo( () => pickBlockClientIds( blocks ), [ blocks ] );

	return (
		<>
			{ ! isLegacyTemplateBlockAdded && (
				<PluginDocumentSettingPanel name="wc-block-editor-revert-button-panel">
					<div className="wc-block-editor-revert-button-container">
						<Button
							variant="secondary"
							onClick={ () => {
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
														template?.record?.slug,
												}
											),
										]
									)
								);
							} }
						>
							{ __(
								'Revert to Classic Template',
								'woocommerce'
							) }
						</Button>
						<span>
							{ createInterpolateElement(
								__(
									`The <strongText /> template doesn’t allow for reordering or customizing blocks, but might work better with your extensions.`,
									'woocommerce'
								),
								{
									strongText: (
										<strong>
											{ template?.record?.title?.rendered
												? `${ template.record.title.rendered } (Classic)`
												: '' }
										</strong>
									),
								}
							) }
						</span>
					</div>
				</PluginDocumentSettingPanel>
			) }
		</>
	);
};

const templateSlugs = [
	'single-product',
	'archive-product',
	'product-search-results',
	'taxonomy-product_cat',
	'taxonomy-product_tag',
	'taxonomy-product_attribute',
];

const REVERT_BUTTON_PLUGIN_NAME = 'woocommerce-blocks-revert-button-templates';

let currentTemplateSlug: string | undefined;
subscribe( () => {
	const previousTemplateSlug = currentTemplateSlug;

	if ( ! isSiteEditorPage() ) {
		return;
	}

	// @ts-expect-error getEditedPostSlug is not typed
	currentTemplateSlug = select( editorStore ).getEditedPostSlug();

	if ( previousTemplateSlug === currentTemplateSlug ) {
		return;
	}

	const isWooTemplate = templateSlugs.some( ( slug ) =>
		isString( currentTemplateSlug )
			? currentTemplateSlug.includes( slug )
			: false
	);

	const hasSupportForPluginDocumentSettingPanel =
		PluginDocumentSettingPanel !== undefined;

	if ( isWooTemplate && hasSupportForPluginDocumentSettingPanel ) {
		if ( getPlugin( REVERT_BUTTON_PLUGIN_NAME ) ) {
			return;
		}

		return registerPlugin( REVERT_BUTTON_PLUGIN_NAME, {
			render: RevertClassicTemplateButton,
		} );
	}

	if ( getPlugin( REVERT_BUTTON_PLUGIN_NAME ) === undefined ) {
		return;
	}

	unregisterPlugin( REVERT_BUTTON_PLUGIN_NAME );
}, 'core/edit-site' );
