/**
 * External dependencies
 */
import classnames from 'classnames';
import { useRef, useState } from '@wordpress/element';
import { PinnedItems } from '@wordpress/interface';
import { Button, ToolbarItem as WpToolbarItem } from '@wordpress/components';
import {
	NavigableToolbar,
	BlockToolbar as WPBlockToolbar,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as preferencesStore } from '@wordpress/preferences';
import { __ } from '@wordpress/i18n';
import { plus, listView, undo, redo, next, previous } from '@wordpress/icons';
import {
	// @ts-expect-error DocumentBar types are not available
	DocumentBar,
	store as editorStore,
	// @ts-expect-error useEntitiesSavedStatesIsDirty types are not available
	useEntitiesSavedStatesIsDirty,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */

import { storeName, editorCurrentPostType } from '../../store';
import { MoreMenu } from './more-menu';
import { PreviewDropdown } from '../preview';
import { SaveEmailButton } from './save-email-button';
import { CampaignName } from './campaign-name';
import { SendButton } from './send-button';
import { SaveAllButton } from './save-all-button';
import { useContentValidation, useEditorMode } from '../../hooks';
import { recordEvent } from '../../events';

// Build type for ToolbarItem contains only "as" and "children" properties but it takes all props from
// component passed to "as" property (in this case Button). So as fix for TS errors we need to pass all props from Button to ToolbarItem.
// We should be able to remove this fix when ToolbarItem will be fixed in Gutenberg.
const ToolbarItem = WpToolbarItem as React.ForwardRefExoticComponent<
	React.ComponentProps< typeof WpToolbarItem > &
		React.ComponentProps< typeof Button >
>;

// Definition of BlockToolbar in currently installed Gutenberg packages (wp-6.4) is missing hideDragHandle prop
// After updating to newer version of Gutenberg we should be able to remove this fix
const BlockToolbar = WPBlockToolbar as React.FC<
	React.ComponentProps< typeof WPBlockToolbar > & {
		hideDragHandle?: boolean;
	}
>;

export function Header() {
	const inserterButton = useRef();
	const listviewButton = useRef();
	const undoButton = useRef();
	const redoButton = useRef();

	const [ isBlockToolsCollapsed, setIsBlockToolsCollapsed ] =
		useState( false );

	const { undo: undoAction, redo: redoAction } = useDispatch( coreDataStore );
	// @ts-expect-error missing types.
	const { setIsInserterOpened, setIsListViewOpened } =
		useDispatch( editorStore );
	const {
		isInserterSidebarOpened,
		isListviewSidebarOpened,
		isFixedToolbarActive,
		isBlockSelected,
		hasUndo,
		hasRedo,
	} = useSelect( ( select ) => {
		return {
			// @ts-expect-error missing types.
			isInserterSidebarOpened: select( editorStore ).isInserterOpened(),
			// @ts-expect-error missing types.
			isListviewSidebarOpened: select( editorStore ).isListViewOpened(),
			isFixedToolbarActive: select( preferencesStore ).get(
				'core',
				'fixedToolbar'
			),
			isBlockSelected:
				!! select( blockEditorStore ).getBlockSelectionStart(),
			hasUndo: select( coreDataStore ).hasUndo(),
			hasRedo: select( coreDataStore ).hasRedo(),
		};
	}, [] );

	const [ editorMode ] = useEditorMode();
	const { validateContent, isInvalid } = useContentValidation();

	const { dirtyEntityRecords } = useEntitiesSavedStatesIsDirty();
	const hasNonEmailEdits = dirtyEntityRecords.some(
		( entity ) => entity.name !== editorCurrentPostType
	);

	const preventDefault = ( event ) => {
		event.preventDefault();
	};

	const toggleTheInserterSidebar = () => {
		if ( isInserterSidebarOpened ) {
			recordEvent( 'header_inserter_sidebar_closed' );
			return setIsInserterOpened( false );
		}
		recordEvent( 'header_inserter_sidebar_opened' );
		return setIsInserterOpened( true );
	};

	const toggleTheListviewSidebar = () => {
		if ( isListviewSidebarOpened ) {
			recordEvent( 'header_listview_sidebar_closed' );
			return setIsListViewOpened( false );
		}
		recordEvent( 'header_listview_sidebar_opened' );
		return setIsListViewOpened( true );
	};

	const shortLabelInserter = ! isInserterSidebarOpened
		? __( 'Add', 'woocommerce' )
		: __( 'Close', 'woocommerce' );

	return (
		<div className="editor-header edit-post-header">
			<div className="editor-header__toolbar">
				<NavigableToolbar
					className="editor-document-tools edit-post-header-toolbar is-unstyled"
					aria-label={ __( 'Email document tools', 'woocommerce' ) }
				>
					<div className="editor-document-tools__left">
						<ToolbarItem
							ref={ inserterButton }
							as={ Button }
							className="editor-header-toolbar__inserter-toggle edit-post-header-toolbar__inserter-toggle"
							variant="primary"
							isPressed={ isInserterSidebarOpened }
							onMouseDown={ preventDefault }
							onClick={ toggleTheInserterSidebar }
							disabled={ false }
							icon={ plus }
							label={ shortLabelInserter }
							showTooltip
							aria-expanded={ isInserterSidebarOpened }
						/>
						<ToolbarItem
							ref={ undoButton }
							as={ Button }
							className="editor-history__undo"
							isPressed={ false }
							onMouseDown={ preventDefault }
							onClick={ () => {
								void undoAction();
								recordEvent( 'header_undo_icon_clicked' );
							} }
							disabled={ ! hasUndo }
							icon={ undo }
							label={ __( 'Undo', 'woocommerce' ) }
							showTooltip
						/>
						<ToolbarItem
							ref={ redoButton }
							as={ Button }
							className="editor-history__redo"
							isPressed={ false }
							onMouseDown={ preventDefault }
							onClick={ () => {
								void redoAction();
								recordEvent( 'header_redo_icon_clicked' );
							} }
							disabled={ ! hasRedo }
							icon={ redo }
							label={ __( 'Redo', 'woocommerce' ) }
							showTooltip
						/>
						<ToolbarItem
							ref={ listviewButton }
							as={ Button }
							className="editor-header-toolbar__document-overview-toggle edit-post-header-toolbar__document-overview-toggle"
							isPressed={ isListviewSidebarOpened }
							onMouseDown={ preventDefault }
							onClick={ toggleTheListviewSidebar }
							disabled={ false }
							icon={ listView }
							label={ __( 'List view', 'woocommerce' ) }
							showTooltip
							aria-expanded={ isInserterSidebarOpened }
						/>
					</div>
				</NavigableToolbar>
				{ isFixedToolbarActive && isBlockSelected && (
					<>
						<div
							className={ classnames(
								'editor-collapsible-block-toolbar',
								{
									'is-collapsed': isBlockToolsCollapsed,
								}
							) }
						>
							<BlockToolbar hideDragHandle />
						</div>
						<Button
							className="editor-header__block-tools-toggle edit-post-header__block-tools-toggle"
							icon={ isBlockToolsCollapsed ? next : previous }
							onClick={ () => {
								setIsBlockToolsCollapsed(
									( collapsed ) => ! collapsed
								);
								recordEvent(
									'header_blocks_tool_button_clicked',
									{ isBlockToolsCollapsed }
								);
							} }
							label={
								isBlockToolsCollapsed
									? __( 'Show block tools', 'woocommerce' )
									: __( 'Hide block tools', 'woocommerce' )
							}
						/>
					</>
				) }
			</div>
			{ ( ! isFixedToolbarActive ||
				! isBlockSelected ||
				isBlockToolsCollapsed ) && (
				<div className="editor-header__center edit-post-header__center">
					{ editorMode === 'template' ? (
						<DocumentBar />
					) : (
						<CampaignName />
					) }
				</div>
			) }
			<div className="editor-header__settings edit-post-header__settings">
				<SaveEmailButton />
				<PreviewDropdown />
				{ hasNonEmailEdits ? (
					<SaveAllButton />
				) : (
					<SendButton
						validateContent={ validateContent }
						isContentInvalid={ isInvalid }
					/>
				) }
				<PinnedItems.Slot scope={ storeName } />
				<MoreMenu />
			</div>
		</div>
	);
}
