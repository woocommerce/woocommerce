/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { EllipsisMenu, Link } from '@woocommerce/components';
import { useState, useEffect } from '@wordpress/element';
import {
	pluginsStore,
	paymentGatewaysStore,
	paymentSettingsStore,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { sanitizeHTML } from '@woocommerce/sanitize';
import { __ } from '@wordpress/i18n';
import { WooPaymentsMethodsLogos } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import './payment-promotion-row.scss';

function sanitizeHTMLForReact( html: string ) {
	return {
		__html: sanitizeHTML( html, {
			tags: [ 'a', 'img', 'br' ],
			attr: [ 'href', 'src', 'class', 'alt', 'target' ],
		} ),
	};
}

type PaymentPromotionRowProps = {
	paymentMethod: {
		gatewayId: string;
		pluginSlug: string;
		url: string;
	};
	title?: string;
	columns: {
		className: string;
		html: string;
		width: string | number | undefined;
	}[];
	subTitleContent?: string;
};

export const PaymentPromotionRow = ( {
	paymentMethod,
	title,
	subTitleContent,
	columns,
}: PaymentPromotionRowProps ) => {
	const { gatewayId, pluginSlug, url } = paymentMethod;
	const [ installing, setInstalling ] = useState( false );
	const [ isVisible, setIsVisible ] = useState( true );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );
	const { createNotice } = useDispatch( 'core/notices' );
	const { updatePaymentGateway } = useDispatch( paymentGatewaysStore );
	const { gatewayIsActive, paymentGateway } = useSelect( ( select ) => {
		const { getPaymentGateway } = select( paymentGatewaysStore );
		const activePlugins: string[] =
			select( pluginsStore ).getActivePlugins();
		const isActive = activePlugins && activePlugins.includes( pluginSlug );
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
	}, [] );

	const isWooPayEligible = useSelect( ( select ) => {
		const store = select( paymentSettingsStore );
		return store.getIsWooPayEligible();
	}, [] );

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

	const onDismiss = () => {
		setIsVisible( false );
		recordEvent( 'settings_payments_promotions_dismiss', {
			id: gatewayId,
		} );
		updatePaymentGateway( gatewayId, {
			settings: {
				is_dismissed: 'yes',
			},
		} );
	};

	if ( ! isVisible ) {
		return null;
	}

	return (
		<>
			{ columns.map( ( column ) => {
				if ( column.className.includes( 'name' ) ) {
					return (
						<td className="name" key={ column.className }>
							<div className="wc-payment-gateway-method__name">
								<Link
									target="_blank"
									type="external"
									rel="noreferrer"
									href={ url }
								>
									{ title }
								</Link>
								{ gatewayId ===
									'pre_install_woocommerce_payments_promotion' && (
									<div className="pre-install-payment-gateway__subtitle">
										<WooPaymentsMethodsLogos
											maxElements={ 5 }
											isWooPayEligible={
												isWooPayEligible
											}
										/>
									</div>
								) }
								{ gatewayId !==
									'pre_install_woocommerce_payments_promotion' &&
								subTitleContent ? (
									<div
										className="pre-install-payment-gateway__subtitle"
										// eslint-disable-next-line react/no-danger -- innerHTML from the element with class name: gateway-subtitle.
										dangerouslySetInnerHTML={ sanitizeHTMLForReact(
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
							className="pre-install-payment-gateway__status"
							key={ column.className }
						></td>
					);
				} else if ( column.className.includes( 'action' ) ) {
					return (
						<td className="action" key={ column.className }>
							<div className="pre-install-payment-gateway__actions">
								<EllipsisMenu
									label={ __(
										'Payment Promotion Options',
										'woocommerce'
									) }
									className="pre-install-payment-gateway__actions-menu"
									onToggle={ (
										e:
											| React.MouseEvent
											| React.KeyboardEvent
									) => e.stopPropagation() }
									renderContent={ () => (
										<div className="pre-install-payment-gateway__actions-menu-options">
											<Button onClick={ onDismiss }>
												{ __(
													'Dismiss',
													'woocommerce'
												) }
											</Button>
										</div>
									) }
								/>
								<Button
									className="button alignright"
									onClick={ () => installPaymentGateway() }
									isSecondary
									isBusy={ installing }
									aria-disabled={ installing }
								>
									{ __( 'Install', 'woocommerce' ) }
								</Button>
							</div>
						</td>
					);
				}
				return (
					<td
						key={ column.className }
						className={ column.className }
						width={ column.width }
						dangerouslySetInnerHTML={
							column.className.includes( 'sort' ) ||
							column.className.includes( 'renewals' )
								? {
										__html: column.html,
								  }
								: sanitizeHTMLForReact( column.html )
						}
					></td>
				);
			} ) }
		</>
	);
};
