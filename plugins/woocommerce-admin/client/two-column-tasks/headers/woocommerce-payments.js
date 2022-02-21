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
import GetPaid from './illustrations/get-paid';

const connect = ( createNotice, setIsBusy ) => {
	const errorMessage = __(
		'There was an error connecting to WooCommerce Payments. Please try again or connect later in store settings.',
		'woocommerce-admin'
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
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isBusy, setIsBusy ] = useState( false );
	const onClick = () => {
		trackClick();
		connect( createNotice, setIsBusy );
	};

	return (
		<div className="woocommerce-task-header__contents-container">
			<GetPaid className="svg-background" />
			<div className="woocommerce-task-header__contents">
				<h1>{ __( "It's time to get paid", 'woocommerce-admin' ) }</h1>
				<p>
					{ __(
						"You're only one step away from getting paid. Verify your business details to start managing transactions with WooCommerce Payments.",
						'woocommerce-admin'
					) }
				</p>
				<p>
					{ interpolateComponents( {
						mixedString: __(
							'By clicking "Verify Details", you agree to the {{link}}Terms of Service{{/link}}',
							'woocommerce-admin'
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
					{ __( 'Verify details', 'woocommerce-admin' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ __( '2 minutes' ) }</span>
				</p>
			</div>
		</div>
	);
};

export default WoocommercePaymentsHeader;
