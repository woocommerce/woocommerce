/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { check, cloud, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

export function SaveEmailButton() {
	const { saveEditedEmail } = useDispatch( storeName );

	const { hasEdits, isEmpty, isSaving } = useSelect(
		( select ) => ( {
			hasEdits: select( storeName ).hasEdits(),
			isEmpty: select( storeName ).isEmpty(),
			isSaving: select( storeName ).isSaving(),
		} ),
		[]
	);

	const isSaved = ! isEmpty && ! isSaving && ! hasEdits;
	const isDisabled = ! hasEdits && ( isEmpty || isSaving || isSaved );

	let label = __( 'Save Draft', 'woocommerce' );
	if ( isSaved ) {
		label = __( 'Saved', 'woocommerce' );
	} else if ( isSaving ) {
		label = __( 'Saving', 'woocommerce' );
	}

	return (
		<Button
			variant="tertiary"
			disabled={ isDisabled }
			onClick={ () => {
				void saveEditedEmail();
				recordEvent( 'header_save_email_button_clicked', {
					label,
					isSaving,
					isSaved,
				} );
			} }
		>
			{ isSaving && <Icon icon={ cloud } /> }
			{ isSaved && <Icon icon={ check } /> }
			{ label }
		</Button>
	);
}
