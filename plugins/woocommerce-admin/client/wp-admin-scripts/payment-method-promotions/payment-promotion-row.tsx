/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import {
	PLUGINS_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	WCDataSelector,
	PluginsStoreActions,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { sanitize } from 'dompurify';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './payment-promotion-row.scss';

function sanitizeHTML( html: string ) {
	return {
		__html: sanitize( html, {
			ALLOWED_TAGS: [ 'a', 'img', 'br' ],
			ALLOWED_ATTR: [ 'href', 'src', 'class', 'alt', 'target' ],
		} ),
	};
}

type PaymentPromotionRowProps = {
	pluginSlug: string;
	titleLink: string;
	title: string;
	columns: {
		className: string;
		html: string;
		width: string;
	}[];
	subTitleContent?: string;
};

export const PaymentPromotionRow: React.FC< PaymentPromotionRowProps > = ( {
	pluginSlug,
	title,
	titleLink,
	subTitleContent,
	columns,
} ) => {
	const [ installing, setInstalling ] = useState( false );
	const { installAndActivatePlugins }: PluginsStoreActions = useDispatch(
		PLUGINS_STORE_NAME
	);
	const { createNotice } = useDispatch( 'core/notices' );
	const { gatewayIsActive, paymentGateway } = useSelect(
		( select: WCDataSelector ) => {
			const { getPaymentGateway } = select( PAYMENT_GATEWAYS_STORE_NAME );
			const activePlugins: string[] = select(
				PLUGINS_STORE_NAME
			).getActivePlugins();
			const isActive =
				activePlugins && activePlugins.includes( pluginSlug );
			let paymentGatewayData;
			if ( isActive ) {
				paymentGatewayData = getPaymentGateway(
					pluginSlug.replace( /\-/g, '_' )
				);
			}

			return {
				gatewayIsActive: isActive,
				paymentGateway: paymentGatewayData,
			};
		}
	);

	useEffect( () => {
		if (
			gatewayIsActive &&
			paymentGateway &&
			paymentGateway.settings_url
		) {
			window.location.href = paymentGateway.settings_url;
		}
	}, [ gatewayIsActive, paymentGateway ] );

	const installPaymentGateway = () => {
		if ( installing ) {
			return;
		}
		setInstalling( true );
		recordEvent( 'settings_payments_recommendations_setup', {
			extension_selected: pluginSlug,
		} );
		installAndActivatePlugins( [ pluginSlug ] ).catch(
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
			{ columns.map( ( column ) => {
				if ( column.className.includes( 'name' ) ) {
					return (
						<td className="name" key={ column.className }>
							<div className="wc-payment-gateway-method_name">
								<Link
									target="_blank"
									type="external"
									rel="noreferrer"
									href={ titleLink }
								>
									{ title }
								</Link>
								{ subTitleContent ? (
									<div
										className="pre-install-payment-gateway_subtitle"
										dangerouslySetInnerHTML={ sanitizeHTML(
											subTitleContent
										) }
									></div>
								) : null }
							</div>
						</td>
					);
				} else if ( column.className.includes( 'status' ) ) {
					return (
						<td
							className="pre-install-payment-gateway_status"
							key={ column.className }
						></td>
					);
				} else if ( column.className.includes( 'action' ) ) {
					return (
						<td className="action" key={ column.className }>
							<Button
								className="button alignright"
								onClick={ () => installPaymentGateway() }
								isSecondary
								isBusy={ installing }
								aria-disabled={ installing }
							>
								{ __( 'Install', 'woocommerce-admin' ) }
							</Button>
						</td>
					);
				}
				return (
					<td
						key={ column.className }
						className={ column.className }
						width={ column.width }
						dangerouslySetInnerHTML={
							column.className.includes( 'sort' )
								? {
										__html: column.html,
								  }
								: sanitizeHTML( column.html )
						}
					></td>
				);
			} ) }
		</>
	);
};
