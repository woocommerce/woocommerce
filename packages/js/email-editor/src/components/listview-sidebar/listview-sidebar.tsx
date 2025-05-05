/**
 * External dependencies
 */
// eslint-disable-next-line
import { __experimentalListView as ListView } from '@wordpress/block-editor';

export function ListviewSidebar() {
	return (
		<div className="editor-list-view-sidebar">
			<ListView />
		</div>
	);
}
