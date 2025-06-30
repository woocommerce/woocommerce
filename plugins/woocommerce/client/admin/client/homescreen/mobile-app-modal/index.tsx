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
import { optionsStore, onboardingStore } from '@woocommerce/data';
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
import { EmailSentPage, MobileAppLoginStepperPage } from './pages';
import './style.scss';
import { SETUP_TASK_HELP_ITEMS_FILTER } from '../../activity-panel/panels/help';

export const MobileAppModal = () => {
	const [ guideIsOpen, setGuideIsOpen ] = useState( false );
	const [ isReturningFromWordpressConnection, setIsReturning ] =
		useState( false );

	const { state, jetpackConnectionData } = useJetpackPluginState();
	const { updateOptions } = useDispatch( optionsStore );

	const [ pageContent, setPageContent ] = useState< React.ReactNode >();
	const [ searchParams ] = useSearchParams();

	const { invalidateResolutionForStoreSelector } =
		useDispatch( onboardingStore );

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

	const [ appInstalledClicked, setAppInstalledClicked ] = useState( false );
	const [ hasSentEmail, setHasSentEmail ] = useState( false );
	const [ isRetryingMagicLinkSend, setIsRetryingMagicLinkSend ] =
		useState( false );

	const { requestState: magicLinkRequestStatus, fetchMagicLinkApiCall } =
		useSendMagicLink();

	const completeAppInstallationStep = useCallback( () => {
		setAppInstalledClicked( true );
		recordEvent( 'onboarding_app_install_click' );
	}, [] );

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
		} else {
			const isJetpackPluginInstalled =
				( state === JetpackPluginStates.FULL_CONNECTION &&
					jetpackConnectionData?.currentUser?.wpcomUser?.email !==
						undefined ) ??
				false;
			const wordpressAccountEmailAddress =
				jetpackConnectionData?.currentUser?.wpcomUser?.email;
			setPageContent(
				<MobileAppLoginStepperPage
					appInstalledClicked={ appInstalledClicked }
					isJetpackPluginInstalled={ isJetpackPluginInstalled }
					wordpressAccountEmailAddress={
						wordpressAccountEmailAddress
					}
					completeInstallationHandler={ completeAppInstallationStep }
					sendMagicLinkHandler={ sendMagicLink }
					sendMagicLinkStatus={ magicLinkRequestStatus }
				/>
			);
		}
	}, [
		appInstalledClicked,
		sendMagicLink,
		hasSentEmail,
		isReturningFromWordpressConnection,
		jetpackConnectionData?.currentUser?.wpcomUser?.email,
		state,
		isRetryingMagicLinkSend,
		magicLinkRequestStatus,
		completeAppInstallationStep,
	] );

	const clearQueryString = useCallback( () => {
		// clear the search params that we use so that the URL is clean
		updateQueryString(
			{
				jetpackState: undefined,
				mobileAppModal: undefined,
			},
			undefined,
			Object.fromEntries( searchParams.entries() )
		);
	}, [ searchParams ] );

	const onFinish = () => {
		updateOptions( {
			woocommerce_admin_dismissed_mobile_app_modal: 'yes',
		} ).then( () =>
			invalidateResolutionForStoreSelector( 'getTaskLists' )
		);

		clearQueryString();
		setGuideIsOpen( false );
	};

	return (
		<>
			{ guideIsOpen && (
				<Guide
					onFinish={ onFinish }
					contentLabel=""
					className={ 'woocommerce__mobile-app-welcome-modal' }
					pages={ [
						{
							content: (
								<ModalIllustrationLayout
									body={ pageContent }
									onDismiss={ onFinish }
								/>
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
 */
export const MobileAppHelpMenuEntryLoader = () => {
	const addMobileAppHelpEntry = useCallback(
		(
			helpMenuEntries: Array< {
				title: string;
				link: string;
				linkType?: string;
			} >
		) => {
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
		[]
	);

	useEffect( () => {
		removeFilter(
			SETUP_TASK_HELP_ITEMS_FILTER,
			MOBILE_APP_MODAL_HELP_ENTRY_FILTER_CALLBACK
		);
		addFilter(
			SETUP_TASK_HELP_ITEMS_FILTER,
			MOBILE_APP_MODAL_HELP_ENTRY_FILTER_CALLBACK,
			addMobileAppHelpEntry,
			10
		);
	}, [ addMobileAppHelpEntry ] );

	return null;
};

registerPlugin( 'woocommerce-mobile-app-modal', {
	render: MobileAppHelpMenuEntryLoader,
	scope: 'woocommerce-admin',
} );
