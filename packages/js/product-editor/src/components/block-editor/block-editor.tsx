/**
 * External dependencies
 */
import { synchronizeBlocksWithTemplate, Template } from '@wordpress/blocks';
import { createElement, useMemo, useLayoutEffect } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { useSelect, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
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
	WritingFlow,
	ObserveTyping,
} from '@wordpress/block-editor';
// It doesn't seem to notice the External dependency block whn @ts-ignore is added.
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityBlockEditor,
} from '@wordpress/core-data';

type BlockEditorProps = {
	context: {
		[ key: string ]: unknown;
	};
	product: Partial< Product >;
	settings:
		| ( Partial< EditorSettings & EditorBlockListSettings > & {
				template?: Template[];
		  } )
		| undefined;
};

export function BlockEditor( {
	context,
	settings: _settings,
	product,
}: BlockEditorProps ) {
	const canUserCreateMedia = useSelect( ( select: typeof WPSelect ) => {
		const { canUser } = select( 'core' );
		return canUser( 'create', 'media', '' ) !== false;
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

	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'product',
		{ id: product.id }
	);

	useLayoutEffect( () => {
		onChange(
			synchronizeBlocksWithTemplate( [], _settings?.template ),
			{}
		);
	}, [ product.id ] );

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
