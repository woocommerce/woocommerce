/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	registerEntityAction,
	unregisterEntityAction,
} from '../../private-apis';
import getTrashEmailPostAction from '../../components/header/trash-email-post';

const removeDefaultMoveToTrashActionAddCustom = ( postType: string ) => {
	// Remove the default move to trash action.
	unregisterEntityAction( 'postType', postType, 'move-to-trash' );

	// Add the custom trash email post action.
	registerEntityAction( 'postType', postType, getTrashEmailPostAction() );
};

function modifyMoveToTrashAction() {
	// Available in WordPress 6.8+
	addAction(
		'core.registerPostTypeSchema',
		'woocommerce-email-editor/modify-move-to-trash-action',
		( postType ) => {
			removeDefaultMoveToTrashActionAddCustom( postType );
		}
	);

	// Support for WordPress 6.7+
	addAction(
		'core.registerPostTypeActions',
		'woocommerce-email-editor/modify-move-to-trash-action',
		( postType ) => {
			removeDefaultMoveToTrashActionAddCustom( postType );
		}
	);
}

export { modifyMoveToTrashAction };
