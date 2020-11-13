/**
 * External dependencies
 */
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import {
	Button,
	CheckboxControl,
	FormToggle,
	Popover,
	__experimentalText as Text,
} from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';
import { keys, get, pickBy } from 'lodash';
import {
	H,
	Card,
	Link,
	SelectControl,
	Form,
	TextControl,
} from '@woocommerce/components';
import { formatValue } from '@woocommerce/number';
import { getSetting } from '@woocommerce/wc-admin-settings';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	pluginNames,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getCountryCode, getCurrencyRegion } from '../../dashboard/utils';
import { CurrencyContext } from '../../lib/currency-context';
import { createNoticesFromResponse } from '../../lib/notices';

const wcAdminAssetUrl = getSetting( 'wcAdminAssetUrl', '' );

class BusinessDetails extends Component {
	constructor( props ) {
		super();
		const settings = get( props, 'settings', {} );
		const profileItems = get( props, 'profileItems', {} );
		const industrySlugs = get( profileItems, 'industry', [] ).map(
			( industry ) => industry.slug
		);
		const businessExtensions = get(
			profileItems,
			'business_extensions',
			false
		);

		this.state = {
			isPopoverVisible: false,
		};

		this.initialValues = {
			other_platform: profileItems.other_platform || '',
			other_platform_name: profileItems.other_platform_name || '',
			product_count: profileItems.product_count || '',
			selling_venues: profileItems.selling_venues || '',
			revenue: profileItems.revenue || '',
			'facebook-for-woocommerce': businessExtensions
				? businessExtensions.includes( 'facebook-for-woocommerce' )
				: true,
			'mailchimp-for-woocommerce': businessExtensions
				? businessExtensions.includes( 'mailchimp-for-woocommerce' )
				: true,
			'creative-mail-by-constant-contact': businessExtensions
				? businessExtensions.includes(
						'creative-mail-by-constant-contact'
				  )
				: true,
			'kliken-marketing-for-google': businessExtensions
				? businessExtensions.includes( 'kliken-marketing-for-google' )
				: true,
			install_extensions: true,
		};

		this.extensions = [
			'facebook-for-woocommerce',
			'mailchimp-for-woocommerce',
			'kliken-marketing-for-google',
			'creative-mail-by-constant-contact',
		];

		this.bundleInstall =
			getCountryCode( settings.woocommerce_default_country ) === 'US' &&
			( industrySlugs.includes( 'fashion-apparel-accessories' ) ||
				industrySlugs.includes( 'health-beauty' ) ) &&
			! industrySlugs.includes( 'cbd-other-hemp-derived-products' );
		this.onContinue = this.onContinue.bind( this );
		this.validate = this.validate.bind( this );
		this.getNumberRangeString = this.getNumberRangeString.bind( this );
		this.numberFormat = this.numberFormat.bind( this );
	}

	async onContinue( values ) {
		const {
			createNotice,
			goToNextStep,
			installAndActivatePlugins,
			updateProfileItems,
		} = this.props;
		const {
			install_extensions: installExtensions,
			other_platform: otherPlatform,
			other_platform_name: otherPlatformName,
			product_count: productCount,
			revenue,
			selling_venues: sellingVenues,
		} = values;
		const businessExtensions = this.getBusinessExtensions( values );
		const { getCurrencyConfig } = this.context;

		recordEvent( 'storeprofiler_store_business_details_continue', {
			product_number: productCount,
			already_selling: sellingVenues,
			currency: getCurrencyConfig().code,
			revenue,
			used_platform: otherPlatform,
			used_platform_name: otherPlatformName,
			install_woocommerce_services: businessExtensions.includes(
				'woocommerce-services'
			),
			install_jetpack: businessExtensions.includes( 'jetpack' ),
			install_facebook: businessExtensions.includes(
				'facebook-for-woocommerce'
			),
			install_mailchimp: businessExtensions.includes(
				'mailchimp-for-woocommerce'
			),
			install_creative_mail: businessExtensions.includes(
				'creative-mail-by-constant-contact'
			),
			install_google_ads: businessExtensions.includes(
				'kliken-marketing-for-google'
			),
			install_extensions: installExtensions,
			bundle_install: this.bundleInstall,
		} );

		const _updates = {
			other_platform: otherPlatform,
			other_platform_name:
				otherPlatform === 'other' ? otherPlatformName : '',
			product_count: productCount,
			revenue,
			selling_venues: sellingVenues,
			business_extensions: businessExtensions,
		};

		// Remove possible empty values like `revenue` and `other_platform`.
		const updates = {};
		Object.keys( _updates ).forEach( ( key ) => {
			if ( _updates[ key ] !== '' ) {
				updates[ key ] = _updates[ key ];
			}
		} );

		const promises = [
			updateProfileItems( updates ).catch( () => {
				throw new Error();
			} ),
		];

		if ( businessExtensions.length ) {
			promises.push(
				installAndActivatePlugins( businessExtensions )
					.then( ( response ) => {
						createNoticesFromResponse( response );
					} )
					.catch( ( error ) => {
						createNoticesFromResponse( error );
						throw new Error();
					} )
			);
		}

		Promise.all( promises )
			.then( () => {
				goToNextStep();
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem updating your business details.',
						'woocommerce-admin'
					)
				);
			} );
	}

	validate( values ) {
		const errors = {};

		if ( ! values.product_count.length ) {
			errors.product_count = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		if ( ! values.selling_venues.length ) {
			errors.selling_venues = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		if (
			! values.other_platform.length &&
			[ 'other', 'brick-mortar-other' ].includes( values.selling_venues )
		) {
			errors.other_platform = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		if (
			! values.other_platform_name &&
			values.other_platform === 'other' &&
			[ 'other', 'brick-mortar-other' ].includes( values.selling_venues )
		) {
			errors.other_platform_name = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		if (
			! values.revenue.length &&
			[
				'other',
				'brick-mortar',
				'brick-mortar-other',
				'other-woocommerce',
			].includes( values.selling_venues )
		) {
			errors.revenue = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		return errors;
	}

	getBusinessExtensions( values ) {
		if ( this.bundleInstall ) {
			return values.install_extensions
				? [
						'jetpack',
						'woocommerce-services',
						'woocommerce-payments',
						...this.extensions,
				  ]
				: [];
		}

		if ( values.selling_venues === '' ) {
			return [];
		}

		return keys( pickBy( values ) ).filter( ( name ) =>
			this.extensions.includes( name )
		);
	}

	convertCurrency( value ) {
		const region = getCurrencyRegion(
			this.props.settings.woocommerce_default_country
		);
		if ( region === 'US' ) {
			return value;
		}

		// These are rough exchange rates from USD.  Precision is not paramount.
		// The keys here should match the keys in `getCurrencyData`.
		const exchangeRates = {
			US: 1,
			EU: 0.9,
			IN: 71.24,
			GB: 0.76,
			BR: 4.19,
			VN: 23172.5,
			ID: 14031.0,
			BD: 84.87,
			PK: 154.8,
			RU: 63.74,
			TR: 5.75,
			MX: 19.37,
			CA: 1.32,
		};

		const exchangeRate = exchangeRates[ region ] || exchangeRates.US;
		const digits = exchangeRate.toString().split( '.' )[ 0 ].length;
		const multiplier = Math.pow( 10, 2 + digits );

		return Math.round( ( value * exchangeRate ) / multiplier ) * multiplier;
	}

	numberFormat( value ) {
		const { getCurrencyConfig } = this.context;
		return formatValue( getCurrencyConfig(), 'number', value );
	}

	getNumberRangeString( min, max = false, format = this.numberFormat ) {
		if ( ! max ) {
			return sprintf(
				_x(
					'%s+',
					'store product count or revenue',
					'woocommerce-admin'
				),
				format( min )
			);
		}

		return sprintf(
			_x(
				'%1$s - %2$s',
				'store product count or revenue range',
				'woocommerce-admin'
			),
			format( min ),
			format( max )
		);
	}

	renderBusinessExtensionHelpText( values ) {
		const { isInstallingActivating } = this.props;
		const extensions = this.getBusinessExtensions( values );

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
				<Text variant="caption" as="p">
					{ sprintf(
						_n(
							'Installing the following plugin: %s',
							'Installing the following plugins: %s',
							extensions.length,
							'woocommerce-admin'
						),
						extensionsList
					) }
				</Text>
			);
		}
		const accountRequiredText = this.bundleInstall
			? __(
					'User accounts are required to use these features.',
					'woocommerce-admin'
			  )
			: '';
		return (
			<div className="woocommerce-profile-wizard__footnote">
				<Text variant="caption" as="p">
					{ sprintf(
						_n(
							'The following plugin will be installed for free: %s. %s',
							'The following plugins will be installed for free: %s. %s',
							extensions.length,
							'woocommerce-admin'
						),
						extensionsList,
						accountRequiredText
					) }
				</Text>
				{ this.bundleInstall && (
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
	}

	renderBusinessExtensions( values, getInputProps ) {
		// Show extensions when the currently selling elsewhere checkbox has been answered.
		if ( values.selling_venues === '' ) {
			return null;
		}

		const extensionBenefits = [
			{
				slug: 'facebook-for-woocommerce',
				title: __( 'Market on Facebook', 'woocommerce-admin' ),
				icon: 'onboarding/fb-woocommerce.png',
				description: __(
					'Grow your business by targeting the right people and driving sales with Facebook.',
					'woocommerce-admin'
				),
			},
			{
				slug: 'mailchimp-for-woocommerce',
				title: __(
					'Contact customers with Mailchimp',
					'woocommerce-admin'
				),
				icon: 'onboarding/mailchimp.png',
				description: __(
					'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.',
					'woocommerce-admin'
				),
			},
			{
				slug: 'creative-mail-by-constant-contact',
				title: __(
					'Email marketing for WooCommerce with Creative Mail',
					'woocommerce-admin'
				),
				icon: 'onboarding/creativemail.png',
				description: __(
					'Create on-brand store campaigns, fast email promotions and customer retargeting with Creative Mail.',
					'woocommerce-admin'
				),
			},
			{
				slug: 'kliken-marketing-for-google',
				title: __(
					'Drive traffic to your store with Google Ads & Marketing by Kliken',
					'woocommerce-admin'
				),
				icon: 'onboarding/g-shopping.png',
				description: __(
					'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
					'woocommerce-admin'
				),
			},
		];

		return (
			<Fragment>
				{ extensionBenefits.map( ( benefit ) => (
					<div
						className="woocommerce-profile-wizard__benefit"
						key={ benefit.title }
					>
						<div className="woocommerce-profile-wizard__business-extension">
							<img
								src={ wcAdminAssetUrl + benefit.icon }
								alt=""
							/>
						</div>
						<div className="woocommerce-profile-wizard__benefit-content">
							<H className="woocommerce-profile-wizard__benefit-title">
								{ benefit.title }
							</H>
							<p>{ benefit.description }</p>
						</div>
						<div className="woocommerce-profile-wizard__benefit-toggle">
							<FormToggle
								checked={ values[ benefit.slug ] }
								{ ...getInputProps( benefit.slug ) }
							/>
						</div>
					</div>
				) ) }
			</Fragment>
		);
	}

	renderBusinessExtensionsBundle( values, getInputProps ) {
		const { isPopoverVisible } = this.state;

		return (
			<div className="woocommerce-business-extensions">
				<label htmlFor="woocommerce-business-extensions__checkbox">
					<CheckboxControl
						id="woocommerce-business-extensions__checkbox"
						{ ...getInputProps( 'install_extensions' ) }
					/>
					<span className="woocommerce-business-extensions__label-text">
						{ interpolateComponents( {
							mixedString: __(
								'Install recommended {{strong}}free{{/strong}} business features',
								'woocommerce-admin'
							),
							components: {
								strong: <strong />,
							},
						} ) }
						<span className="woocommerce-business-extensions__label-subtext">
							{ __( 'Requires an account', 'woocommerce-admin' ) }
						</span>
					</span>
				</label>

				<div className="woocommerce-business-extensions__popover-wrapper">
					<Button
						isTertiary
						label={ __(
							'Learn more about recommended free business features',
							'woocommerce-admin'
						) }
						onClick={ () => {
							recordEvent(
								'storeprofiler_store_business_details_popover'
							);
							this.setState( { isPopoverVisible: true } );
						} }
					>
						<i
							className="material-icons-outlined"
							aria-hidden="true"
						>
							info
						</i>
					</Button>
					{ isPopoverVisible && (
						<Popover
							className="woocommerce-business-extensions__popover"
							focusOnMount="container"
							position="top center"
							onClose={ () =>
								this.setState( { isPopoverVisible: false } )
							}
						>
							<div className="woocommerce-business-extensions__benefits">
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Manage your store on the go with the WooCommerce mobile app',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Accept credit cards with WooCommerce Payments',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Speed & security enhancements',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Automatic sales taxes',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Market on Facebook',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Contact customers with Mailchimp',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Drive sales with Google Ads',
										'woocommerce-admin'
									) }
								</div>
								<div className="woocommerce-business-extensions__benefit">
									<i
										className="material-icons-outlined"
										aria-hidden="true"
									>
										check
									</i>
									{ __(
										'Print shipping labels at home',
										'woocommerce-admin'
									) }
								</div>
							</div>
						</Popover>
					) }
				</div>
			</div>
		);
	}

	render() {
		const {
			goToNextStep,
			isInstallingActivating,
			hasInstallActivateError,
		} = this.props;
		const { formatAmount } = this.context;
		const productCountOptions = [
			{
				key: '0',
				label: __(
					"I don't have any products yet.",
					'woocommerce-admin'
				),
			},
			{
				key: '1-10',
				label: this.getNumberRangeString( 1, 10 ),
			},
			{
				key: '11-100',
				label: this.getNumberRangeString( 11, 100 ),
			},
			{
				key: '101-1000',
				label: this.getNumberRangeString( 101, 1000 ),
			},
			{
				key: '1000+',
				label: this.getNumberRangeString( 1000 ),
			},
		];

		const revenueOptions = [
			{
				key: 'none',
				label: sprintf(
					/* translators: %s: $0 revenue amount */
					__( "%s (I'm just getting started)", 'woocommerce-admin' ),
					formatAmount( 0 )
				),
			},
			{
				key: 'up-to-2500',
				label: sprintf(
					/* translators: %s: A given revenue amount, e.g., $2500 */
					__( 'Up to %s', 'woocommerce-admin' ),
					formatAmount( this.convertCurrency( 2500 ) )
				),
			},
			{
				key: '2500-10000',
				label: this.getNumberRangeString(
					this.convertCurrency( 2500 ),
					this.convertCurrency( 10000 ),
					formatAmount
				),
			},
			{
				key: '10000-50000',
				label: this.getNumberRangeString(
					this.convertCurrency( 10000 ),
					this.convertCurrency( 50000 ),
					formatAmount
				),
			},
			{
				key: '50000-250000',
				label: this.getNumberRangeString(
					this.convertCurrency( 50000 ),
					this.convertCurrency( 250000 ),
					formatAmount
				),
			},
			{
				key: 'more-than-250000',
				label: sprintf(
					/* translators: %s: A given revenue amount, e.g., $250000 */
					__( 'More than %s', 'woocommerce-admin' ),
					formatAmount( this.convertCurrency( 250000 ) )
				),
			},
		];

		const sellingVenueOptions = [
			{
				key: 'no',
				label: __( 'No', 'woocommerce-admin' ),
			},
			{
				key: 'other',
				label: __( 'Yes, on another platform', 'woocommerce-admin' ),
			},
			{
				key: 'other-woocommerce',
				label: __(
					'Yes, I own a different store powered by WooCommerce',
					'woocommerce-admin'
				),
			},
			{
				key: 'brick-mortar',
				label: __(
					'Yes, in person at physical stores and/or events',
					'woocommerce-admin'
				),
			},
			{
				key: 'brick-mortar-other',
				label: __(
					'Yes, on another platform and in person at physical stores and/or events',
					'woocommerce-admin'
				),
			},
		];

		const otherPlatformOptions = [
			{
				key: 'shopify',
				label: __( 'Shopify', 'woocommerce-admin' ),
			},
			{
				key: 'bigcommerce',
				label: __( 'BigCommerce', 'woocommerce-admin' ),
			},
			{
				key: 'magento',
				label: __( 'Magento', 'woocommerce-admin' ),
			},
			{
				key: 'wix',
				label: __( 'Wix', 'woocommerce-admin' ),
			},
			{
				key: 'amazon',
				label: __( 'Amazon', 'woocommerce-admin' ),
			},
			{
				key: 'ebay',
				label: __( 'eBay', 'woocommerce-admin' ),
			},
			{
				key: 'etsy',
				label: __( 'Etsy', 'woocommerce-admin' ),
			},
			{
				key: 'squarespace',
				label: __( 'Squarespace', 'woocommerce-admin' ),
			},
			{
				key: 'other',
				label: __( 'Other', 'woocommerce-admin' ),
			},
		];

		return (
			<Form
				initialValues={ this.initialValues }
				onSubmitCallback={ this.onContinue }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit, values, isValidForm } ) => {
					return (
						<Fragment>
							<div className="woocommerce-profile-wizard__step-header">
								<Text variant="title.small" as="h2">
									{ __(
										'Tell us about your business',
										'woocommerce-admin'
									) }
								</Text>
								<Text variant="body">
									{ __(
										"We'd love to know if you are just getting started or you already have a business in place.",
										'woocommerce-admin'
									) }
								</Text>
							</div>
							<Card>
								<Fragment>
									<SelectControl
										label={ __(
											'How many products do you plan to display?',
											'woocommerce-admin'
										) }
										options={ productCountOptions }
										required
										{ ...getInputProps( 'product_count' ) }
									/>

									<SelectControl
										label={ __(
											'Currently selling elsewhere?',
											'woocommerce-admin'
										) }
										options={ sellingVenueOptions }
										required
										{ ...getInputProps( 'selling_venues' ) }
									/>

									{ [
										'other',
										'brick-mortar',
										'brick-mortar-other',
										'other-woocommerce',
									].includes( values.selling_venues ) && (
										<SelectControl
											label={ __(
												"What's your current annual revenue?",
												'woocommerce-admin'
											) }
											options={ revenueOptions }
											required
											{ ...getInputProps( 'revenue' ) }
										/>
									) }

									{ [
										'other',
										'brick-mortar-other',
									].includes( values.selling_venues ) && (
										<Fragment>
											<div className="business-competitors">
												<SelectControl
													label={ __(
														'Which platform is the store using?',
														'woocommerce-admin'
													) }
													options={
														otherPlatformOptions
													}
													required
													{ ...getInputProps(
														'other_platform'
													) }
												/>
												{ values.other_platform ===
													'other' && (
													<TextControl
														label={ __(
															'What is the platform name?',
															'woocommerce-admin'
														) }
														required
														{ ...getInputProps(
															'other_platform_name'
														) }
													/>
												) }
											</div>
										</Fragment>
									) }

									{ this.bundleInstall
										? this.renderBusinessExtensionsBundle(
												values,
												getInputProps
										  )
										: this.renderBusinessExtensions(
												values,
												getInputProps
										  ) }

									<div className="woocommerce-profile-wizard__card-actions">
										<Button
											isPrimary
											onClick={ handleSubmit }
											disabled={ ! isValidForm }
											isBusy={ isInstallingActivating }
										>
											{ ! hasInstallActivateError
												? __(
														'Continue',
														'woocommerce-admin'
												  )
												: __(
														'Retry',
														'woocommerce-admin'
												  ) }
										</Button>
										{ hasInstallActivateError && (
											<Button
												onClick={ () => goToNextStep() }
											>
												{ __(
													'Continue without installing',
													'woocommerce-admin'
												) }
											</Button>
										) }
									</div>
								</Fragment>
							</Card>

							{ this.renderBusinessExtensionHelpText( values ) }
						</Fragment>
					);
				} }
			</Form>
		);
	}
}

BusinessDetails.contextType = CurrencyContext;

export default compose(
	withSelect( ( select ) => {
		const { getSettings, getSettingsError } = select( SETTINGS_STORE_NAME );
		const { getProfileItems, getOnboardingError } = select(
			ONBOARDING_STORE_NAME
		);
		const { getPluginsError, isPluginsRequesting } = select(
			PLUGINS_STORE_NAME
		);
		const { general: settings = {} } = getSettings( 'general' );

		return {
			hasInstallActivateError:
				getPluginsError( 'installPlugins' ) ||
				getPluginsError( 'activatePlugins' ),
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			profileItems: getProfileItems(),
			isSettingsError: Boolean( getSettingsError( 'general' ) ),
			settings,
			isInstallingActivating:
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' ) ||
				isPluginsRequesting( 'getJetpackConnectUrl' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			installAndActivatePlugins,
			updateProfileItems,
		};
	} )
)( BusinessDetails );
