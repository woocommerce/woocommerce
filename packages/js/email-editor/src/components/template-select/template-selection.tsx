/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { SelectTemplateModal } from './select-modal';

export function TemplateSelection() {
	const [ templateSelected, setTemplateSelected ] = useState( false );
	const { emailContentIsEmpty, postType } = useSelect(
		( select ) => ( {
			emailContentIsEmpty: select( storeName ).hasEmptyContent(),
			postType: select( storeName ).getEmailPostType(),
		} ),
		[]
	);
	// Show the template modal whenever content is empty, regardless of WP's dirty state.
	// WP 7.0 auto-inserts an empty paragraph block on fresh posts, causing hasEdits()
	// to return true before the user does anything.
	if ( ! emailContentIsEmpty || templateSelected ) {
		return null;
	}

	return (
		<SelectTemplateModal
			onSelectCallback={ () => setTemplateSelected( true ) }
			postType={ postType }
		/>
	);
}
