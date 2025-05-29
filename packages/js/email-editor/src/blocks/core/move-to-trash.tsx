/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { unlock } from '../../private-apis';
import trashEmailPost from '../../components/header/trash-email-post';

function modifyMoveToTrashAction() {
	addAction(
		'core.registerPostTypeSchema',
		'woocommerce-email-editor/modify-move-to-trash-action',
		( postType ) => {
			// Remove the default move to trash action.
			unlock( dispatch( editorStore ) ).unregisterEntityAction(
				'postType',
				postType,
				'move-to-trash'
			);
			// Add the custom trash email post action.
			unlock( dispatch( editorStore ) ).registerEntityAction(
				'postType',
				postType,
				trashEmailPost
			);
		}
	);
}

export { modifyMoveToTrashAction };
