/**
 * External dependencies
 */
import { dispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

export function SentEmailNotice() {
	const { isEmailSent } = useSelect(
		( select ) => ( {
			isEmailSent: select( storeName ).isEmailSent(),
		} ),
		[]
	);

	useEffect( () => {
		if ( isEmailSent ) {
			void dispatch( noticesStore ).createNotice(
				'warning',
				__(
					'This email has already been sent. It can be edited, but not sent again. Duplicate this email if you want to send it again.',
					'woocommerce'
				),
				{
					id: 'email-sent',
					isDismissible: false,
					context: 'email-editor',
				}
			);
			recordEvent( 'editor_showed_email_sent_notice' );
		}
	}, [ isEmailSent ] );

	return null;
}
