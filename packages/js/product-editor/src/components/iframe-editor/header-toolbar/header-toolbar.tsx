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
import { MoreMenu } from './more-menu';
import { getGutenbergVersion } from '../../../utils/get-gutenberg-version';
import { SIDEBAR_COMPLEMENTARY_AREA_SCOPE } from '../constants';

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

	const toggleInserter = useCallback(
		() => setIsInserterOpened( ! isInserterOpened ),
		[ isInserterOpened, setIsInserterOpened ]
	);

	useEffect( () => {
		// If we have a new block selection, show the block tools
		if ( hasBlockSelection ) {
			setIsBlockToolsCollapsed( false );
		}
	}, [ hasBlockSelection ] );

	const renderBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;

	return (
		<div className="woocommerce-iframe-editor__header">
			<div className="woocommerce-iframe-editor__header-left">
				<NavigableToolbar
					className="woocommerce-iframe-editor-document-tools"
					aria-label={ __( 'Document tools', 'woocommerce' ) }
					// @ts-expect-error variant prop exists
					variant="unstyled"
				>
					<div className="woocommerce-iframe-editor-document-tools__left">
						<ToolbarItem
							ref={ inserterButton }
							as={ Button }
							className="woocommerce-iframe-editor__header-inserter-toggle"
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
							label={ __(
								'Toggle block inserter',
								'woocommerce'
							) }
							aria-expanded={ isInserterOpened }
							showTooltip
						/>
						{ isLargeViewport && (
							<ToolbarItem
								as={ ToolSelector }
								disabled={ isTextModeEnabled }
								size="compact"
							/>
						) }
						<ToolbarItem as={ EditorHistoryUndo } size="compact" />
						<ToolbarItem as={ EditorHistoryRedo } size="compact" />
						<ToolbarItem as={ DocumentOverview } size="compact" />
					</div>
				</NavigableToolbar>
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
			<div className="woocommerce-iframe-editor__header-right">
				<Button
					variant="tertiary"
					className="woocommerce-modal-actions__cancel-button"
					onClick={ onCancel }
					text={ __( 'Cancel', 'woocommerce' ) }
				/>
				<Button
					variant="primary"
					className="woocommerce-modal-actions__done-button"
					onClick={ onSave }
					text={ __( 'Done', 'woocommerce' ) }
				/>
				<PinnedItems.Slot scope={ SIDEBAR_COMPLEMENTARY_AREA_SCOPE } />
				<MoreMenu />
			</div>
		</div>
	);
}
