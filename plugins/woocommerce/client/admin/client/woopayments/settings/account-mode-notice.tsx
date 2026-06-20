/**
 * External dependencies
 */
import { Button, ExternalLink, Modal, Notice } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getWooPaymentsAccountSettings } from './api';

type AccountModeNoticeState = {
	kind: 'test' | 'sandbox';
	setupUrl?: string;
};

const LEARN_MORE_URL =
	'https://woocommerce.com/document/woopayments/startup-guide/#sign-up-process';
const RESET_ACCOUNT_URL =
	'https://woocommerce.com/document/woopayments/startup-guide/#resetting';
const WORDPRESS_ENVIRONMENT_URL =
	'https://make.wordpress.org/core/2020/08/27/wordpress-environment-types/';
const TEST_ACCOUNT_DEV_URL =
	'https://woocommerce.com/document/woopayments/testing-and-troubleshooting/test-accounts/#developer-notes';
const SETUP_LIVE_FROM = 'WCPAY_SETTINGS';
const SETUP_LIVE_SOURCE = 'wcadmin-settings-page';

const getSetupLiveUrl = ( setupUrl?: string ) =>
	setupUrl
		? addQueryArgs( setupUrl, {
				source: SETUP_LIVE_SOURCE,
				from: 'wcpay-setup-live-payments',
		  } )
		: undefined;

const SetupLivePaymentsModal = ( {
	onClose,
	setupUrl,
}: {
	onClose: () => void;
	setupUrl?: string;
} ) => {
	const [ isSubmitted, setSubmitted ] = useState( false );
	const setupLiveUrl = getSetupLiveUrl( setupUrl );
	const handleSetup = (
		event: React.MouseEvent< HTMLAnchorElement | HTMLButtonElement >
	) => {
		if ( ! setupLiveUrl || isSubmitted ) {
			event.preventDefault();
			return;
		}

		setSubmitted( true );
		recordEvent( 'wcpay_onboarding_flow_setup_live_payments', {
			from: SETUP_LIVE_FROM,
			source: SETUP_LIVE_SOURCE,
		} );
		window.location.href = setupLiveUrl;
	};
	const handleClose = () => {
		setSubmitted( false );
		recordEvent( 'wcpay_setup_live_payments_modal_exit', {
			from: SETUP_LIVE_FROM,
			source: SETUP_LIVE_SOURCE,
		} );
		onClose();
	};

	return (
		<Modal
			title={ __( 'Activate payments on your store', 'woocommerce' ) }
			className="woopayments-settings-setup-live-modal"
			onRequestClose={ handleClose }
		>
			<div className="woopayments-settings-setup-live-modal__content">
				<p>
					{ __(
						"Before continuing, please make sure that you're aware of the following:",
						'woocommerce'
					) }
				</p>
				<ul>
					<li>
						{ __(
							'Your test account will be deactivated, but your transactions can be found in your order history.',
							'woocommerce'
						) }
					</li>
					<li>
						{ __(
							'To use WooPayments, you will need to verify your business details.',
							'woocommerce'
						) }
					</li>
					<li>
						{ __(
							'In order to receive payouts, you will need to provide your bank details.',
							'woocommerce'
						) }
					</li>
				</ul>
			</div>
			{ setupLiveUrl && (
				<div className="woopayments-settings-setup-live-modal__footer">
					<Button
						variant="primary"
						href={ setupLiveUrl }
						isBusy={ isSubmitted }
						aria-disabled={ isSubmitted }
						onClick={ handleSetup }
					>
						{ __( 'Activate payments', 'woocommerce' ) }
					</Button>
				</div>
			) }
		</Modal>
	);
};

export const AccountModeNotice = ( {
	isDevModeEnabled,
}: {
	isDevModeEnabled: boolean;
} ) => {
	const [ noticeState, setNoticeState ] =
		useState< AccountModeNoticeState | null >( null );
	const [ isModalVisible, setModalVisible ] = useState( false );

	useEffect( () => {
		let isMounted = true;

		getWooPaymentsAccountSettings()
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				const account = response.account;

				if ( ! account?.connected || account.live ) {
					return;
				}

				if ( account.test_drive ) {
					setNoticeState( {
						kind: 'test',
						setupUrl: response.urls.setup,
					} );
					return;
				}

				if ( account.sandbox ) {
					setNoticeState( {
						kind: 'sandbox',
						setupUrl: response.urls.setup,
					} );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setNoticeState( null );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	useEffect( () => {
		const handleActivatePayments = () => {
			if (
				noticeState?.kind === 'test' &&
				! isDevModeEnabled &&
				noticeState.setupUrl
			) {
				recordEvent( 'wcpay_settings_setup_live_payments_click', {
					source: SETUP_LIVE_SOURCE,
				} );
				setModalVisible( true );
			}
		};

		document.addEventListener(
			'wcpay:activate_payments',
			handleActivatePayments
		);

		return () => {
			document.removeEventListener(
				'wcpay:activate_payments',
				handleActivatePayments
			);
		};
	}, [ isDevModeEnabled, noticeState ] );

	if ( ! noticeState ) {
		return null;
	}

	const isTestAccount = noticeState.kind === 'test';
	const noticeHeading = isTestAccount
		? __( 'You are using a test account.', 'woocommerce' )
		: __( 'You are using a sandbox test account.', 'woocommerce' );

	const renderNoticeCopy = () => {
		if ( isDevModeEnabled ) {
			return (
				<>
					{ __(
						'⚠️ Development mode is enabled for the store! There can be no live onboarding process while using development, testing, or staging WordPress environments!',
						'woocommerce'
					) }{ ' ' }
					<br />
					{ __(
						'To begin accepting real payments, please go to the live store or change your',
						'woocommerce'
					) }{ ' ' }
					<ExternalLink href={ WORDPRESS_ENVIRONMENT_URL }>
						{ __( 'WordPress environment', 'woocommerce' ) }
					</ExternalLink>{ ' ' }
					{ __( 'to a production one.', 'woocommerce' ) }{ ' ' }
					<ExternalLink href={ TEST_ACCOUNT_DEV_URL }>
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</>
			);
		}

		if ( isTestAccount ) {
			return (
				<>
					<span>
						{ __(
							'Provide additional details about your business so you can begin accepting real payments.',
							'woocommerce'
						) }
					</span>{ ' ' }
					<ExternalLink href={ LEARN_MORE_URL }>
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</>
			);
		}

		return (
			<>
				{ __(
					'To begin accepting real payments you will need to first',
					'woocommerce'
				) }{ ' ' }
				<ExternalLink href={ RESET_ACCOUNT_URL }>
					{ __( 'reset your account', 'woocommerce' ) }
				</ExternalLink>{ ' ' }
				{ __(
					'and, then, provide additional details about your business.',
					'woocommerce'
				) }{ ' ' }
				<ExternalLink href={ LEARN_MORE_URL }>
					{ __( 'Learn more', 'woocommerce' ) }
				</ExternalLink>
			</>
		);
	};

	return (
		<>
			<Notice
				className="woopayments-settings-account-mode-notice"
				status="warning"
				isDismissible={ false }
			>
				<p>
					<strong>{ noticeHeading }</strong> { renderNoticeCopy() }
				</p>
				{ isTestAccount && ! isDevModeEnabled && (
					<Button
						variant="secondary"
						onClick={ () => {
							recordEvent(
								'wcpay_setup_live_payments_modal_open',
								{
									from: SETUP_LIVE_FROM,
									source: SETUP_LIVE_SOURCE,
								}
							);
							document.dispatchEvent(
								new CustomEvent( 'wcpay:activate_payments' )
							);
						} }
					>
						{ __( 'Activate payments', 'woocommerce' ) }
					</Button>
				) }
			</Notice>
			{ isModalVisible && (
				<SetupLivePaymentsModal
					onClose={ () => setModalVisible( false ) }
					setupUrl={ noticeState.setupUrl }
				/>
			) }
		</>
	);
};
