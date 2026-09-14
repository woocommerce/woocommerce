/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { SelectTemplateModal } from './select-modal';

export function TemplateSelection() {
	const { emailContentIsEmpty, templateSelected, postType } = useSelect(
		( select ) => ( {
			emailContentIsEmpty: select( storeName ).hasEmptyContent(),
			templateSelected: select( storeName ).isTemplateSelected(),
			postType: select( storeName ).getEmailPostType(),
		} ),
		[]
	);
	const { setTemplateSelected } = useDispatch( storeName );

	// Show the template modal whenever content is empty, regardless of WP's dirty state.
	// WP 7.0 auto-inserts an empty paragraph block on fresh posts, causing hasEdits()
	// to return true before the user does anything.
	if ( ! emailContentIsEmpty || templateSelected ) {
		return null;
	}

	return (
		<SelectTemplateModal
			onSelectCallback={ () => void setTemplateSelected() }
			postType={ postType }
		/>
	);
}
