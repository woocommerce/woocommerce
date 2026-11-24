/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
import { PluginPreviewMenuItem } from '@wordpress/editor';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { storeName } from '../../store/constants';
import { SendPreviewEmail } from './send-preview-email';
import { recordEvent } from '../../events';

export function SendPreview() {
	const { togglePreviewModal } = useDispatch( storeName );
	const postType = useSelect(
		( select ) => select( storeName ).getEmailPostType(),
		[]
	);

	if (
		! applyFilters(
			'woocommerce_email_editor_send_preview_enabled',
			true,
			postType
		)
	) {
		return null;
	}

	return (
		<>
			<PluginPreviewMenuItem
				icon={ external }
				onClick={ () => {
					recordEvent(
						'header_preview_dropdown_send_test_email_selected'
					);
					void togglePreviewModal( true );
				} }
			>
				{ __( 'Send a test email', __i18n_text_domain__ ) }
			</PluginPreviewMenuItem>
			<SendPreviewEmail />
		</>
	);
}
