/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { recordEvent } from '@woocommerce/tracks';

export const SendMagicLinkStates = {
	INIT: 'initializing',
	FETCHING: 'fetching',
	SUCCESS: 'success',
	ERROR: 'error',
} as const;
export type SendMagicLinkStates =
	( typeof SendMagicLinkStates )[ keyof typeof SendMagicLinkStates ];

export type MagicLinkResponse = {
	data: unknown;
	code: string;
	message: string;
} & Response;

export const sendMagicLink = () => {
	return apiFetch< MagicLinkResponse >( {
		path: `${ WC_ADMIN_NAMESPACE }/mobile-app/send-magic-link`,
	} );
};

export const useSendMagicLink = () => {
	const [ requestState, setRequestState ] = useState< SendMagicLinkStates >(
		SendMagicLinkStates.INIT
	);

	const { createNotice } = useDispatch( 'core/notices' );

	const fetchMagicLinkApiCall = useCallback( () => {
		setRequestState( SendMagicLinkStates.FETCHING );

		sendMagicLink()
			.then( ( response ) => {
				if ( response.code === 'success' ) {
					setRequestState( SendMagicLinkStates.SUCCESS );
				} else {
					setRequestState( SendMagicLinkStates.ERROR );
					createNotice(
						'error',
						__( 'Sorry, an unknown error occurred.', 'woocommerce' )
					);
				}
			} )
			.catch( ( response ) => {
				setRequestState( SendMagicLinkStates.ERROR );
				recordEvent( 'magic_prompt_send_magic_link_error', {
					error: response.message,
					code: response.code,
				} );
				if ( response.code === 'error_sending_mobile_magic_link' ) {
					createNotice(
						'error',
						__(
							'We couldn’t send the link. Try again in a few seconds.',
							'woocommerce'
						)
					);
				} else if (
					response.code === 'invalid_user_permission_view_admin'
				) {
					createNotice(
						'error',
						__(
							'Sorry, your account doesn’t have sufficient permission.',
							'woocommerce'
						)
					);
				} else if ( response.code === 'jetpack_not_connected' ) {
					createNotice( 'error', response.message );
				} else {
					createNotice(
						'error',
						'We couldn’t send the link. Try again in a few seconds.'
					);
				}
			} );
	}, [ createNotice ] );

	return { requestState, fetchMagicLinkApiCall };
};
