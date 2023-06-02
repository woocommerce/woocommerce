/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useViewportMatch } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	NavigableToolbar,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { plus } from '@wordpress/icons';
import {
	createElement,
	Fragment,
	useRef,
	useCallback,
} from '@wordpress/element';
import { MouseEvent } from 'react';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore ToolbarItem exists in WordPress components.
// eslint-disable-next-line @woocommerce/dependency-group
import { Button, ToolbarItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import EditorHistoryRedo from './header-toolbar/editor-history-redo';
import EditorHistoryUndo from './header-toolbar/editor-history-undo';

type HeaderToolbarProps = {
	isInserterOpened: boolean;
	setIsInserterOpened: ( value: boolean ) => void;
};

export function HeaderToolbar( {
	isInserterOpened,
	setIsInserterOpened,
}: HeaderToolbarProps ) {
	const isWideViewport = useViewportMatch( 'wide' );
	const inserterButton = useRef< HTMLButtonElement | null >( null );
	const { isInserterEnabled } = useSelect( ( select ) => {
		const {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore These selectors are available in the block data store.
			hasInserterItems,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore These selectors are available in the block data store.
			getBlockRootClientId,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore These selectors are available in the block data store.
			getBlockSelectionEnd,
		} = select( blockEditorStore );

		return {
			isInserterEnabled: hasInserterItems(
				getBlockRootClientId( getBlockSelectionEnd() )
			),
		};
	}, [] );

	/* translators: accessibility text for the editor toolbar */
	const toolbarAriaLabel = __( 'Document tools', 'woocommerce' );

	const toggleInserter = useCallback( () => {
		if ( isInserterOpened ) {
			// Focusing the inserter button should close the inserter popover.
			// However, there are some cases it won't close when the focus is lost.
			// See https://github.com/WordPress/gutenberg/issues/43090 for more details.
			inserterButton.current?.focus();
			setIsInserterOpened( false );
		} else {
			setIsInserterOpened( true );
		}
	}, [ isInserterOpened, setIsInserterOpened ] );

	return (
		<NavigableToolbar
			className="woocommerce-iframe-editor__header-toolbar"
			aria-label={ toolbarAriaLabel }
		>
			<div className="woocommerce-iframe-editor__header-toolbar-left">
				<ToolbarItem
					ref={ inserterButton }
					as={ Button }
					className="woocommerce-iframe-editor__header-toolbar-inserter-toggle"
					variant="primary"
					isPressed={ isInserterOpened }
					onMouseDown={ (
						event: MouseEvent< HTMLButtonElement >
					) => {
						event.preventDefault();
					} }
					onClick={ toggleInserter }
					disabled={ ! isInserterEnabled }
					icon={ plus }
					label={
						! isInserterOpened
							? __( 'Add', 'woocommerce' )
							: __( 'Close', 'woocommerce' )
					}
					showTooltip
				/>
				{ isWideViewport && (
					<>
						<ToolbarItem as={ EditorHistoryUndo } />
						<ToolbarItem as={ EditorHistoryRedo } />
					</>
				) }
			</div>
		</NavigableToolbar>
	);
}
