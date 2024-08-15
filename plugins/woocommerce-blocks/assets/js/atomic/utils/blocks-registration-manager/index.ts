/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import { BlockRegistrationManager } from './blocks-registration-manager';
import { EditorViewChangeDetector } from './editor-view-change-detector';

domReady( () => {
	const editorViewChangeDetector = new EditorViewChangeDetector();
	const blockRegistrationManager = new BlockRegistrationManager();
	editorViewChangeDetector.add( blockRegistrationManager );
} );
