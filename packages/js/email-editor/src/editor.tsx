/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { StrictMode, createRoot } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import '@wordpress/format-library'; // Enables text formatting capabilities

/**
 * Internal dependencies
 */
import { initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { InnerEditor } from './components/block-editor';
import { createStore, storeName, editorCurrentPostType } from './store';
import { initHooks } from './editor-hooks';
import { KeyboardShortcuts } from './components/keybord-shortcuts';
import { initEventCollector } from './events';
import './style.scss';

function Editor() {
	const { postId, settings } = useSelect(
		( select ) => ( {
			postId: select( storeName ).getEmailPostId(),
			settings: select( storeName ).getInitialEditorSettings(),
		} ),
		[]
	);

	return (
		<StrictMode>
			<KeyboardShortcuts />
			<InnerEditor
				initialEdits={ [] }
				postId={ postId }
				postType={ editorCurrentPostType }
				settings={ settings }
			/>
		</StrictMode>
	);
}

const WrappedEditor = applyFilters(
	'woocommerce_email_editor_wrap_editor_component',
	Editor
) as typeof Editor;

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	initEventCollector();
	createStore();
	initializeLayout();
	initBlocks();
	initHooks();
	const root = createRoot( container );
	root.render( <WrappedEditor /> );
}
