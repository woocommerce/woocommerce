/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL, getAdminSetting } from '~/utils/admin-settings';
import sanitizeHTML from '~/lib/sanitize-html';

const connect = ( createNotice, setIsBusy ) => {
	const errorMessage = __(
		'There was an error connecting to WooPayments. Please try again or connect later in store settings.',
		'woocommerce'
	);
	setIsBusy( true );
	apiFetch( {
		path: WC_ADMIN_NAMESPACE + '/plugins/connect-wcpay',
		method: 'POST',
	} )
		.then( ( response ) => {
			window.location = response.connectUrl;
		} )
		.catch( () => {
			createNotice( 'error', errorMessage );
			setIsBusy( false );
		} );
};

const WoocommercePaymentsHeader = ( { task, trackClick } ) => {
	const incentive =
		getAdminSetting( 'wcpayWelcomePageIncentive' ) ||
		window.wcpaySettings?.connectIncentive;
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isBusy, setIsBusy ] = useState( false );
	const onClick = () => {
		trackClick();
		connect( createNotice, setIsBusy );
	};

	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Payment illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL + 'images/task_list/payment-illustration.svg'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'It’s time to get paid', 'woocommerce' ) }</h1>
				{ incentive?.task_header_content ? (
					<p
						dangerouslySetInnerHTML={ sanitizeHTML(
							incentive.task_header_content
						) }
					/>
				) : (
					<p>
						{ __(
							'Power your payments with a simple, all-in-one option. Verify your business details to start managing transactions with WooCommerce Payments.',
							'woocommerce'
						) }
					</p>
				) }
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					isBusy={ isBusy }
					disabled={ isBusy }
					onClick={ onClick }
				>
					{ __( 'Get paid', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default WoocommercePaymentsHeader;
