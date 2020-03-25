/**
 * External dependencies
 */
import { select } from '@wordpress/data';

// Stand-in wcTracks.recordEvent in case tracks is not available (for any reason).
window.wcTracks = window.wcTracks || {};
window.wcTracks.recordEvent = window.wcTracks.recordEvent || function() {};

export const recordEditorEvent = function( event, props ) {
	// Force prefix - our editor events will be 'wcadmin_blocks_*'.
	const blocksPrefix = 'blocks_';

	const postData = select( 'core/editor' );

	// If tracks is not available (e.g. opt-out), recordEvent is a no-op.
	window.wcTracks.recordEvent( blocksPrefix + event, {
		// Automatically include post id and post type props.
		post_id: postData.getCurrentPostId(),
		post_type: postData.getCurrentPostType(),
		...props,
	} );
};
