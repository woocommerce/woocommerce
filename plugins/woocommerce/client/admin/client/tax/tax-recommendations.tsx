/**
 * External dependencies
 */
import { Button, CardFooter, ExternalLink } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { Children, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { PluginNames, pluginsStore, settingsStore } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import taxLogo from '../task-lists/fills/tax/woocommerce-tax/logo.png';
import { createNoticesFromResponse } from '../lib/notices';
import {
	DismissableList,
	DismissableListHeading,
} from '../settings-recommendations/dismissable-list';
import { supportsWooCommerceTax } from '../task-lists/fills/tax/utils';
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import { getCountryCode } from '~/dashboard/utils';
import { getAdminSetting } from '~/utils/admin-settings';
import { useEndpointDismiss } from '~/hooks/use-endpoint-dismiss';
import './tax-recommendations.scss';

const ANROK_LOGO_URL = 'https://ps.w.org/anrok-tax/assets/icon.svg';

type TaxRecommendation = {
	id: 'anrok-tax' | 'woocommerce-tax';
	title: string;
	description: string;
	productUrl: string;
	logo: React.ReactNode;
	pluginSlugs: string[];
};

const useInstallPlugin = () => {
	const [ pluginsBeingSetup, setPluginsBeingSetup ] = useState<
		Array< string >
	>( [] );

	const { installPlugins, activatePlugins } = useDispatch( pluginsStore );

	const handleInstall = ( slugs: string[] ): PromiseLike< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return installPlugins( slugs as Partial< PluginNames >[] )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

	const handleActivate = ( slugs: string[] ): PromiseLike< void > => {
		if ( pluginsBeingSetup.length > 0 ) {
			return Promise.resolve();
		}

		setPluginsBeingSetup( slugs );

		return activatePlugins( slugs as Partial< PluginNames >[] )
			.then( () => {
				setPluginsBeingSetup( [] );
			} )
			.catch( ( response: { errors: Record< string, string > } ) => {
				createNoticesFromResponse( response );
				setPluginsBeingSetup( [] );

				return Promise.reject();
			} );
	};

	return [ pluginsBeingSetup, handleInstall, handleActivate ] as const;
};

const TaxRecommendationItem = ( {
	pluginSlug,
	isPluginInstalled,
	isPluginActive,
	pluginsBeingSetup,
	onInstallClick,
	onActivateClick,
	title,
	description,
	productUrl,
	logo,
}: TaxRecommendation & {
	pluginSlug: string;
	isPluginInstalled: boolean;
	isPluginActive: boolean;
	pluginsBeingSetup: Array< string >;
	onInstallClick: ( slugs: string[] ) => PromiseLike< void >;
	onActivateClick: ( slugs: string[] ) => PromiseLike< void >;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleLearnMoreClick = () => {
		recordEvent( 'settings_tax_recommendation_click', {
			extension: title,
		} );
	};

	const handleClick = () => {
		const trackingBase = {
			context: 'settings',
			selected_plugin: pluginSlug,
		};

		recordEvent( 'tax_partner_click', trackingBase );
		recordEvent( 'settings_tax_recommendation_setup_click', {
			plugin: pluginSlug,
			action: isPluginInstalled ? 'activate' : 'install',
		} );

		const action = isPluginInstalled ? onActivateClick : onInstallClick;
		const eventName = isPluginInstalled
			? 'tax_partner_activate'
			: 'tax_partner_install';

		action( [ pluginSlug ] ).then(
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: true,
				} );
				createSuccessNotice(
					isPluginInstalled
						? sprintf(
								/* translators: %s: extension name. */
								__( '%s activated!', 'woocommerce' ),
								title
						  )
						: sprintf(
								/* translators: %s: extension name. */
								__( '%s is installed!', 'woocommerce' ),
								title
						  ),
					{}
				);
			},
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: false,
				} );
			}
		);
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-tax-recommendation-item">
			<div className="woocommerce-list__item-before">{ logo }</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">{ title }</span>
				<span className="woocommerce-list__item-content">
					{ description }
					<br />
					<ExternalLink
						href={ productUrl }
						onClick={ handleLearnMoreClick }
					>
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				{ isPluginActive ? (
					<Button
						variant="secondary"
						aria-disabled="true"
						aria-label={ sprintf(
							/* translators: %s: extension name. */
							__( '%s is already active', 'woocommerce' ),
							title
						) }
					>
						{ __( 'Active', 'woocommerce' ) }
					</Button>
				) : (
					<Button
						variant={ isPluginInstalled ? 'primary' : 'secondary' }
						onClick={ handleClick }
						isBusy={ pluginsBeingSetup.includes( pluginSlug ) }
						disabled={ pluginsBeingSetup.length > 0 }
					>
						{ isPluginInstalled
							? __( 'Activate', 'woocommerce' )
							: __( 'Install', 'woocommerce' ) }
					</Button>
				) }
			</div>
		</div>
	);
};

const TaxRecommendationsList = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const { isDismissed, onDismiss } = useEndpointDismiss(
		'/wc-admin/tax/recommendations/dismiss',
		getAdminSetting( 'taxRecommendationsHidden', false )
	);

	return (
		<DismissableList
			className="woocommerce-recommended-tax-extensions"
			isDismissed={ isDismissed }
		>
			<DismissableListHeading onDismiss={ onDismiss }>
				<Text variant="title.small" as="p" size="20" lineHeight="28px">
					{ __( 'Recommended tax solutions', 'woocommerce' ) }
				</Text>
				<Text
					className="woocommerce-recommended-tax__header-heading"
					variant="caption"
					as="p"
					size="12"
					lineHeight="16px"
				>
					{ __(
						'Explore tax extensions that can help automate calculations and compliance for your store.',
						'woocommerce'
					) }
				</Text>
			</DismissableListHeading>
			<ul className="woocommerce-list">
				{ Children.map( children, ( item ) => (
					<li className="woocommerce-list__item">{ item }</li>
				) ) }
			</ul>
			<CardFooter>
				<TrackedLink
					message={ __(
						// translators: {{Link}} is a placeholder for a html element.
						'Visit {{Link}}the WooCommerce Marketplace{{/Link}} to find more tax solutions.',
						'woocommerce'
					) }
					targetUrl={ getAdminLink(
						'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=operations'
					) }
					linkType="wc-admin"
					eventName="settings_tax_recommendation_visit_marketplace_click"
				/>
			</CardFooter>
		</DismissableList>
	);
};

const getPluginSlugForAction = (
	pluginSlugs: string[],
	installedPlugins: string[],
	activePlugins: string[]
) =>
	pluginSlugs.find(
		( pluginSlug ) =>
			activePlugins.includes( pluginSlug ) ||
			installedPlugins.includes( pluginSlug )
	) ?? pluginSlugs[ 0 ];

const TaxRecommendations = () => {
	const [ pluginsBeingSetup, handleInstall, handleActivate ] =
		useInstallPlugin();
	const { activePlugins, installedPlugins, countryCode } = useSelect(
		( select ) => {
			const settings = select( settingsStore ).getSettings( 'general' );
			const { getActivePlugins, getInstalledPlugins } =
				select( pluginsStore );

			return {
				activePlugins: getActivePlugins() ?? [],
				installedPlugins: getInstalledPlugins() ?? [],
				countryCode: getCountryCode(
					settings.general?.woocommerce_default_country
				),
			};
		},
		[]
	) ?? {
		activePlugins: [],
		installedPlugins: [],
		countryCode: '',
	};

	const recommendations: TaxRecommendation[] = [
		{
			id: 'woocommerce-tax',
			title: __( 'WooCommerce Tax', 'woocommerce' ),
			description: __(
				'Free, one-click tool to automate essential sales tax on every WooCommerce order.',
				'woocommerce'
			),
			productUrl: 'https://woocommerce.com/products/tax/',
			pluginSlugs: [ 'woocommerce-services', 'woocommerce-tax' ],
			logo: (
				<img
					className="woocommerce-tax-recommendation-item__logo"
					src={ taxLogo }
					alt=""
				/>
			),
		},
		{
			id: 'anrok-tax',
			title: __( 'Anrok', 'woocommerce' ),
			description: __(
				'Advanced tax compliance for growing brands selling within the US and around the globe.',
				'woocommerce'
			),
			productUrl: 'https://woocommerce.com/products/anrok-tax/',
			pluginSlugs: [ 'anrok-tax' ],
			logo: (
				<img
					className="woocommerce-tax-recommendation-item__logo"
					src={ ANROK_LOGO_URL }
					alt=""
				/>
			),
		},
	];
	const visibleRecommendations = recommendations.filter(
		( recommendation ) =>
			recommendation.id === 'anrok-tax' ||
			supportsWooCommerceTax( countryCode )
	);
	const visiblePluginSlugs = visibleRecommendations
		.map( ( recommendation ) => recommendation.pluginSlugs[ 0 ] )
		.join( ',' );
	const impressionFired = useRef( false );

	useEffect( () => {
		if (
			countryCode &&
			visibleRecommendations.length > 0 &&
			! impressionFired.current
		) {
			recordEvent( 'tax_partner_impression', {
				context: 'settings',
				country: countryCode,
				plugins: visiblePluginSlugs,
			} );
			impressionFired.current = true;
		}
	}, [ countryCode, visiblePluginSlugs, visibleRecommendations.length ] );

	return (
		<div className="woocommerce-recommended-tax-extensions-wrapper">
			<TaxRecommendationsList>
				{ visibleRecommendations.map( ( recommendation ) => {
					const isPluginActive = recommendation.pluginSlugs.some(
						( pluginSlug ) => activePlugins.includes( pluginSlug )
					);
					const isPluginInstalled =
						isPluginActive ||
						recommendation.pluginSlugs.some( ( pluginSlug ) =>
							installedPlugins.includes( pluginSlug )
						);

					return (
						<TaxRecommendationItem
							key={ recommendation.id }
							pluginSlug={ getPluginSlugForAction(
								recommendation.pluginSlugs,
								installedPlugins,
								activePlugins
							) }
							isPluginInstalled={ isPluginInstalled }
							isPluginActive={ isPluginActive }
							pluginsBeingSetup={ pluginsBeingSetup }
							onInstallClick={ handleInstall }
							onActivateClick={ handleActivate }
							{ ...recommendation }
						/>
					);
				} ) }
			</TaxRecommendationsList>
		</div>
	);
};

export default TaxRecommendations;
