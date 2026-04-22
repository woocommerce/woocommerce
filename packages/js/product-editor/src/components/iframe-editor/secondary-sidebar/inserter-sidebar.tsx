/**
 * External dependencies
 */
import { useViewportMatch } from '@wordpress/compose';
import {
	createElement,
	useCallback,
	useContext,
	useEffect,
	useRef,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { ESCAPE } from '@wordpress/keycodes';
import {
	store as blockEditorStore,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	// @ts-expect-error __experimentalLibrary is not exported from @wordpress/block-editor's public types.
	__experimentalLibrary as Library,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';

export default function InserterSidebar() {
	const { setIsInserterOpened } = useContext( EditorContext );
	const isMobileViewport = useViewportMatch( 'medium', '<' );
	const { rootClientId } = useSelect( ( select ) => {
		const { getBlockRootClientId } = select( blockEditorStore );

		return {
			rootClientId: getBlockRootClientId( '' ),
		};
	}, [] );

	const closeInserter = useCallback( () => {
		return setIsInserterOpened( false );
	}, [ setIsInserterOpened ] );

	const closeOnEscape = useCallback(
		( event: React.KeyboardEvent ) => {
			if ( event.keyCode === ESCAPE && ! event.defaultPrevented ) {
				event.preventDefault();
				closeInserter();
			}
		},
		[ closeInserter ]
	);

	const libraryRef = useRef< Library | null >( null );
	useEffect( () => {
		// Focus the search input when the inserter is opened,
		// if using an older version of the Library.
		libraryRef.current?.focusSearch?.();
	}, [] );

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div
			onKeyDown={ ( event ) => closeOnEscape( event ) }
			className="woocommerce-iframe-editor__inserter-panel"
		>
			<div className="woocommerce-iframe-editor__inserter-panel-content">
				<Library
					showInserterHelpPanel
					shouldFocusBlock={ isMobileViewport }
					rootClientId={ rootClientId }
					ref={ libraryRef }
					onClose={ closeInserter }
					onSelect={ () => {
						if ( isMobileViewport ) {
							closeInserter();
						}
					} }
				/>
			</div>
		</div>
	);
}
