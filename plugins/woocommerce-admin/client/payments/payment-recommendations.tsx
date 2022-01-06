/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { Card, CardHeader, CardFooter, Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { EllipsisMenu, List, Pill } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';
import {
	PLUGINS_STORE_NAME,
	WCDataSelector,
	Plugin,
	PluginsStoreActions,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import ExternalIcon from 'gridicons/dist/external';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './payment-recommendations.scss';
import { createNoticesFromResponse } from '../lib/notices';

const SEE_MORE_LINK =
	'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations';

export function getPaymentRecommendationData(
	select: WCDataSelector
): {
	recommendedPlugins?: Plugin[];
	isLoading: boolean;
} {
	const { getRecommendedPlugins } = select( PLUGINS_STORE_NAME );

	const plugins = getRecommendedPlugins( 'payments' );
	const isLoading = plugins === undefined;

	return {
		recommendedPlugins: plugins,
		isLoading,
	};
}

const WcPayPromotionGateway = document.querySelector(
	'[data-gateway_id="pre_install_woocommerce_payments_promotion"]'
);

const PaymentRecommendations: React.FC = () => {
	const [ installingPlugin, setInstallingPlugin ] = useState< string | null >(
		null
	);
	const {
		installAndActivatePlugins,
		dismissRecommendedPlugins,
		invalidateResolution,
	}: PluginsStoreActions = useDispatch( PLUGINS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );
	const { recommendedPlugins, isLoading } = useSelect(
		getPaymentRecommendationData
	);
	const triggeredPageViewRef = useRef( false );
	const shouldShowRecommendations =
		recommendedPlugins && recommendedPlugins.length > 0;

	useEffect( () => {
		if (
			( shouldShowRecommendations ||
				( WcPayPromotionGateway && ! isLoading ) ) &&
			! triggeredPageViewRef.current
		) {
			triggeredPageViewRef.current = true;
			const eventProps = ( recommendedPlugins || [] ).reduce(
				( props, plugin ) => {
					if ( plugin.plugins && plugin.plugins.length > 0 ) {
						return {
							...props,
							[ plugin.plugins[ 0 ].replace( /\-/g, '_' ) +
							'_displayed' ]: true,
						};
					}
					return props;
				},
				{
					woocommerce_payments_displayed: !! WcPayPromotionGateway,
				}
			);
			recordEvent(
				'settings_payments_recommendations_pageview',
				eventProps
			);
		}
	}, [ shouldShowRecommendations, WcPayPromotionGateway, isLoading ] );

	if ( ! shouldShowRecommendations ) {
		return null;
	}
	const dismissPaymentRecommendations = async () => {
		recordEvent( 'settings_payments_recommendations_dismiss', {} );
		const success = await dismissRecommendedPlugins( 'payments' );
		if ( success ) {
			invalidateResolution( 'getRecommendedPlugins', [ 'payments' ] );
		} else {
			createNotice(
				'error',
				__(
					'There was a problem hiding the "Recommended ways to get paid" card.',
					'woocommerce-admin'
				)
			);
		}
	};

	const setupPlugin = ( plugin: Plugin ) => {
		if ( installingPlugin ) {
			return;
		}
		setInstallingPlugin( plugin.id );
		recordEvent( 'settings_payments_recommendations_setup', {
			extension_selected: plugin.plugins[ 0 ],
		} );
		installAndActivatePlugins( [ plugin.plugins[ 0 ] ] )
			.then( () => {
				window.location.href = getAdminLink(
					plugin[ 'setup-link' ].replace( '/wp-admin/', '' )
				);
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setInstallingPlugin( null );
			} );
	};

	const pluginsList = ( recommendedPlugins || [] ).map(
		( plugin: Plugin ) => {
			return {
				key: plugin.id,
				title: (
					<>
						{ plugin.title }
						{ plugin.recommended && (
							<Pill>
								{ __( 'Recommended', 'woocommerce-admin' ) }
							</Pill>
						) }
					</>
				),
				content: decodeEntities( plugin.content ),
				after: (
					<Button
						isSecondary
						onClick={ () => setupPlugin( plugin ) }
						isBusy={ installingPlugin === plugin.id }
						disabled={ !! installingPlugin }
					>
						{ plugin[ 'button-text' ] }
					</Button>
				),
				before: <img src={ plugin.image } alt="" />,
			};
		}
	);

	return (
		<Card size="medium" className="woocommerce-recommended-payments-card">
			<CardHeader>
				<div className="woocommerce-recommended-payments-card__header">
					<Text
						variant="title.small"
						as="p"
						size="20"
						lineHeight="28px"
					>
						{ __(
							'Recommended ways to get paid',
							'woocommerce-admin'
						) }
					</Text>
					<Text
						className={
							'woocommerce-recommended-payments__header-heading'
						}
						variant="caption"
						as="p"
						size="12"
						lineHeight="16px"
					>
						{ __(
							'We recommend adding one of the following payment extensions to your store. The extension will be installed and activated for you when you click "Get started".',
							'woocommerce-admin'
						) }
					</Text>
				</div>
				<div className="woocommerce-card__menu woocommerce-card__header-item">
					<EllipsisMenu
						label={ __( 'Task List Options', 'woocommerce-admin' ) }
						renderContent={ () => (
							<div className="woocommerce-review-activity-card__section-controls">
								<Button
									onClick={ dismissPaymentRecommendations }
								>
									{ __( 'Hide this', 'woocommerce-admin' ) }
								</Button>
							</div>
						) }
					/>
				</div>
			</CardHeader>
			<List items={ pluginsList } />
			<CardFooter>
				<Button href={ SEE_MORE_LINK } target="_blank" isTertiary>
					{ __( 'See more options', 'woocommerce-admin' ) }
					<ExternalIcon size={ 18 } />
				</Button>
			</CardFooter>
		</Card>
	);
};

export default PaymentRecommendations;
