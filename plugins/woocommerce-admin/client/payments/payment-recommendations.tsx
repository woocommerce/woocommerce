/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import {
	Card,
	CardBody,
	CardHeader,
	CardFooter,
	Button,
} from '@wordpress/components';
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

/**
 * Internal dependencies
 */
import './payment-recommendations.scss';
import { getCountryCode } from '../dashboard/utils';
import { getAdminLink } from '../wc-admin-settings';
import { createNoticesFromResponse } from '../lib/notices';
import { isWCPaySupported } from '../task-list/tasks/payments/methods/wcpay';

const SEE_MORE_LINK =
	'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/?utm_source=payments_recommendations';
const DISMISS_OPTION = 'woocommerce_setting_payments_recommendations_hidden';
const SHOW_MARKETPLACE_SUGGESTION_OPTION =
	'woocommerce_show_marketplace_suggestions';
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
} {
	const { getOption, isResolving: isResolvingOption } = select(
		OPTIONS_STORE_NAME
	) as OptionsSelector;
	const { getSettings } = select( SETTINGS_STORE_NAME ) as SettingsSelector;
	const { getRecommendedPlugins } = select( PLUGINS_STORE_NAME );
	const { general: settings = {} } = getSettings( 'general' );
	const marketplaceSuggestions = getOption(
		SHOW_MARKETPLACE_SUGGESTION_OPTION
	);

	const hidden = getOption( DISMISS_OPTION );
	const countryCode = settings.woocommerce_default_country
		? getCountryCode( settings.woocommerce_default_country )
		: null;
	const countrySupported = countryCode
		? isWCPaySupported( countryCode )
		: false;
	const isRequestingOptions =
		isResolvingOption( 'getOption', [ DISMISS_OPTION ] ) ||
		isResolvingOption( 'getOption', [
			SHOW_MARKETPLACE_SUGGESTION_OPTION,
		] );

	const displayable =
		! isRequestingOptions &&
		hidden !== 'yes' &&
		marketplaceSuggestions === 'yes' &&
		countrySupported;
	let plugins;
	if ( displayable ) {
		// don't get recommended plugins until it is displayable.
		plugins = getRecommendedPlugins( 'payments' );
	}

	return {
		displayable,
		recommendedPlugins: plugins,
	};
}

const PaymentRecommendations: React.FC = () => {
	const [ installingPlugin, setInstallingPlugin ] = useState< string | null >(
		null
	);
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { displayable, recommendedPlugins } = useSelect(
		getPaymentRecommendationData
	);
	const triggeredPageViewRef = useRef( false );
	const shouldShowRecommendations =
		displayable && recommendedPlugins && recommendedPlugins.length > 0;

	useEffect( () => {
		if ( shouldShowRecommendations && ! triggeredPageViewRef.current ) {
			triggeredPageViewRef.current = true;
			recordEvent( 'settings_payments_recommendations_pageview', {} );
		}
	}, [ shouldShowRecommendations ] );

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
		<Card size="large" className="woocommerce-recommended-payments-card">
			<CardHeader size="medium">
				<div className="woocommerce-recommended-payments-card__header">
					<Text variant="title.small">
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
			<CardBody>
				<List items={ pluginsList } />
			</CardBody>
			<CardFooter>
				<Button href={ SEE_MORE_LINK } isTertiary>
					{ __( 'See more options', 'woocommerce-admin' ) }
				</Button>
			</CardFooter>
		</Card>
	);
};

export default PaymentRecommendations;
