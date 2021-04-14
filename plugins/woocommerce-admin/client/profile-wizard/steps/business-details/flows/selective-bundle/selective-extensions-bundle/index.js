/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import {
	Button,
	Card,
	CheckboxControl,
	__experimentalText as Text,
} from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import interpolateComponents from 'interpolate-components';
import { pluginNames } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { AppIllustration } from '../app-illustration';
import './style.scss';
import { setAllPropsToValue } from '~/lib/collections';
import { getCountryCode } from '~/dashboard/utils';
import { isWCPaySupported } from '~/task-list/tasks/payments/methods/wcpay';

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

const installableExtensions = [
	{
		title: __( 'Get the basics', 'woocommerce-admin' ),
		plugins: [
			{
				slug: 'woocommerce-payments',
				description: generatePluginDescriptionWithLink(
					__(
						'Accept credit cards with {{link}}WooCommerce Payments{{/link}}',
						'woocommerce-admin'
					),
					'woocommerce-payments'
				),
				isVisible: ( countryCode, industry ) => {
					const hasCbdIndustry = ( industry || [] ).some(
						( { slug } ) => {
							return slug === 'cbd-other-hemp-derived-products';
						}
					);
					return isWCPaySupported( countryCode ) && ! hasCbdIndustry;
				},
			},
			{
				slug: 'woocommerce-services:shipping',
				description: generatePluginDescriptionWithLink(
					__(
						'Print shipping labels with {{link}}WooCommerce Shipping{{/link}}',
						'woocommerce-admin'
					),
					'shipping'
				),
				isVisible: ( countryCode, industry, productTypes ) => {
					return (
						countryCode === 'US' ||
						( countryCode === 'US' &&
							productTypes.length === 1 &&
							productTypes[ 0 ] === 'downloads' )
					);
				},
			},
			{
				slug: 'woocommerce-services:tax',
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
				slug: 'jetpack',
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
		title: 'Grow your store',
		plugins: [
			{
				slug: 'mailpoet',
				description: generatePluginDescriptionWithLink(
					__(
						'Level up your email marketing with {{link}}Mailpoet{{/link}}',
						'woocommerce-admin'
					),
					'mailpoet',
					'https://wordpress.org/plugins/mailpoet/'
				),
			},
			{
				slug: 'facebook-for-woocommerce',
				description: generatePluginDescriptionWithLink(
					__(
						'Market on {{link}}Facebook{{/link}}',
						'woocommerce-admin'
					),
					'facebook'
				),
			},
			{
				slug: 'kliken-marketing-for-google',
				description: generatePluginDescriptionWithLink(
					__(
						'Drive sales with {{link}}Google Ads{{/link}}',
						'woocommerce-admin'
					),
					'google-ads-and-marketing'
				),
			},
			{
				slug: 'mailchimp-for-woocommerce',
				description: generatePluginDescriptionWithLink(
					__(
						'Contact customers with {{link}}Mailchimp{{/link}}',
						'woocommerce-admin'
					),
					'mailchimp-for-woocommerce'
				),
			},
			{
				slug: 'creative-mail-by-constant-contact',
				description: generatePluginDescriptionWithLink(
					__(
						'Emails made easy with {{link}}Creative Mail{{/link}}',
						'woocommerce-admin'
					),
					'creative-mail-for-woocommerce'
				),
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
		.map( ( extension ) => {
			return pluginNames[ extension ];
		} )
		.join( ', ' );

	if ( isInstallingActivating ) {
		return (
			<div className="woocommerce-profile-wizard__footnote">
				<Text variant="caption" as="p">
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
			<Text variant="caption" as="p">
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
				<Text variant="caption" as="p">
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
 */
const getVisiblePlugins = ( plugins, country, industry, productTypes ) => {
	const countryCode = getCountryCode( country );

	return plugins.filter(
		( plugin ) =>
			! plugin.isVisible ||
			plugin.isVisible( countryCode, industry, productTypes )
	);
};

export const SelectiveExtensionsBundle = ( {
	isInstallingActivating,
	onSubmit,
	country,
	industry,
	productTypes,
} ) => {
	const [ showExtensions, setShowExtensions ] = useState( false );
	const [ values, setValues ] = useState( {} );

	useEffect( () => {
		const initialValues = installableExtensions.reduce(
			( acc, curr ) => {
				const plugins = getVisiblePlugins(
					curr.plugins,
					country,
					industry,
					productTypes
				).reduce( ( pluginAcc, { slug } ) => {
					return { ...pluginAcc, [ slug ]: true };
				}, {} );

				return {
					...acc,
					...plugins,
				};
			},
			{ install_extensions: true }
		);
		setValues( initialValues );
	}, [ country ] );

	const getCheckboxChangeHandler = ( slug ) => {
		return ( checked ) => {
			const newState = {
				...values,
				[ slug ]: checked,
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
					[ slug ]: checked,
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
								'Add recommended business features to my site'
							) }
						</p>
						<Icon
							className="woocommerce-admin__business-details__selective-extensions-bundle__expand"
							icon={ showExtensions ? chevronUp : chevronDown }
							onClick={ () => {
								setShowExtensions( ! showExtensions );

								if ( ! showExtensions ) {
									// only record the accordion click when the accordion is opened.
									recordEvent(
										'storeprofiler_store_business_features_accordion_click'
									);
								}
							} }
						/>
					</div>
					{ showExtensions &&
						installableExtensions.map( ( { plugins, title } ) => (
							<div key={ title }>
								<div className="woocommerce-admin__business-details__selective-extensions-bundle__category">
									{ title }
								</div>
								{ getVisiblePlugins(
									plugins,
									country,
									industry,
									productTypes
								).map( ( { description, slug } ) => (
									<BundleExtensionCheckbox
										key={ slug }
										description={ description }
										isChecked={ values[ slug ] }
										onChange={ getCheckboxChangeHandler(
											slug
										) }
									/>
								) ) }
							</div>
						) ) }
				</div>
				<div className="woocommerce-profile-wizard__business-details__free-features__action">
					<Button
						onClick={ () => {
							onSubmit( values );
						} }
						isBusy={ isInstallingActivating }
						isPrimary
					>
						Continue
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
