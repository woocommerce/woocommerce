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
	SETTINGS_STORE_NAME,
	WCDataSelector,
	Plugin,
	WPDataSelectors,
	OPTIONS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import ExternalIcon from 'gridicons/dist/external';
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import './payment-recommendations.scss';
import { getCountryCode } from '../dashboard/utils';
import { createNoticesFromResponse } from '../lib/notices';
import { isWCPaySupported } from '~/task-list/tasks/PaymentGatewaySuggestions/components/WCPay';

const SEE_MORE_LINK =
	'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations';
const DISMISS_OPTION = 'woocommerce_setting_payments_recommendations_hidden';
type SettingsSelector = WPDataSelectors & {
	getSettings: (
		type: string
	) => { general: { woocommerce_default_country?: string } };
};

type OptionsSelector = WPDataSelectors & {
	getOption: ( option: string ) => boolean | string;
};

export function getPaymentRecommendationData(
	select: WCDataSelector
): {
	displayable: boolean;
	recommendedPlugins?: Plugin[];
	isLoading: boolean;
} {
	const { getOption, isResolving: isResolvingOption } = select(
		OPTIONS_STORE_NAME
	) as OptionsSelector;
	const { getSettings } = select( SETTINGS_STORE_NAME ) as SettingsSelector;
	const { getRecommendedPlugins } = select( PLUGINS_STORE_NAME );
	const { general: settings } = getSettings( 'general' );

	const hidden = getOption( DISMISS_OPTION );
	const countryCode =
		settings && settings.woocommerce_default_country
			? getCountryCode( settings.woocommerce_default_country )
			: null;
	const countrySupported = countryCode
		? isWCPaySupported( countryCode )
		: false;
	const isRequestingOptions = isResolvingOption( 'getOption', [
		DISMISS_OPTION,
	] );

	const displayable =
		! isRequestingOptions && hidden !== 'yes' && countrySupported;
	let plugins = null;
	if ( displayable ) {
		// don't get recommended plugins until it is displayable.
		plugins = getRecommendedPlugins( 'payments' );
	}
	const isLoading =
		isRequestingOptions ||
		hidden === undefined ||
		settings === undefined ||
		plugins === undefined;

	return {
		displayable,
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
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { displayable, recommendedPlugins, isLoading } = useSelect(
		getPaymentRecommendationData
	);
	const triggeredPageViewRef = useRef( false );
	const shouldShowRecommendations =
		displayable && recommendedPlugins && recommendedPlugins.length > 0;

	useEffect( () => {
		if (
			( shouldShowRecommendations ||
				( WcPayPromotionGateway && ! isLoading ) ) &&
			! triggeredPageViewRef.current
		) {
			triggeredPageViewRef.current = true;
			const eventProps = ( recommendedPlugins || [] ).reduce(
				( props, plugin ) => {
					if ( plugin.product ) {
						return {
							...props,
							[ plugin.product.replace( /\-/g, '_' ) +
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

	const dismissPaymentRecommendations = () => {
		recordEvent( 'settings_payments_recommendations_dismiss', {} );
		updateOptions( {
			[ DISMISS_OPTION ]: 'yes',
		} );
	};

	const setupPlugin = ( plugin: Plugin ) => {
		if ( installingPlugin ) {
			return;
		}
		setInstallingPlugin( plugin.product );
		recordEvent( 'settings_payments_recommendations_setup', {
			extension_selected: plugin.product,
		} );
		installAndActivatePlugins( [ plugin.product ] )
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
				key: plugin.slug,
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
				content: decodeEntities( plugin.copy ),
				after: (
					<Button
						isSecondary
						onClick={ () => setupPlugin( plugin ) }
						isBusy={ installingPlugin === plugin.product }
						disabled={ !! installingPlugin }
					>
						{ plugin[ 'button-text' ] }
					</Button>
				),
				before: <img src={ plugin.icon } alt="" />,
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
