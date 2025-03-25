/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

export function useEditorMode() {
	const { isEditingTemplate } = useSelect(
		( select ) => ( {
			isEditingTemplate:
				select( editorStore ).getCurrentPostType() === 'wp_template',
		} ),
		[]
	);
	return [ isEditingTemplate ? 'template' : 'email' ];
}
