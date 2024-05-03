/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import { Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
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
					WC_ASSET_URL + 'images/task_list/payment-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( "It's time to get paid", 'woocommerce' ) }</h1>
				{ incentive?.task_header_content ? (
					<p
						dangerouslySetInnerHTML={ sanitizeHTML(
							incentive.task_header_content
						) }
					/>
				) : (
					<p>
						{ __(
							"You're only one step away from getting paid. Verify your business details to start managing transactions with WooPayments.",
							'woocommerce'
						) }
					</p>
				) }
				<p>
					{ interpolateComponents( {
						mixedString: __(
							'By clicking "Verify Details", you agree to the {{link}}Terms of Service{{/link}}',
							'woocommerce'
						),
						components: {
							link: (
								<Link
									href="https://wordpress.com/tos/"
									target="_blank"
									type="external"
									rel="noreferrer"
								/>
							),
						},
					} ) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					isBusy={ isBusy }
					disabled={ isBusy }
					onClick={ onClick }
				>
					{ __( 'Verify details', 'woocommerce' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ __( '2 minutes', 'woocommerce' ) }</span>
				</p>
			</div>
		</div>
	);
};

export default WoocommercePaymentsHeader;
