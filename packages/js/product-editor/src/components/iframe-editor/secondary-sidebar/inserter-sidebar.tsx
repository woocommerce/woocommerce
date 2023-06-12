/**
 * External dependencies
 */
import { Button, VisuallyHidden } from '@wordpress/components';
import { close } from '@wordpress/icons';
import {
	useViewportMatch,
	__experimentalUseDialog as useDialog,
} from '@wordpress/compose';
import {
	createElement,
	useCallback,
	useContext,
	useEffect,
	useRef,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	store as blockEditorStore,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This is actively used in the GB repo and probably safe to use.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
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
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore These selectors are available in the block data store.
		const { getBlockRootClientId } = select( blockEditorStore );

		return {
			rootClientId: getBlockRootClientId(),
		};
	} );

	const closeInserter = useCallback( () => {
		return setIsInserterOpened( false );
	}, [ setIsInserterOpened ] );

	const TagName = ! isMobileViewport ? VisuallyHidden : 'div';
	const [ inserterDialogRef, inserterDialogProps ] = useDialog( {
		onClose: closeInserter,
		focusOnMount: false,
	} );

	const libraryRef = useRef< Library | null >( null );
	useEffect( () => {
		libraryRef.current?.focusSearch();
	}, [] );

	return (
		<div
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore Types are not provided by useDialog.
			ref={ inserterDialogRef }
			{ ...inserterDialogProps }
			className="woocommerce-iframe-editor__inserter-panel"
		>
			<TagName className="woocommerce-iframe-editor__inserter-panel-header">
				<Button
					icon={ close }
					onClick={ closeInserter }
					label={ __( 'Close block inserter', 'woocommerce' ) }
				/>
			</TagName>
			<div className="woocommerce-iframe-editor__inserter-panel-content">
				<Library
					showInserterHelpPanel
					shouldFocusBlock={ isMobileViewport }
					rootClientId={ rootClientId }
					ref={ libraryRef }
				/>
			</div>
		</div>
	);
}
