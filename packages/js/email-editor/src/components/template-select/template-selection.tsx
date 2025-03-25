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
	const { emailContentIsEmpty, emailHasEdits } = useSelect(
		( select ) => ( {
			emailContentIsEmpty: select( storeName ).hasEmptyContent(),
			emailHasEdits: select( storeName ).hasEdits(),
		} ),
		[]
	);
	if ( ! emailContentIsEmpty || emailHasEdits || templateSelected ) {
		return null;
	}

	return (
		<SelectTemplateModal
			onSelectCallback={ () => setTemplateSelected( true ) }
		/>
	);
}
