/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, BlockInstance } from '@wordpress/blocks';
import { createElement, useState, useMemo } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { useSelect, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockBreadcrumb,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockContextProvider,
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
	BlockList,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	BlockInspector,
	EditorSettings,
	EditorBlockListSettings,
	WritingFlow,
	ObserveTyping,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { Sidebar } from '../sidebar';
import { Tabs } from '../tabs';
import tabBlock from '../tab/block.json';
import sectionBlock from '../section/block.json';

type BlockEditorProps = {
	product: Partial< Product >;
	settings: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function BlockEditor( { settings: _settings }: BlockEditorProps ) {
	const initialBlocks = [
		createBlock(
			tabBlock.name,
			{
				id: 'general',
				title: 'General',
			},
			[
				createBlock(
					sectionBlock.name,
					{
						title: __( 'Product details', 'woocommerce' ),
						description: __(
							'This info will be displayed on the product page, category pages, social media, and search results.',
							'woocommerce'
						),
					},
					[
						createBlock( 'core/paragraph', {
							content: 'First paragraph',
						} ),
						createBlock( 'core/paragraph', {
							content: 'Second paragraph',
						} ),
					]
				),
			]
		),
		createBlock(
			tabBlock.name,
			{
				id: 'shipping',
				title: 'Shipping',
			},
			[
				createBlock(
					sectionBlock.name,
					{
						title: __( 'Shipping details', 'woocommerce' ),
						description: __(
							'This info will be displayed on the product page, category pages, social media, and search results.',
							'woocommerce'
						),
					},
					[
						createBlock( 'core/paragraph', {
							content: 'First paragraph',
						} ),
						createBlock( 'core/paragraph', {
							content: 'Second paragraph',
						} ),
					]
				),
			]
		),
	];

	const [ selectedTab, setSelectedTab ] = useState< string | null >( null );
	const [ blocks, updateBlocks ] =
		useState< BlockInstance[] >( initialBlocks );

	const canUserCreateMedia = useSelect( ( select: typeof WPSelect ) => {
		const { canUser } = select( 'core' ) as Record<
			string,
			( ...args: string[] ) => boolean
		>;
		return canUser( 'create', 'media' ) !== false;
	}, [] );

	const settings = useMemo( () => {
		if ( ! canUserCreateMedia ) {
			return _settings;
		}
		return {
			..._settings,
			mediaUpload( {
				onError,
				...rest
			}: {
				onError: ( message: string ) => void;
			} ) {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore No types for this exist yet.
				uploadMedia( {
					wpAllowedMimeTypes:
						_settings?.allowedMimeTypes || undefined,
					onError: ( { message } ) => onError( message ),
					...rest,
				} );
			},
		};
	}, [ canUserCreateMedia, _settings ] );

	/**
	 * Wrapper for updating blocks. Required as `onInput` callback passed to
	 * `BlockEditorProvider` is now called with more than 1 argument. Therefore
	 * attempting to setState directly via `updateBlocks` will trigger an error
	 * in React.
	 *
	 * @param  _blocks
	 */
	function handleUpdateBlocks( _blocks: BlockInstance[] ) {
		updateBlocks( _blocks );
	}

	function handlePersistBlocks( newBlocks: BlockInstance[] ) {
		updateBlocks( newBlocks );
	}

	return (
		<div className="woocommerce-product-block-editor">
			<BlockContextProvider value={ { selectedTab } }>
				<BlockEditorProvider
					value={ blocks }
					onInput={ handleUpdateBlocks }
					onChange={ handlePersistBlocks }
					settings={ settings }
				>
					<Tabs onChange={ setSelectedTab } />
					<BlockBreadcrumb />
					<Sidebar.InspectorFill>
						<BlockInspector />
					</Sidebar.InspectorFill>
					<div className="editor-styles-wrapper">
						{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
						{ /* @ts-ignore No types for this exist yet. */ }
						<BlockEditorKeyboardShortcuts.Register />
						<BlockTools>
							<WritingFlow>
								<ObserveTyping>
									<BlockList className="woocommerce-product-block-editor__block-list" />
								</ObserveTyping>
							</WritingFlow>
						</BlockTools>
					</div>
				</BlockEditorProvider>
			</BlockContextProvider>
		</div>
	);
}
