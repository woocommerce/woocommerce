/**
 * External dependencies
 */
import '@wordpress/editor';
import '@wordpress/format-library';
import { BlockInstance, serialize, parse } from '@wordpress/blocks';
import {
	createElement,
	useEffect,
	useState,
	useMemo,
} from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { useSelect, useDispatch, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import {
	// @ts-ignore
	BlockBreadcrumb,
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
	BlockList,
	// @ts-ignore
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
import { parseProductToBlocks } from '../../utils/parse-product-to-blocks';
import { Sidebar } from '../sidebar';

type BlockEditorProps = {
	product: Partial< Product >;
	settings: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function BlockEditor( {
	product,
	settings: _settings,
}: BlockEditorProps ) {
	const [ blocks, updateBlocks ] = useState< BlockInstance[] >( [] );

	const canUserCreateMedia = useSelect( ( select: typeof WPSelect ) => {
		const _canUserCreateMedia = select( 'core' ).canUser(
			'create',
			'media'
		);
		return _canUserCreateMedia || _canUserCreateMedia !== false;
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
				// @ts-ignore
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
		console.log( 'handleUpdateBlocks', _blocks );
		updateBlocks( _blocks );
	}

	useEffect( () => {
		function UpdateBlocks() {
			console.time( 'parseProductToBlocks' );
			const newBlocks = parseProductToBlocks( product );
			console.timeEnd( 'parseProductToBlocks' );

			handleUpdateBlocks( newBlocks );
		}
		Object.defineProperty( UpdateBlocks, 'name', {
			value: 'UpdateBlocks',
			writable: false,
		} );

		const id = setTimeout( UpdateBlocks, 2000 );
		return () => clearTimeout( id );
	}, [ product ] );

	function handlePersistBlocks( newBlocks: BlockInstance[] ) {
		updateBlocks( newBlocks );
	}

	return (
		<div className="woocommerce-product-block-editor">
			<BlockEditorProvider
				value={ blocks }
				onInput={ handleUpdateBlocks }
				onChange={ handlePersistBlocks }
				settings={ settings }
			>
				<BlockBreadcrumb />
				<Sidebar.InspectorFill>
					<BlockInspector />
				</Sidebar.InspectorFill>
				<div className="editor-styles-wrapper">
					{ /* @ts-ignore */ }
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
		</div>
	);
}
