/**
 * External dependencies
 */
import { PluginTemplateSettingPanel } from '@wordpress/edit-site';
import { subscribe, select, useSelect, useDispatch } from '@wordpress/data';
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { Button, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useMemo } from '@wordpress/element';
import { useEntityRecord } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { isSiteEditorPage } from '@woocommerce/utils';

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
				<PluginTemplateSettingPanel>
					<PanelBody className="wc-block-editor-revert-button-container">
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
					</PanelBody>
				</PluginTemplateSettingPanel>
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

let currentTemplateId: string | undefined;
subscribe( () => {
	const previousTemplateId = currentTemplateId;
	const store = select( 'core/edit-site' );

	if ( ! isSiteEditorPage( store ) ) {
		return;
	}

	currentTemplateId = store?.getEditedPostId();

	if ( previousTemplateId === currentTemplateId ) {
		return;
	}

	const isWooTemplate = templateSlugs.some( ( slug ) =>
		currentTemplateId?.includes( slug )
	);

	const hasSupportForPluginTemplateSettingPanel =
		PluginTemplateSettingPanel !== undefined;

	if ( isWooTemplate && hasSupportForPluginTemplateSettingPanel ) {
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
