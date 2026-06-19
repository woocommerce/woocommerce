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

type TestModeNoticePage = 'loans';

const pageLabels: Record< TestModeNoticePage, string > = {
	loans: __( 'loans', 'woocommerce' ),
};

export const WooPaymentsTestModeNotice = ( {
	currentPage,
}: {
	currentPage: TestModeNoticePage;
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
				{ sprintf(
					/* translators: %s: current WooPayments admin resource, such as "loans". */
					__(
						'To view live %s, disable test mode in',
						'woocommerce'
					),
					pageLabel
				) }{ ' ' }
				<a
					href={ getSettingsPaymentsProviderRouteUrl(
						'/woopayments/settings'
					) }
				>
					{ __( 'WooPayments settings', 'woocommerce' ) }
				</a>
				{ '.' }
			</span>
		</Notice>
	);
};
