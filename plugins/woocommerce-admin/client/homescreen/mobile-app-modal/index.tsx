/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Guide } from '@wordpress/components';
import { useSearchParams } from 'react-router-dom';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ModalIllustrationLayout } from './layouts/ModalIllustrationLayout';
import {
	useJetpackPluginState,
	JetpackPluginStates,
	useSendMagicLink,
} from './components';
import {
	EmailSentPage,
	JetpackInstallStepperPage,
	JetpackAlreadyInstalledPage,
} from './pages';
import './style.scss';

export const MobileAppModal = () => {
	const [ guideIsOpen, setGuideIsOpen ] = useState( true );

	const { state, jetpackConnectionData } = useJetpackPluginState();

	const [ pageContent, setPageContent ] = useState< React.ReactNode >();
	const [ searchParams ] = useSearchParams();

	useEffect( () => {
		if ( searchParams.get( 'mobileAppModal' ) ) {
			setGuideIsOpen( true );
		} else {
			setGuideIsOpen( false );
		}
	}, [ searchParams ] );

	const isReturningFromWordpressConnection =
		searchParams.get( 'jetpackState' ) === 'returning';

	const [ hasSentEmail, setHasSentEmail ] = useState( false );

	const { fetchMagicLinkApiCall } = useSendMagicLink();

	const sendMagicLink = useCallback( () => {
		fetchMagicLinkApiCall();
		setHasSentEmail( true );
	}, [ fetchMagicLinkApiCall ] );

	useEffect( () => {
		if ( hasSentEmail ) {
			setPageContent(
				<EmailSentPage
					hasSentEmailHandler={ () => setHasSentEmail( false ) }
				/>
			);
		} else if (
			state === JetpackPluginStates.NOT_INSTALLED ||
			state === JetpackPluginStates.NOT_ACTIVATED ||
			state === JetpackPluginStates.USERLESS_CONNECTION ||
			isReturningFromWordpressConnection
		) {
			setPageContent(
				<JetpackInstallStepperPage
					isReturningFromWordpressConnection={
						isReturningFromWordpressConnection
					}
					sendMagicLinkHandler={ sendMagicLink }
				/>
			);
		} else if (
			state === JetpackPluginStates.FULL_CONNECTION &&
			! hasSentEmail
		) {
			const wordpressAccountEmailAddress =
				jetpackConnectionData?.currentUser.wpcomUser.email;
			setPageContent(
				<JetpackAlreadyInstalledPage
					wordpressAccountEmailAddress={
						wordpressAccountEmailAddress
					}
					sendMagicLinkHandler={ sendMagicLink }
				/>
			);
		}
	}, [
		sendMagicLink,
		hasSentEmail,
		isReturningFromWordpressConnection,
		jetpackConnectionData?.currentUser.wpcomUser.email,
		state,
	] );

	return (
		<>
			{ guideIsOpen && (
				<Guide
					onFinish={ () => {
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
