/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useViewportMatch } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { plus, next, previous } from '@wordpress/icons';
import {
	createElement,
	useRef,
	useCallback,
	useContext,
	useState,
	Fragment,
	useEffect,
} from '@wordpress/element';
import { isWpVersion } from '@woocommerce/settings';
import classnames from 'classnames';
import { MouseEvent } from 'react';
import {
	Button,
	Popover,
	/* @ts-expect-error missing types. */
	ToolbarItem,
} from '@wordpress/components';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	store as preferencesStore,
	/* @ts-expect-error missing types. */
} from '@wordpress/preferences';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	NavigableToolbar,
	store as blockEditorStore,
	// @ts-expect-error ToolSelector exists in WordPress components.
	ToolSelector,
	BlockToolbar,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';
import EditorHistoryRedo from './editor-history-redo';
import EditorHistoryUndo from './editor-history-undo';
import { DocumentOverview } from './document-overview';
import { ShowBlockInspectorPanel } from './show-block-inspector-panel';
import { MoreMenu } from './more-menu';
import { getGutenbergVersion } from '../../../utils/get-gutenberg-version';

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
	const [ isBlockToolsCollapsed, setIsBlockToolsCollapsed ] =
		useState( true );
	const isLargeViewport = useViewportMatch( 'medium' );
	const inserterButton = useRef< HTMLButtonElement | null >( null );
	const {
		isInserterEnabled,
		isTextModeEnabled,
		hasBlockSelection,
		hasFixedToolbar,
	} = useSelect( ( select ) => {
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
			// @ts-expect-error These selectors are available in the block data store.
			getBlockSelectionStart,
		} = select( blockEditorStore );
		// @ts-expect-error These selectors are available in the block data store.
		const { get: getPreference } = select( preferencesStore );

		return {
			isTextModeEnabled: getEditorMode() === 'text',
			isInserterEnabled: hasInserterItems(
				getBlockRootClientId( getBlockSelectionEnd() ?? '' ) ??
					undefined
			),
			hasBlockSelection: !! getBlockSelectionStart(),
			hasFixedToolbar: getPreference( 'core', 'fixedToolbar' ),
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

	useEffect( () => {
		// If we have a new block selection, show the block tools
		if ( hasBlockSelection ) {
			setIsBlockToolsCollapsed( false );
		}
	}, [ hasBlockSelection ] );

	const renderBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;

	return (
		<NavigableToolbar
			className="woocommerce-iframe-editor__header-toolbar"
			aria-label={ toolbarAriaLabel }
		>
			<div className="woocommerce-iframe-editor__header-toolbar-left">
				<div className="woocommerce-iframe-editor-document-tools">
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
				{ hasFixedToolbar && isLargeViewport && renderBlockToolbar && (
					<>
						<div
							className={ classnames(
								'selected-block-tools-wrapper',
								{
									'is-collapsed': isBlockToolsCollapsed,
								}
							) }
						>
							{ /* @ts-expect-error missing type */ }
							<BlockToolbar hideDragHandle />
						</div>
						{ /* @ts-expect-error missing type */ }
						<Popover.Slot name="block-toolbar" />
						{ hasBlockSelection && (
							<Button
								className="edit-post-header__block-tools-toggle"
								icon={ isBlockToolsCollapsed ? next : previous }
								onClick={ () => {
									setIsBlockToolsCollapsed(
										( collapsed ) => ! collapsed
									);
								} }
								label={
									isBlockToolsCollapsed
										? __(
												'Show block tools',
												'woocommerce'
										  )
										: __(
												'Hide block tools',
												'woocommerce'
										  )
								}
							/>
						) }
					</>
				) }
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
				<PinnedItems.Slot scope="woocommerce-product-editor-iframe-editor" />
				<ToolbarItem
					as={ ShowBlockInspectorPanel }
					className="woocommerce-show-block-inspector-panel"
				/>
				<ToolbarItem as={ MoreMenu } />
			</div>
		</NavigableToolbar>
	);
}
