/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Button, Card, CheckboxControl, Spinner } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { Link } from '@woocommerce/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import interpolateComponents from 'interpolate-components';
import { pluginNames, SETTINGS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import apiFetch from '@wordpress/api-fetch';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { AppIllustration } from '../app-illustration';
import './style.scss';
import { setAllPropsToValue } from '~/lib/collections';
import { getCountryCode } from '~/dashboard/utils';
import { isWCPaySupported } from '~/task-list/tasks/PaymentGatewaySuggestions/components/WCPay';

const generatePluginDescriptionWithLink = (
	description,
	productName,
	linkURL
) => {
	const url = linkURL ?? `https://woocommerce.com/products/${ productName }`;
	return interpolateComponents( {
		mixedString: description,
		components: {
			link: (
				<Link
					type="external"
					target="_blank"
					className="woocommerce-admin__business-details__selective-extensions-bundle__link"
					href={ url }
					onClick={ () => {
						recordEvent(
							'storeprofiler_store_business_features_link_click',
							{
								extension_name: productName,
							}
						);
					} }
				/>
			),
		},
	} );
};

const installableExtensionsData = [
	{
		title: __( 'Get the basics', 'woocommerce-admin' ),
		key: 'basics',
		plugins: [
			{
				key: 'woocommerce-payments',
				description: generatePluginDescriptionWithLink(
					__(
						'Accept credit cards with {{link}}WooCommerce Payments{{/link}}',
						'woocommerce-admin'
					),
					'woocommerce-payments'
				),
				isVisible: ( countryCode, industry ) => {
					const hasCbdIndustry = ( industry || [] ).some(
						( { industrySlug } ) => {
							return (
								industrySlug ===
								'cbd-other-hemp-derived-products'
							);
						}
					);
					return isWCPaySupported( countryCode ) && ! hasCbdIndustry;
				},
			},
			{
				key: 'woocommerce-services:shipping',
				description: generatePluginDescriptionWithLink(
					__(
						'Print shipping labels with {{link}}WooCommerce Shipping{{/link}}',
						'woocommerce-admin'
					),
					'shipping'
				),
				isVisible: ( countryCode, industry, productTypes ) => {
					// Exclude the WooCommerce Shipping mention if the user is not in the US.
					// Exclude the WooCommerce Shipping mention if the user is in the US but
					// only selected digital products in the Product Types step.
					if (
						countryCode !== 'US' ||
						( countryCode === 'US' &&
							productTypes.length === 1 &&
							productTypes[ 0 ] === 'downloads' )
					) {
						return false;
					}

					return true;
				},
			},
			{
				key: 'woocommerce-services:tax',
				description: generatePluginDescriptionWithLink(
					__(
						'Get automated sales tax with {{link}}WooCommerce Tax{{/link}}',
						'woocommerce-admin'
					),
					'tax'
				),
				isVisible: ( countryCode ) => {
					return [
						'US',
						'FR',
						'GB',
						'DE',
						'CA',
						'PL',
						'AU',
						'GR',
						'BE',
						'PT',
						'DK',
						'SE',
					].includes( countryCode );
				},
			},
			{
				key: 'jetpack',
				description: generatePluginDescriptionWithLink(
					__(
						'Enhance speed and security with {{link}}Jetpack{{/link}}',
						'woocommerce-admin'
					),
					'jetpack'
				),
			},
		],
	},
	{
		title: __( 'Grow your store', 'woocommerce-admin' ),
		key: 'grow',
		plugins: [
			{
				key: 'mailpoet',
				description: generatePluginDescriptionWithLink(
					__(
						'Level up your email marketing with {{link}}MailPoet{{/link}}',
						'woocommerce-admin'
					),
					'mailpoet',
					'https://wordpress.org/plugins/mailpoet/'
				),
			},
			{
				key: 'facebook-for-woocommerce',
				description: generatePluginDescriptionWithLink(
					__(
						'Market on {{link}}Facebook{{/link}}',
						'woocommerce-admin'
					),
					'facebook'
				),
			},
			{
				key: 'google-listings-and-ads',
				description: generatePluginDescriptionWithLink(
					__(
						'Drive sales with {{link}}Google Listings and Ads{{/link}}',
						'woocommerce-admin'
					),
					'google-listings-and-ads'
				),
			},
			{
				key: 'mailchimp-for-woocommerce',
				description: generatePluginDescriptionWithLink(
					__(
						'Contact customers with {{link}}Mailchimp{{/link}}',
						'woocommerce-admin'
					),
					'mailchimp-for-woocommerce'
				),
			},
			{
				key: 'creative-mail-by-constant-contact',
				description: generatePluginDescriptionWithLink(
					__(
						'Emails made easy with {{link}}Creative Mail{{/link}}',
						'woocommerce-admin'
					),
					'creative-mail-for-woocommerce'
				),
				selected: false,
			},
		],
	},
];

const FreeBadge = () => {
	return (
		<div className="woocommerce-admin__business-details__free-badge">
			{ __( 'Free', 'woocommerce-admin' ) }
		</div>
	);
};

const renderBusinessExtensionHelpText = ( values, isInstallingActivating ) => {
	const extensions = Object.keys( values ).filter(
		( key ) => values[ key ] && key !== 'install_extensions'
	);

	if ( extensions.length === 0 ) {
		return null;
	}

	const extensionsList = extensions
		.reduce( ( uniqueExtensionList, extension ) => {
			const extensionName = pluginNames[ extension ];
			return uniqueExtensionList.includes( extensionName )
				? uniqueExtensionList
				: [ ...uniqueExtensionList, extensionName ];
		}, [] )
		.join( ', ' );

	if ( isInstallingActivating ) {
		return (
			<div className="woocommerce-profile-wizard__footnote">
				<Text variant="caption" as="p" size="12" lineHeight="16px">
					{ sprintf(
						/* translators: %s: a comma separated list of plugins, e.g. Jetpack, Woocommerce Shipping */
						_n(
							'Installing the following plugin: %s',
							'Installing the following plugins: %s',
							extensions.length,
							'woocommerce-admin'
						),
						extensionsList
					) }
				</Text>
			</div>
		);
	}

	const installingJetpackOrWcShipping =
		extensions.includes( 'jetpack' ) ||
		extensions.includes( 'woocommerce-shipping' );

	const accountRequiredText = __(
		'User accounts are required to use these features.',
		'woocommerce-admin'
	);
	return (
		<div className="woocommerce-profile-wizard__footnote">
			<Text variant="caption" as="p" size="12" lineHeight="16px">
				{ sprintf(
					/* translators: %1$s: a comma separated list of plugins, e.g. Jetpack, Woocommerce Shipping, %2$s: text: 'User accounts are required to use these features.'  */
					_n(
						'The following plugin will be installed for free: %1$s. %2$s',
						'The following plugins will be installed for free: %1$s. %2$s',
						extensions.length,
						'woocommerce-admin'
					),
					extensionsList,
					accountRequiredText
				) }
			</Text>
			{ installingJetpackOrWcShipping && (
				<Text variant="caption" as="p" size="12" lineHeight="16px">
					{ interpolateComponents( {
						mixedString: __(
							'By installing Jetpack and WooCommerce Shipping plugins for free you agree to our {{link}}Terms of Service{{/link}}.',
							'woocommerce-admin'
						),
						components: {
							link: (
								<Link
									href="https://wordpress.com/tos/"
									target="_blank"
									type="external"
								/>
							),
						},
					} ) }
				</Text>
			) }
		</div>
	);
};

const BundleExtensionCheckbox = ( { onChange, description, isChecked } ) => {
	return (
		<div className="woocommerce-admin__business-details__selective-extensions-bundle__extension">
			<CheckboxControl
				id="woocommerce-business-extensions__checkbox"
				checked={ isChecked }
				onChange={ onChange }
			/>
			<p className="woocommerce-admin__business-details__selective-extensions-bundle__description">
				{ description }
			</p>
			<FreeBadge />
		</div>
	);
};

/**
 * Returns plugins that either don't have the acceptedCountryCodes param or one defined
 * that includes the passed in country.
 *
 * @param {Array} plugins  list of plugins
 * @param {string} country  Woo store country
 * @param {Array} industry List of selected industries
 * @param {Array} productTypes List of selected product types
 *
 * @return {Array} Array of visible plugins
 */
const getVisiblePlugins = ( plugins, country, industry, productTypes ) => {
	const countryCode = getCountryCode( country );

	return plugins.filter(
		( plugin ) =>
			! plugin.isVisible ||
			plugin.isVisible( countryCode, industry, productTypes )
	);
};

/**
 * Returns bundles that have at least 1 visible plugin.
 *
 * @param {Array} bundles  list of bundles
 * @param {string} country  Woo store country
 * @param {Array} industry List of selected industries
 * @param {Array} productTypes List of selected product types
 *
 * @return {Array} Array of visible bundles
 */
const getVisibleBundles = ( bundles, country, industry, productTypes ) => {
	return bundles
		.map( ( bundle ) => {
			return {
				...bundle,
				plugins: getVisiblePlugins(
					bundle.plugins,
					country,
					industry,
					productTypes
				),
			};
		} )
		.filter( ( bundle ) => {
			return bundle.plugins.length;
		} );
};

const transformRemoteExtensions = ( extensionData ) => {
	return extensionData.map( ( section ) => {
		const plugins = section.plugins.map( ( plugin ) => {
			return {
				...plugin,
				description: generatePluginDescriptionWithLink(
					plugin.description,
					plugin.product
				),
				isVisible: () => plugin.is_visible,
			};
		} );
		return {
			...section,
			plugins,
		};
	} );
};

const baseValues = { install_extensions: true };
export const createInitialValues = (
	extensions,
	country,
	industry,
	productTypes
) => {
	return extensions.reduce( ( acc, curr ) => {
		const plugins = getVisiblePlugins(
			curr.plugins,
			country,
			industry,
			productTypes
		).reduce( ( pluginAcc, { key, selected } ) => {
			return { ...pluginAcc, [ key ]: selected ?? true };
		}, {} );

		return {
			...acc,
			...plugins,
		};
	}, baseValues );
};

export const SelectiveExtensionsBundle = ( {
	isInstallingActivating,
	onSubmit,
	country,
	industry,
	productTypes,
} ) => {
	const [ showExtensions, setShowExtensions ] = useState( false );
	const [ values, setValues ] = useState( baseValues );
	const [ installableExtensions, setInstallableExtensions ] = useState( [
		{ key: 'spinner', plugins: [] },
	] );
	const [ isFetching, setIsFetching ] = useState( true );

	const allowMarketplaceSuggestions = useSelect( ( select ) =>
		select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'allowMarketplaceSuggestions'
		)
	);

	useEffect( () => {
		const setLocalInstallableExtensions = () => {
			const initialValues = createInitialValues(
				installableExtensionsData,
				country,
				industry,
				productTypes
			);
			setInstallableExtensions(
				getVisibleBundles(
					installableExtensionsData,
					country,
					industry,
					productTypes
				)
			);
			setValues( initialValues );
			setIsFetching( false );
		};

		if (
			window.wcAdminFeatures &&
			window.wcAdminFeatures[ 'remote-extensions-list' ] === true &&
			allowMarketplaceSuggestions
		) {
			apiFetch( {
				path: '/wc-admin/onboarding/free-extensions',
			} )
				.then( ( results ) => {
					if ( ! results?.length ) {
						// Assuming empty array or null results is err.
						setLocalInstallableExtensions();
						return;
					}
					const transformedExtensions = transformRemoteExtensions(
						results
					);
					const initialValues = createInitialValues(
						transformedExtensions,
						country,
						industry,
						productTypes
					);
					setInstallableExtensions(
						getVisibleBundles(
							transformedExtensions,
							country,
							industry,
							productTypes
						)
					);
					setValues( initialValues );
					setIsFetching( false );
				} )
				.catch( () => {
					// An error has occurred, default to local config
					setLocalInstallableExtensions();
				} );
		} else {
			// Use local config
			setLocalInstallableExtensions();
		}
	}, [ country, industry, productTypes, allowMarketplaceSuggestions ] );

	const getCheckboxChangeHandler = ( key ) => {
		return ( checked ) => {
			const newState = {
				...values,
				[ key ]: checked,
			};

			const allExtensionsDisabled =
				Object.entries( newState ).filter( ( [ , val ] ) => val )
					.length === 1 && newState.install_extensions;

			if ( allExtensionsDisabled ) {
				// If all the extensions are disabled then disable the "Install Extensions" checkbox too
				setValues( {
					...newState,
					install_extensions: false,
				} );
			} else {
				setValues( {
					...values,
					[ key ]: checked,
					install_extensions: true,
				} );
			}
		};
	};

	return (
		<div className="woocommerce-profile-wizard__business-details__free-features">
			<Card>
				<div className="woocommerce-profile-wizard__business-details__free-features__illustration">
					<AppIllustration />
				</div>
				<div className="woocommerce-admin__business-details__selective-extensions-bundle">
					<div className="woocommerce-admin__business-details__selective-extensions-bundle__extension">
						<CheckboxControl
							checked={ values.install_extensions }
							onChange={ ( checked ) => {
								setValues(
									setAllPropsToValue( values, checked )
								);
							} }
						/>
						<p className="woocommerce-admin__business-details__selective-extensions-bundle__description">
							{ __(
								'Add recommended business features to my site',
								'woocommerce-admin'
							) }
						</p>
						<Button
							className="woocommerce-admin__business-details__selective-extensions-bundle__expand"
							onClick={ () => {
								setShowExtensions( ! showExtensions );

								if ( ! showExtensions ) {
									// only record the accordion click when the accordion is opened.
									recordEvent(
										'storeprofiler_store_business_features_accordion_click'
									);
								}
							} }
						>
							<Icon
								icon={
									showExtensions ? chevronUp : chevronDown
								}
							/>
						</Button>
					</div>
					{ showExtensions &&
						installableExtensions.map(
							( { plugins, title, key: sectionKey } ) => (
								<div key={ sectionKey }>
									<div className="woocommerce-admin__business-details__selective-extensions-bundle__category">
										{ title }
									</div>
									{ isFetching ? (
										<Spinner />
									) : (
										plugins.map(
											( { description, key } ) => (
												<BundleExtensionCheckbox
													key={ key }
													description={ description }
													isChecked={ values[ key ] }
													onChange={ getCheckboxChangeHandler(
														key
													) }
												/>
											)
										)
									) }
								</div>
							)
						) }
				</div>
				<div className="woocommerce-profile-wizard__business-details__free-features__action">
					<Button
						onClick={ () => {
							onSubmit( values );
						} }
						isBusy={ isInstallingActivating }
						disabled={ isInstallingActivating }
						isPrimary
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</div>
			</Card>
			{ renderBusinessExtensionHelpText(
				values,
				isInstallingActivating
			) }
		</div>
	);
};
