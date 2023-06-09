/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import Banner from './banner';
import ApmList, { Apm } from './apms';
import './style.scss';
import { getAdminSetting } from '~/utils/admin-settings';
import FrequentlyAskedQuestionsSimple from './faq-simple';

declare global {
	interface Window {
		wcCalypsoBridge: unknown;
		location: Location;
		wcSettings: {
			admin: {
				wcpay_welcome_page_connect_nonce: string;
				currentUserData: {
					first_name: string;
				};
			};
		};
	}
}

interface activatePromoResponse {
	success: boolean;
}

const PROMO_NAME = 'wcpay-promo-2022-us-incentive-20-off';

const ConnectAccountPage = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { installAndActivatePlugins } = useDispatch( 'wc/admin/plugins' );
	const [ isSubmitted, setSubmitted ] = useState( false );
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
				setErrorMessage( e.message );
				setSubmitted( false );
			} else {
				throw new Error( `Unexpected error occurred. ${ e }` );
			}
		}
	};

	return (
		<div className="woopayments-welcome-page">
			{ errorMessage && (
				<Notice status="error" isDismissible={ false }>
					{ errorMessage }
				</Notice>
			) }
			<Banner isSubmitted={ isSubmitted } handleSetup={ handleSetup } />
			<ApmList
				enabledApms={ enabledApms }
				setEnabledApms={ setEnabledApms }
			/>
			<FrequentlyAskedQuestionsSimple />
		</div>
	);
};
export default ConnectAccountPage;
