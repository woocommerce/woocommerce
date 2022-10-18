/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Guide } from '@wordpress/components';
import { useSearchParams } from 'react-router-dom';
import { updateQueryString } from '@woocommerce/navigation';
import { registerPlugin } from '@wordpress/plugins';
import { addFilter, removeFilter } from '@wordpress/hooks';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ModalIllustrationLayout } from './layouts/ModalIllustrationLayout';
import {
	useJetpackPluginState,
	JetpackPluginStates,
	useSendMagicLink,
	SendMagicLinkStates,
} from './components';
import {
	EmailSentPage,
	JetpackInstallStepperPage,
	JetpackAlreadyInstalledPage,
} from './pages';
import './style.scss';
import { WrongUserConnectedPage } from './pages/WrongUserConnectedPage';
import { SETUP_TASK_HELP_ITEMS_FILTER } from '../../activity-panel/panels/help';

export const MobileAppModal = () => {
	const [ guideIsOpen, setGuideIsOpen ] = useState( false );
	const [ isReturningFromWordpressConnection, setIsReturning ] =
		useState( false );

	const { state, jetpackConnectionData } = useJetpackPluginState();
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const [ pageContent, setPageContent ] = useState< React.ReactNode >();
	const [ searchParams ] = useSearchParams();

	useEffect( () => {
		if ( searchParams.get( 'mobileAppModal' ) ) {
			setGuideIsOpen( true );
		} else {
			setGuideIsOpen( false );
		}

		if ( searchParams.get( 'jetpackState' ) === 'returning' ) {
			setIsReturning( true );
		}
	}, [ searchParams ] );

	const [ hasSentEmail, setHasSentEmail ] = useState( false );
	const [ isRetryingMagicLinkSend, setIsRetryingMagicLinkSend ] =
		useState( false );

	const { requestState: magicLinkRequestStatus, fetchMagicLinkApiCall } =
		useSendMagicLink();

	const sendMagicLink = useCallback( () => {
		fetchMagicLinkApiCall();
		recordEvent( 'magic_prompt_send_signin_link_click' );
	}, [ fetchMagicLinkApiCall ] );

	useEffect( () => {
		if ( magicLinkRequestStatus === SendMagicLinkStates.SUCCESS ) {
			setHasSentEmail( true );
		}
	}, [ magicLinkRequestStatus ] );

	useEffect( () => {
		if ( hasSentEmail ) {
			setPageContent(
				<EmailSentPage
					returnToSendLinkPage={ () => {
						setHasSentEmail( false );
						setIsRetryingMagicLinkSend( true );
						recordEvent( 'magic_prompt_retry_send_signin_link' );
					} }
				/>
			);
		} else if ( state === JetpackPluginStates.NOT_OWNER_OF_CONNECTION ) {
			setPageContent( <WrongUserConnectedPage /> );
		} else if (
			state === JetpackPluginStates.NOT_INSTALLED ||
			state === JetpackPluginStates.NOT_ACTIVATED ||
			state === JetpackPluginStates.USERLESS_CONNECTION ||
			( state === JetpackPluginStates.FULL_CONNECTION &&
				isReturningFromWordpressConnection )
		) {
			setPageContent(
				<JetpackInstallStepperPage
					isReturningFromWordpressConnection={
						isReturningFromWordpressConnection
					}
					isRetryingMagicLinkSend={ isRetryingMagicLinkSend }
					sendMagicLinkHandler={ sendMagicLink }
					sendMagicLinkStatus={ magicLinkRequestStatus }
				/>
			);
		} else if (
			state === JetpackPluginStates.FULL_CONNECTION &&
			jetpackConnectionData?.currentUser?.wpcomUser?.email &&
			! hasSentEmail
		) {
			const wordpressAccountEmailAddress =
				jetpackConnectionData?.currentUser.wpcomUser.email;
			setPageContent(
				<JetpackAlreadyInstalledPage
					wordpressAccountEmailAddress={
						wordpressAccountEmailAddress
					}
					isRetryingMagicLinkSend={ isRetryingMagicLinkSend }
					sendMagicLinkStatus={ magicLinkRequestStatus }
					sendMagicLinkHandler={ sendMagicLink }
				/>
			);
		}
	}, [
		sendMagicLink,
		hasSentEmail,
		isReturningFromWordpressConnection,
		jetpackConnectionData?.currentUser?.wpcomUser?.email,
		state,
		isRetryingMagicLinkSend,
		magicLinkRequestStatus,
	] );

	return (
		<>
			{ guideIsOpen && (
				<Guide
					onFinish={ () => {
						updateOptions( {
							woocommerce_admin_dismissed_mobile_app_modal: 'yes',
						} );
						// clear the search params that we use so that the URL is clean
						updateQueryString(
							{
								jetpackState: undefined,
								mobileAppModal: undefined,
							},
							undefined,
							Object.fromEntries( searchParams.entries() )
						);
					} }
					className={ 'woocommerce__mobile-app-welcome-modal' }
					pages={ [
						{
							content: (
								<ModalIllustrationLayout body={ pageContent } />
							),
						},
					] }
				/>
			) }
		</>
	);
};

export const MOBILE_APP_MODAL_HELP_ENTRY_FILTER_CALLBACK =
	'wc/admin/mobile-app-help-entry-callback';

/**
 * This component exists to add the mobile app entry to the help panel.
 * If the user has no pathway to achieve the required Jetpack connection,
 * then we don't want to show the help panel entry.
 */
export const MobileAppHelpMenuEntryLoader = () => {
	const { state } = useJetpackPluginState();

	const filterHelpMenuEntries = useCallback(
		( helpMenuEntries ) => {
			if (
				state === JetpackPluginStates.INITIALIZING ||
				state === JetpackPluginStates.USER_CANNOT_INSTALL ||
				state === JetpackPluginStates.NOT_OWNER_OF_CONNECTION
			) {
				return helpMenuEntries;
			}
			return [
				...helpMenuEntries,
				{
					title: __( 'Get the WooCommerce app', 'woocommerce' ),
					link: getAdminLink(
						'./admin.php?page=wc-admin&mobileAppModal=true'
					),
					linkType: 'wc-admin',
				},
			];
		},
		[ state ]
	);

	useEffect( () => {
		removeFilter(
			SETUP_TASK_HELP_ITEMS_FILTER,
			MOBILE_APP_MODAL_HELP_ENTRY_FILTER_CALLBACK
		);
		addFilter(
			SETUP_TASK_HELP_ITEMS_FILTER,
			MOBILE_APP_MODAL_HELP_ENTRY_FILTER_CALLBACK,
			filterHelpMenuEntries,
			10
		);
	}, [ filterHelpMenuEntries ] );

	return null;
};

registerPlugin( 'woocommerce-mobile-app-modal', {
	render: MobileAppHelpMenuEntryLoader,
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-admin',
} );
