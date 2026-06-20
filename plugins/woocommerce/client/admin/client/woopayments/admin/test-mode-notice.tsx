/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsAccountSettings } from '../settings/api';
import { getSettingsPaymentsProviderRouteUrl } from './utils';

type TestModeNoticePage = 'loans' | 'payments';

const pageLabels: Record< TestModeNoticePage, string > = {
	loans: __( 'loans', 'woocommerce' ),
	payments: __( 'payments', 'woocommerce' ),
};

export const WooPaymentsTestModeNotice = ( {
	currentPage,
	isDetailsView = false,
}: {
	currentPage: TestModeNoticePage;
	isDetailsView?: boolean;
} ) => {
	const [ isTestMode, setIsTestMode ] = useState( false );

	useEffect( () => {
		let isMounted = true;

		getWooPaymentsAccountSettings()
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				const account = response.account;
				setIsTestMode(
					!! account?.connected &&
						! account.live &&
						( !! account.test_mode ||
							!! account.test_drive ||
							!! account.sandbox ||
							account.mode === 'test' )
				);
			} )
			.catch( () => {
				if ( isMounted ) {
					setIsTestMode( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	if ( ! isTestMode ) {
		return null;
	}

	const pageLabel = pageLabels[ currentPage ];
	const settingsUrl = getSettingsPaymentsProviderRouteUrl(
		'/woopayments/settings'
	);

	return (
		<Notice
			className="woocommerce-woopayments-test-mode-notice"
			status="warning"
			isDismissible={ false }
		>
			<span>
				<strong>
					{ sprintf(
						/* translators: %s: current WooPayments admin resource, such as "loans". */
						__( 'Viewing test %s.', 'woocommerce' ),
						pageLabel
					) }
				</strong>{ ' ' }
				{ isDetailsView && currentPage === 'payments' ? (
					<>
						{ sprintf(
							/* translators: 1: payment provider name. */
							__(
								'Your %1$s account is currently in test mode. To view live payments, disable test mode in',
								'woocommerce'
							),
							'WooPayments'
						) }{ ' ' }
						<a href={ settingsUrl }>
							{ sprintf(
								/* translators: %s: payment provider name. */
								__( '%s settings', 'woocommerce' ),
								'WooPayments'
							) }
						</a>
						.
					</>
				) : (
					<>
						{ sprintf(
							/* translators: %s: current WooPayments admin resource, such as "loans". */
							__(
								'To view live %s, disable test mode in',
								'woocommerce'
							),
							pageLabel
						) }{ ' ' }
						<a href={ settingsUrl }>
							{ __( 'WooPayments settings', 'woocommerce' ) }
						</a>
						{ '.' }
					</>
				) }
			</span>
		</Notice>
	);
};
