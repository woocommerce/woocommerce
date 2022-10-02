/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	Notice,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME, PluginsStoreActions } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';
import {
	Visa,
	MasterCard,
	Maestro,
	Amex,
	ApplePay,
	CB,
	DinersClub,
	Discover,
	JCB,
	UnionPay,
} from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import strings from './strings';
import Banner from './banner';
import ApmList, { Apm } from './apms';
import './style.scss';
import ExitSurveyModal from './exit-survey-modal';
import { getAdminSetting } from '~/utils/admin-settings';
import FrequentlyAskedQuestionsSimple from './faq-simple';

declare global {
	interface Window {
		wcCalypsoBridge: unknown;
		location: Location;
		wcSettings: {
			admin: {
				wcpay_welcome_page_connect_nonce: string;
			};
		};
	}
}

interface activatePromoResponse {
	success: boolean;
}

const PROMO_NAME = 'wcpay-promo-2022-us-incentive-20-off';

const LearnMore = () => {
	const handleClick = () => {
		recordEvent( 'wcpay_welcome_learn_more', {} );
	};
	return (
		<a
			onClick={ handleClick }
			href="https://woocommerce.com/payments/"
			target="_blank"
			rel="noreferrer"
		>
			{ strings.learnMore }
		</a>
	);
};

export const PaymentMethods = () => (
	<div className="wcpay-connect-account-page-payment-methods">
		<Visa />
		<MasterCard />
		<Maestro />
		<Amex />
		<DinersClub />
		<CB />
		<Discover />
		<UnionPay />
		<JCB />
		<ApplePay />
	</div>
);

const TermsOfService = () => (
	<p className="wcpay-connect-account-page-terms-of-service">
		{ strings.terms }
	</p>
);

const ConnectPageError = ( { errorMessage }: { errorMessage: string } ) => {
	if ( ! errorMessage ) {
		return null;
	}
	return (
		<Notice
			className="wcpay-connect-error-notice"
			status="error"
			isDismissible={ false }
		>
			{ errorMessage }
		</Notice>
	);
};

const ConnectPageOnboarding = ( {
	isJetpackConnected,
	installAndActivatePlugins,
	setErrorMessage,
	connectUrl,
	enabledApms,
}: {
	isJetpackConnected?: boolean;
	installAndActivatePlugins: PluginsStoreActions[ 'installAndActivatePlugins' ];
	// eslint-disable-next-line @typescript-eslint/ban-types
	setErrorMessage: Function;
	connectUrl: string;
	enabledApms: Set< Apm >;
} ) => {
	const [ isSubmitted, setSubmitted ] = useState( false );
	const [ isNoThanksClicked, setNoThanksClicked ] = useState( false );

	const [ isExitSurveyModalOpen, setExitSurveyModalOpen ] = useState( false );

	const renderErrorMessage = ( message: string ) => {
		setErrorMessage( message );
		setSubmitted( false );
	};

	const activatePromo = async () => {
		const activatePromoRequest: activatePromoResponse = await apiFetch( {
			path: `/wc-analytics/admin/notes/experimental-activate-promo/${ PROMO_NAME }`,
			method: 'POST',
		} );
		if ( activatePromoRequest?.success ) {
			window.location.href = connectUrl;
		}
	};

	const handleSetup = async () => {
		setSubmitted( true );
		recordEvent( 'wcpay_connect_account_clicked', {
			// eslint-disable-next-line camelcase
			wpcom_connection: isJetpackConnected ? 'Yes' : 'No',
		} );

		const pluginsToInstall = [ ...enabledApms ].map(
			( apm ) => apm.extension
		);

		try {
			const installAndActivateResponse = await installAndActivatePlugins(
				[ 'woocommerce-payments' ].concat( pluginsToInstall )
			);
			if ( installAndActivateResponse?.success ) {
				recordEvent( 'wcpay_extension_installed', {
					extensions: [ ...enabledApms ]
						.map( ( apm ) => apm.id )
						.join( ', ' ),
				} );
				await activatePromo();
			} else {
				throw new Error( installAndActivateResponse.message );
			}
		} catch ( e ) {
			if ( e instanceof Error ) {
				renderErrorMessage( e.message );
			} else {
				throw new Error( `Unexpected error occurred. ${ e }` );
			}
		}
	};

	const handleNoThanks = () => {
		setNoThanksClicked( true );
		setExitSurveyModalOpen( true );
	};

	return (
		<Card className="connect-account__card">
			<CardHeader>
				<div>
					<h1 className="banner-heading-copy">
						{ strings.bannerHeading }
					</h1>
					<TermsOfService />
				</div>
				<div className="connect-account__action">
					<Button
						isSecondary
						isBusy={ isNoThanksClicked && isExitSurveyModalOpen }
						disabled={ isNoThanksClicked && isExitSurveyModalOpen }
						onClick={ handleNoThanks }
						className="btn-nothanks"
					>
						{ strings.nothanks }
					</Button>
					<Button
						isPrimary
						isBusy={ isSubmitted }
						disabled={ isSubmitted }
						onClick={ handleSetup }
						className="btn-install"
					>
						{ strings.button }
					</Button>
					{ isExitSurveyModalOpen && (
						<ExitSurveyModal
							setExitSurveyModalOpen={ setExitSurveyModalOpen }
						/>
					) }
				</div>
			</CardHeader>
			<CardBody>
				<div className="content">
					<p className="onboarding-description">
						{ strings.onboarding.description } <LearnMore />
					</p>

					<p className="accepted-payment-methods">
						{ strings.paymentMethodsHeading }
					</p>

					<PaymentMethods />
				</div>
			</CardBody>
		</Card>
	);
};

const ConnectAccountPage = () => {
	const [ errorMessage, setErrorMessage ] = useState( '' );

	const [ enabledApms, setEnabledApms ] = useState( new Set< Apm >() );

	const { isJetpackConnected, connectUrl, hasViewedWelcomePage } = useSelect(
		( select ) => {
			const { getOption } = select( OPTIONS_STORE_NAME );
			let pageViewTimestamp = getOption(
				'wc_pay_welcome_page_viewed_timestamp'
			);
			pageViewTimestamp =
				typeof pageViewTimestamp === 'undefined' ||
				typeof pageViewTimestamp === 'string'
					? true
					: false;

			return {
				isJetpackConnected:
					select( 'wc/admin/plugins' ).isJetpackConnected(),
				connectUrl:
					'admin.php?wcpay-connect=1&_wpnonce=' +
					getAdminSetting( 'wcpay_welcome_page_connect_nonce' ),
				hasViewedWelcomePage: pageViewTimestamp,
			};
		}
	);
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	/**
	 * Submits a request to store viewing welcome time.
	 */
	const storeViewWelcome = () => {
		if ( ! hasViewedWelcomePage ) {
			updateOptions( {
				wc_pay_welcome_page_viewed_timestamp: Math.floor(
					Date.now() / 1000
				),
			} );
		}
	};
	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_connect_core_test',
		} );
		storeViewWelcome();
	}, [ hasViewedWelcomePage ] );

	const { installAndActivatePlugins } = useDispatch( 'wc/admin/plugins' );

	return (
		<div className="connect-account-page">
			<div className="woocommerce-payments-page is-narrow connect-account">
				<ConnectPageError errorMessage={ errorMessage } />
				<ConnectPageOnboarding
					isJetpackConnected={ isJetpackConnected }
					installAndActivatePlugins={ installAndActivatePlugins }
					connectUrl={ connectUrl }
					setErrorMessage={ setErrorMessage }
					enabledApms={ enabledApms }
				/>
				<Banner />
				<ApmList
					enabledApms={ enabledApms }
					setEnabledApms={ setEnabledApms }
				/>
				<FrequentlyAskedQuestionsSimple />
			</div>
		</div>
	);
};
export default ConnectAccountPage;
