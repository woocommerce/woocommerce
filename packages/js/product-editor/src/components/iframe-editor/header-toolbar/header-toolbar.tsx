/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useViewportMatch } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import {
	createElement,
	useRef,
	useCallback,
	useContext,
} from '@wordpress/element';
import { MouseEvent } from 'react';
import {
	NavigableToolbar,
	store as blockEditorStore,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore ToolSelector exists in WordPress components.
	ToolSelector,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore ToolbarItem exists in WordPress components.
// eslint-disable-next-line @woocommerce/dependency-group
import { Button, ToolbarItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';
import EditorHistoryRedo from './editor-history-redo';
import EditorHistoryUndo from './editor-history-undo';
import { DocumentOverview } from './document-overview';
import { ShowBlockInspectorPanel } from './show-block-inspector-panel';
import { MoreMenu } from './more-menu';

type HeaderToolbarProps = {
	onSave?: () => void;
	onCancel?: () => void;
};

export function HeaderToolbar( {
	onSave = () => {},
	onCancel = () => {},
}: HeaderToolbarProps ) {
	const { isInserterOpened, setIsInserterOpened } =
		useContext( EditorContext );
	const isLargeViewport = useViewportMatch( 'medium' );
	const inserterButton = useRef< HTMLButtonElement | null >( null );
	const { isInserterEnabled, isTextModeEnabled } = useSelect( ( select ) => {
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
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore These selectors are available in the block data store.
			__unstableGetEditorMode: getEditorMode,
		} = select( blockEditorStore );

		return {
			isTextModeEnabled: getEditorMode() === 'text',
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
				{ isLargeViewport && (
					<ToolbarItem
						as={ ToolSelector }
						disabled={ isTextModeEnabled }
					/>
				) }
				<ToolbarItem as={ EditorHistoryUndo } />
				<ToolbarItem as={ EditorHistoryRedo } />
				<ToolbarItem as={ DocumentOverview } />
			</div>
			<div className="woocommerce-iframe-editor__header-toolbar-right">
				<ToolbarItem
					as={ Button }
					variant="tertiary"
					className="woocommerce-modal-actions__cancel-button"
					onClick={ onCancel }
					text={ __( 'Cancel', 'woocommerce' ) }
				/>
				<ToolbarItem
					as={ Button }
					variant="primary"
					className="woocommerce-modal-actions__done-button"
					onClick={ onSave }
					text={ __( 'Done', 'woocommerce' ) }
				/>
				<ToolbarItem
					as={ ShowBlockInspectorPanel }
					className="woocommerce-show-block-inspector-panel"
				/>
				<ToolbarItem as={ MoreMenu } />
			</div>
		</NavigableToolbar>
	);
}
