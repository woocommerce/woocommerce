/**
 * External dependencies
 */
import { PluginPreviewMenuItem } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { storeName } from '../../store/constants';
import { SendPreviewEmail } from './send-preview-email';

export function SendPreview() {
	const { togglePreviewModal } = useDispatch( storeName );

	return (
		<>
			<PluginPreviewMenuItem
				icon={ external }
				onClick={ () => {
					togglePreviewModal( true );
				} }
			>
				{ __( 'Send preview', 'woocommerce' ) }
			</PluginPreviewMenuItem>
			<SendPreviewEmail />
		</>
	);
}
