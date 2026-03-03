/**
 * External dependencies
 */
import { SnackbarList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

// Based on https://github.com/WordPress/gutenberg/blob/2788a9cf8b8149be3ee52dd15ce91fa55815f36a/packages/editor/src/components/editor-snackbars/index.js
// Uses both class names to support pre-7.0 and 7.0+ Gutenberg layouts.

const MAX_VISIBLE_NOTICES = -3;

export function EditorSnackbars() {
	const notices = useSelect(
		( select ) => select( noticesStore ).getNotices(),
		[]
	);

	const { removeNotice } = useDispatch( noticesStore );

	const snackbarNotices = notices
		.filter( ( { type } ) => type === 'snackbar' )
		.slice( MAX_VISIBLE_NOTICES );

	return (
		<SnackbarList
			notices={ snackbarNotices }
			className="components-editor-notices__snackbar edit-post-layout__snackbar"
			onRemove={ removeNotice }
		/>
	);
}
