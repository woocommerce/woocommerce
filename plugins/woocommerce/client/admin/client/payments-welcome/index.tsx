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

interface activatePromoResponse {
	success: boolean;
}

const ConnectAccountPage = () => {
	const incentive = getAdminSetting( 'wcpayWelcomePageIncentive' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { installAndActivatePlugins } = useDispatch( 'wc/admin/plugins' );
	const [ isSubmitted, setSubmitted ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ enabledApms, setEnabledApms ] = useState( new Set< Apm >() );

	const { isJetpackConnected, connectUrl } = useSelect( ( select ) => {
		return {
			isJetpackConnected:
				select( 'wc/admin/plugins' ).isJetpackConnected(),
			connectUrl:
				'admin.php?wcpay-connect=1&promo=' +
				encodeURIComponent( incentive.id ) +
				'&_wpnonce=' +
				getAdminSetting( 'wcpay_welcome_page_connect_nonce' ) +
				'&from=WCADMIN_PAYMENT_INCENTIVE',
		};
	} );

	const determineTrackingSource = () => {
		const urlParams = new URLSearchParams( window.location.search );
		const from = urlParams.get( 'from' ) || '';

		// Determine where the user came from.
		let source = 'wcadmin';
		switch ( from ) {
			case 'WCADMIN_PAYMENT_TASK':
				source = 'wcadmin-payment-task';
				break;
			case 'WCADMIN_PAYMENT_SETTINGS':
				source = 'wcadmin-settings-page';
				break;
		}

		return source;
	};

	/**
	 * Record page view and save viewed timestamp.
	 */
	useEffect( () => {
		recordEvent( 'page_view', {
			path: 'payments_connect_core_test',
			incentive_id: incentive.id,
			source: determineTrackingSource(),
		} );
		updateOptions( {
			wcpay_welcome_page_viewed_timestamp: Math.floor(
				Date.now() / 1000
			),
		} );
		// We only want to record this once, so we don't want to re-run this effect.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const activatePromo = async () => {
		const activatePromoRequest: activatePromoResponse = await apiFetch( {
			path: `/wc-analytics/admin/notes/experimental-activate-promo/${ incentive.id }`,
			method: 'POST',
		} );
		if ( activatePromoRequest?.success ) {
			window.location.href = connectUrl;
		}
	};

	const trackConnectAccountClicked = () => {
		recordEvent( 'wcpay_connect_account_clicked', {
			wpcom_connection: isJetpackConnected ? 'Yes' : 'No',
			incentive_id: incentive.id,
			path: 'payments_connect_core_test',
			source: determineTrackingSource(),
		} );
	};

	const handleSetup = async () => {
		setSubmitted( true );

		trackConnectAccountClicked();

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
					incentive_id: incentive.id,
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

	if ( ! incentive ) return null;

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
