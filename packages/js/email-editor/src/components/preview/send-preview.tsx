/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error Type for PluginPreviewMenuItem is missing in @types/wordpress__editor
	PluginPreviewMenuItem,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store/constants';
import { SendPreviewEmail } from './send-preview-email';
import { recordEvent } from '../../events';

export function SendPreview() {
	const { togglePreviewModal } = useDispatch( storeName );

	return (
		<>
			<PluginPreviewMenuItem
				icon={ external }
				onClick={ () => {
					recordEvent(
						'header_preview_dropdown_send_test_email_selected'
					);
					togglePreviewModal( true );
				} }
			>
				{ __( 'Send a test email', 'woocommerce' ) }
			</PluginPreviewMenuItem>
			<SendPreviewEmail />
		</>
	);
}
