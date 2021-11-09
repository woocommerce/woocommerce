/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	TabPanel,
	__experimentalText as Text,
	FlexItem,
	CheckboxControl,
} from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { SelectControl, Form, TextControl } from '@woocommerce/components';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CurrencyContext } from '~/lib/currency-context';
import { createNoticesFromResponse } from '~/lib/notices';
import { platformOptions } from '../../data/platform-options';
import { sellingVenueOptions } from '../../data/selling-venue-options';
import { getRevenueOptions } from '../../data/revenue-options';
import { getProductCountOptions } from '../../data/product-options';
import { SelectiveExtensionsBundle } from './selective-extensions-bundle';
import './style.scss';

const BUSINESS_DETAILS_TAB_NAME = 'business-details';
const FREE_FEATURES_TAB_NAME = 'free-features';

export const filterBusinessExtensions = ( extensionInstallationOptions ) => {
	return (
		Object.keys( extensionInstallationOptions )
			.filter(
				( key ) =>
					extensionInstallationOptions[ key ] &&
					key !== 'install_extensions'
			)
			.map( ( key ) => {
				// Remove anything after :
				// Please refer to selective-extensions-bundle/index.js
				// installableExtensions variable
				// this is to allow duplicate slugs (Tax & Shipping for example)
				return key.split( ':' )[ 0 ];
			} )
			// remove duplicate
			.filter( ( item, index, arr ) => arr.indexOf( item ) === index )
	);
};

class BusinessDetails extends Component {
	constructor() {
		super();

		this.state = {
			isPopoverVisible: false,
			isValid: false,
			currentTab: 'business-details',
			savedValues: null,
		};

		this.onContinue = this.onContinue.bind( this );
		this.validate = this.validate.bind( this );
	}

	async onContinue( extensionInstallationOptions ) {
		const {
			createNotice,
			goToNextStep,
			installAndActivatePlugins,
		} = this.props;

		const businessExtensions = filterBusinessExtensions(
			extensionInstallationOptions
		);

		recordEvent( 'storeprofiler_store_business_features_continue', {
			all_extensions_installed: Object.values(
				extensionInstallationOptions
			).every( ( val ) => val ),
			install_woocommerce_services:
				extensionInstallationOptions[
					'woocommerce-services:shipping'
				] || extensionInstallationOptions[ 'woocommerce-services:tax' ],
			install_google_listings_and_ads:
				extensionInstallationOptions[ 'google-listings-and-ads' ],
			install_jetpack: extensionInstallationOptions.jetpack,
			install_mailpoet: extensionInstallationOptions.mailpoet,
			install_wcpay:
				extensionInstallationOptions[ 'woocommerce-payments' ],
		} );

		const promises = [
			this.persistProfileItems( {
				business_extensions: businessExtensions,
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
						'There was a problem updating your business details',
						'woocommerce-admin'
					)
				);
			} );
	}

	async persistProfileItems( additions = {} ) {
		const { updateProfileItems, createNotice } = this.props;

		const {
			other_platform: otherPlatform,
			other_platform_name: otherPlatformName,
			product_count: productCount,
			revenue,
			selling_venues: sellingVenues,
			setup_client: isSetupClient,
		} = this.state.savedValues;

		const updates = {
			other_platform: otherPlatform,
			other_platform_name:
				otherPlatform === 'other' ? otherPlatformName : '',
			product_count: productCount,
			revenue,
			selling_venues: sellingVenues,
			setup_client: isSetupClient,
			...additions,
		};

		// Remove possible empty values like `revenue` and `other_platform`.
		const finalUpdates = Object.entries( updates ).reduce(
			( acc, [ key, val ] ) => {
				if ( val !== '' ) {
					return {
						...acc,
						[ key ]: val,
					};
				}

				return acc;
			},
			{}
		);

		return updateProfileItems( finalUpdates ).catch( () => {
			createNotice(
				'error',
				__(
					'There was a problem updating your business details',
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

		if ( Object.keys( errors ).length === 0 ) {
			this.setState( { isValid: true } );
		}

		return errors;
	}

	trackBusinessDetailsStep( {
		other_platform: otherPlatform,
		other_platform_name: otherPlatformName,
		product_count: productCount,
		selling_venues: sellingVenues,
		revenue,
		setup_client: isSetupClient,
	} ) {
		const { getCurrencyConfig } = this.context;

		recordEvent( 'storeprofiler_store_business_details_continue_variant', {
			already_selling: sellingVenues,
			currency: getCurrencyConfig().code,
			product_number: productCount,
			revenue,
			used_platform: otherPlatform,
			used_platform_name: otherPlatformName,
			setup_client: isSetupClient,
		} );
	}

	renderBusinessDetailsStep() {
		const {
			goToNextStep,
			isInstallingActivating,
			hasInstallActivateError,
		} = this.props;

		const { formatAmount, getCurrencyConfig } = this.context;

		const productCountOptions = getProductCountOptions(
			getCurrencyConfig()
		);

		return (
			<Form
				initialValues={
					this.state.savedValues || this.props.initialValues
				}
				onSubmit={ ( values ) => {
					this.setState( {
						savedValues: values,
						currentTab: 'free-features',
					} );

					this.trackBusinessDetailsStep( values );
				} }
				onChange={ ( _, values, isValid ) => {
					this.setState( { savedValues: values, isValid } );
				} }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit, values, isValidForm } ) => {
					return (
						<>
							<div className="woocommerce-profile-wizard__step-header">
								<Text
									variant="title.small"
									as="h2"
									size="20"
									lineHeight="28px"
								>
									{ __(
										'Tell us about your business',
										'woocommerce-admin'
									) }
								</Text>
								<Text variant="body" as="p">
									{ __(
										"We'd love to know if you are just getting started or you already have a business in place.",
										'woocommerce-admin'
									) }
								</Text>
							</div>
							<Card>
								<CardBody>
									<SelectControl
										excludeSelectedOptions={ false }
										label={ __(
											'How many products do you plan to display?',
											'woocommerce-admin'
										) }
										options={ productCountOptions }
										required
										{ ...getInputProps( 'product_count' ) }
									/>

									<SelectControl
										excludeSelectedOptions={ false }
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
											excludeSelectedOptions={ false }
											label={ __(
												"What's your current annual revenue?",
												'woocommerce-admin'
											) }
											options={ getRevenueOptions(
												getCurrencyConfig(),
												this.props.settings
													.woocommerce_default_country,
												formatAmount
											) }
											required
											{ ...getInputProps( 'revenue' ) }
										/>
									) }

									{ [
										'other',
										'brick-mortar-other',
									].includes( values.selling_venues ) && (
										<>
											<div className="business-competitors">
												<SelectControl
													excludeSelectedOptions={
														false
													}
													label={ __(
														'Which platform is the store using?',
														'woocommerce-admin'
													) }
													options={ platformOptions }
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
										</>
									) }
								</CardBody>
								<CardFooter isBorderless>
									<FlexItem>
										<div className="woocommerce-profile-wizard__client">
											<CheckboxControl
												label={ __(
													"I'm setting up a store for a client",
													'woocommerce-admin'
												) }
												{ ...getInputProps(
													'setup_client'
												) }
											/>
										</div>
									</FlexItem>
								</CardFooter>
								<CardFooter justify="center">
									<Button
										isPrimary
										onClick={ async () => {
											await handleSubmit();
											this.persistProfileItems();
										} }
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
											onClick={ () => {
												this.persistProfileItems();
												goToNextStep();
											} }
										>
											{ __(
												'Continue without installing',
												'woocommerce-admin'
											) }
										</Button>
									) }
								</CardFooter>
							</Card>
						</>
					);
				} }
			</Form>
		);
	}

	renderFreeFeaturesStep() {
		const { isInstallingActivating, settings, profileItems } = this.props;
		const country = settings.woocommerce_default_country
			? settings.woocommerce_default_country
			: null;

		return (
			<>
				<div className="woocommerce-profile-wizard__step-header">
					<Text
						variant="title.small"
						as="h2"
						size="20"
						lineHeight="28px"
					>
						{ __(
							'Included business features',
							'woocommerce-admin'
						) }
					</Text>
					<Text variant="body" as="p">
						{ __(
							'We recommend enhancing your store with these free extensions',
							'woocommerce-admin'
						) }
					</Text>
					<Text variant="body" as="p">
						{ __(
							'No commitment required - you can remove them at any time.',
							'woocommerce-admin'
						) }
					</Text>
				</div>

				<SelectiveExtensionsBundle
					isInstallingActivating={ isInstallingActivating }
					onSubmit={ this.onContinue }
					country={ country }
					industry={ profileItems.industry }
					productTypes={ profileItems.product_types }
				/>
			</>
		);
	}

	render() {
		const { initialValues } = this.props;

		// There is a hack here to help us manage the selected tab programatically.
		// We set the tab name "current-tab". when its the one we want selected. This tricks
		// the logic in the TabPanel and allows us to switch which tab has the name "current-tab"
		// and force it to re-render with a different tab selected.
		return (
			<TabPanel
				activeClass="is-active"
				initialTabName="current-tab"
				onSelect={ ( tabName ) => {
					if ( this.state.currentTab !== tabName ) {
						this.setState( {
							currentTab: tabName,
							savedValues:
								this.state.savedValues || initialValues,
						} );
					}
				} }
				tabs={ [
					{
						name:
							this.state.currentTab === BUSINESS_DETAILS_TAB_NAME
								? 'current-tab'
								: BUSINESS_DETAILS_TAB_NAME,
						id: BUSINESS_DETAILS_TAB_NAME,
						title: __( 'Business details', 'woocommerce-admin' ),
					},
					{
						name:
							this.state.currentTab === FREE_FEATURES_TAB_NAME
								? 'current-tab'
								: FREE_FEATURES_TAB_NAME,
						id: FREE_FEATURES_TAB_NAME,
						title: __( 'Free features', 'woocommerce-admin' ),
						className: this.state.isValid ? '' : 'is-disabled',
					},
				] }
			>
				{ ( tab ) => <>{ this.getTab( tab.id ) }</> }
			</TabPanel>
		);
	}

	getTab( tabId ) {
		if ( tabId === BUSINESS_DETAILS_TAB_NAME ) {
			return this.renderBusinessDetailsStep();
		}
		return this.renderFreeFeaturesStep();
	}
}

BusinessDetails.contextType = CurrencyContext;

export const BusinessFeaturesList = compose(
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
