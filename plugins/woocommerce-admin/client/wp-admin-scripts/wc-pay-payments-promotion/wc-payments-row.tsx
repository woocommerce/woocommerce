/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import {
	PLUGINS_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	Visa,
	MasterCard,
	Amex,
	ApplePay,
	GooglePay,
} from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import './wc-payments-row.scss';

type WCPaymentsRowProps = {
	sortColumnContent: string;
	descriptionColumnContent: string;
};

const WC_PAY_SLUG = 'woocommerce-payments';
export const WCPaymentsRow: React.FC< WCPaymentsRowProps > = ( {
	sortColumnContent,
	descriptionColumnContent,
} ) => {
	const [ installing, setInstalling ] = useState( false );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );
	const wcPayInstallationInfo = useSelect( ( select ) => {
		const { getPaymentGateway } = select( PAYMENT_GATEWAYS_STORE_NAME );
		const activePlugins: string[] = select(
			PLUGINS_STORE_NAME
		).getActivePlugins();
		const isWCPayActive =
			activePlugins && activePlugins.includes( WC_PAY_SLUG );
		let wcPayGateway;
		if ( isWCPayActive ) {
			wcPayGateway = getPaymentGateway(
				WC_PAY_SLUG.replace( /\-/g, '_' )
			);
		}

		return {
			isWCPayActive,
			wcPayGateway,
		};
	} );

	useEffect( () => {
		if (
			wcPayInstallationInfo.isWCPayActive &&
			wcPayInstallationInfo.wcPayGateway &&
			wcPayInstallationInfo.wcPayGateway.settings_url
		) {
			window.location.href =
				wcPayInstallationInfo.wcPayGateway.settings_url;
		}
	}, [
		wcPayInstallationInfo.isWCPayActive,
		wcPayInstallationInfo.wcPayGateway,
	] );

	const installWCPay = () => {
		if ( installing ) {
			return;
		}
		setInstalling( true );
		recordEvent( 'settings_payments_recommendations_setup', {
			extension_selected: WC_PAY_SLUG,
		} );
		installAndActivatePlugins( [ WC_PAY_SLUG ] ).catch(
			( response: { message?: string } ) => {
				if ( response.message ) {
					createNotice( 'error', response.message );
				}
				setInstalling( false );
			}
		);
	};

	return (
		<>
			<td
				className="sort ui-sortable-handle"
				width="1%"
				dangerouslySetInnerHTML={ {
					__html: sortColumnContent,
				} }
			></td>
			<td className="name">
				<div className="pre-install-wcpay_name">
					<Link
						target="_blank"
						type="external"
						rel="noreferrer"
						href="https://woocommerce.com/payments/?utm_medium=product"
					>
						{ __( 'WooCommerce Payments', 'woocommerce-admin' ) }
					</Link>
					<div className="pre-install-wcpay_accepted">
						<Visa />
						<MasterCard />
						<Amex />
						<GooglePay />
						<ApplePay />
					</div>
				</div>
			</td>
			<td className="pre-install-wcpay_status"></td>
			<td
				className="description"
				dangerouslySetInnerHTML={ {
					__html: descriptionColumnContent,
				} }
			></td>
			<td className="action">
				<Button
					className="button alignright"
					onClick={ () => installWCPay() }
					isSecondary
					isBusy={ installing }
					aria-disabled={ installing }
				>
					{ __( 'Install', 'woocommerce-admin' ) }
				</Button>
			</td>
		</>
	);
};
