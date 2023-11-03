/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import { SendMagicLinkButton } from '../components';
import { SendMagicLinkStates } from '../components/useSendMagicLink';
import { MobileAppInstallationInfo } from '../components/MobileAppInstallationInfo';

interface JetpackAlreadyInstalledPageProps {
	wordpressAccountEmailAddress: string | undefined;
	isRetryingMagicLinkSend: boolean;
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
}

export const JetpackAlreadyInstalledPage: React.FC<
	JetpackAlreadyInstalledPageProps
> = ( {
	wordpressAccountEmailAddress,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
	isRetryingMagicLinkSend,
} ) => {
	const DISMISSED_MOBILE_APP_MODAL_OPTION =
		'woocommerce_admin_dismissed_mobile_app_modal';

	const { repeatUser, isLoading } = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		return {
			isLoading: ! hasFinishedResolution( 'getOption', [
				DISMISSED_MOBILE_APP_MODAL_OPTION,
			] ),
			repeatUser:
				getOption( DISMISSED_MOBILE_APP_MODAL_OPTION ) === 'yes',
		};
	} );

	useEffect( () => {
		if ( ! isLoading && ! isRetryingMagicLinkSend ) {
			recordEvent( 'magic_prompt_view', {
				// jetpack_state value is implied by the precondition of rendering this screen
				jetpack_state: 'full-connection',
				repeat_user: repeatUser,
			} );
		}
	}, [ repeatUser, isLoading, isRetryingMagicLinkSend ] );

	return (
		<ModalContentLayoutWithTitle>
			<>
				<MobileAppInstallationInfo />
				<div className="modal-subheader jetpack-already-installed">
					<h3>
						{ sprintf(
							/* translators: %s: user's WordPress.com account email address */
							__(
								'After the app is installed, click below to send a magic link to %s. Open it on your smartphone or tablet to sign into your store instantly.',
								'woocommerce'
							),
							wordpressAccountEmailAddress
						) }
					</h3>
				</div>
				<SendMagicLinkButton
					isFetching={
						sendMagicLinkStatus === SendMagicLinkStates.FETCHING
					}
					onClickHandler={ sendMagicLinkHandler }
				/>
			</>
		</ModalContentLayoutWithTitle>
	);
};
