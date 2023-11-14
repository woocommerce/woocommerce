/**
 * External dependencies
 */
import { synchronizeBlocksWithTemplate, Template } from '@wordpress/blocks';
import { createElement, useMemo, useLayoutEffect } from '@wordpress/element';
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import { PluginArea } from '@wordpress/plugins';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockContextProvider,
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
	BlockList,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	EditorSettings,
	EditorBlockListSettings,
	ObserveTyping,
} from '@wordpress/block-editor';
// It doesn't seem to notice the External dependency block whn @ts-ignore is added.
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityBlockEditor,
} from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useConfirmUnsavedProductChanges } from '../../hooks/use-confirm-unsaved-product-changes';
import { ProductEditorContext } from '../../types';
import { PostTypeContext } from '../../contexts/post-type-context';

type BlockEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
> & {
	templates?: Record< string, Template[] >;
};

type BlockEditorProps = {
	context: Partial< ProductEditorContext >;
	productType: string;
	productId: number;
	settings: BlockEditorSettings | undefined;
};

export function BlockEditor( {
	context,
	settings: _settings,
	productType,
	productId,
}: BlockEditorProps ) {
	useConfirmUnsavedProductChanges( productType );

	const canUserCreateMedia = useSelect( ( select: typeof WPSelect ) => {
		const { canUser } = select( 'core' );
		return canUser( 'create', 'media', '' ) !== false;
	}, [] );

	const settings: BlockEditorSettings = useMemo( () => {
		const mediaSettings = canUserCreateMedia
			? {
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
			  }
			: {};

		return {
			..._settings,
			...mediaSettings,
			templateLock: 'all',
		};
	}, [ canUserCreateMedia, _settings ] );

	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		productType,
		{ id: productId }
	);

	const { updateEditorSettings } = useDispatch( 'core/editor' );

	useLayoutEffect( () => {
		const template = settings?.templates?.[ productType ];

		if ( ! template ) {
			return;
		}

		const blockInstances = synchronizeBlocksWithTemplate( [], template );

		onChange( blockInstances, {} );

		updateEditorSettings( settings ?? {} );
	}, [ productType, productId ] );

	if ( ! blocks ) {
		return null;
	}

	return (
		<div className="woocommerce-product-block-editor">
			<BlockContextProvider value={ context }>
				<BlockEditorProvider
					value={ blocks }
					onInput={ onInput }
					onChange={ onChange }
					settings={ settings }
				>
					{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
					{ /* @ts-ignore No types for this exist yet. */ }
					<BlockEditorKeyboardShortcuts.Register />
					<BlockTools>
						<ObserveTyping>
							<BlockList className="woocommerce-product-block-editor__block-list" />
						</ObserveTyping>
					</BlockTools>
					{ /* eslint-disable-next-line @typescript-eslint/no-non-null-assertion */ }
					<PostTypeContext.Provider value={ context.postType! }>
						{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
						<PluginArea scope="woocommerce-product-block-editor" />
					</PostTypeContext.Provider>
				</BlockEditorProvider>
			</BlockContextProvider>
		</div>
	);
}
