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
import { getAllowedBlockNames, initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { InnerEditor } from './components/block-editor';
import { createStore, storeName } from './store';
import { initHooks } from './editor-hooks';
import { initTextHooks } from './text-hooks';
import {
	initEventCollector,
	initStoreTracking,
	initDomTracking,
} from './events';
import { initContentValidationMiddleware } from './middleware/content-validation';
import { useContentValidation, useRemoveSavingFailedNotices } from './hooks';

function Editor() {
	const { postId, postType, settings } = useSelect(
		( select ) => ( {
			postId: select( storeName ).getEmailPostId(),
			postType: select( storeName ).getEmailPostType(),
			settings: select( storeName ).getInitialEditorSettings(),
		} ),
		[]
	);
	useContentValidation();
	useRemoveSavingFailedNotices();

	// Set allowed blockTypes to the editor settings.
	settings.allowedBlockTypes = getAllowedBlockNames();

	return (
		<StrictMode>
			<InnerEditor
				postId={ postId }
				postType={ postType }
				settings={ settings }
			/>
		</StrictMode>
	);
}

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	const WrappedEditor = applyFilters(
		'woocommerce_email_editor_wrap_editor_component',
		Editor
	) as typeof Editor;
	initEventCollector();
	initStoreTracking();
	initDomTracking();
	createStore();
	initContentValidationMiddleware();
	initializeLayout();
	initBlocks();
	initHooks();
	initTextHooks();
	const root = createRoot( container );
	root.render( <WrappedEditor /> );
}
