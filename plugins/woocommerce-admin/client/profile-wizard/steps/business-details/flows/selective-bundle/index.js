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
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { CurrencyContext } from '~/lib/currency-context';
import { createNoticesFromResponse } from '~/lib/notices';
import { platformOptions } from '../../data/platform-options';
import { employeeOptions } from '../../data/employee-options';
import { sellingVenueOptions } from '../../data/selling-venue-options';
import { getRevenueOptions } from '../../data/revenue-options';
import { getProductCountOptions } from '../../data/product-options';
import { SelectiveExtensionsBundle } from './selective-extensions-bundle';
import { getPluginSlug, getPluginTrackKey } from '~/utils';
import './style.scss';

const BUSINESS_DETAILS_TAB_NAME = 'business-details';
const FREE_FEATURES_TAB_NAME = 'free-features';

export const filterBusinessExtensions = (
	extensionInstallationOptions,
	alreadyActivatedExtensions = []
) => {
	return (
		Object.keys( extensionInstallationOptions )
			.filter(
				( key ) =>
					extensionInstallationOptions[ key ] &&
					key !== 'install_extensions' &&
					! alreadyActivatedExtensions.includes( key )
			)
			.map( getPluginSlug )
			// remove duplicate
			.filter( ( item, index, arr ) => arr.indexOf( item ) === index )
	);
};

const timeFrames = [
	{ name: '0-2s', max: 2 },
	{ name: '2-5s', max: 5 },
	{ name: '5-10s', max: 10 },
	{ name: '10-15s', max: 15 },
	{ name: '15-20s', max: 20 },
	{ name: '20-30s', max: 30 },
	{ name: '30-60s', max: 60 },
	{ name: '>60s' },
];
function getTimeFrame( timeInMs ) {
	for ( const timeFrame of timeFrames ) {
		if ( ! timeFrame.max ) {
			return timeFrame.name;
		}
		if ( timeInMs < timeFrame.max * 1000 ) {
			return timeFrame.name;
		}
	}
}

export const prepareExtensionTrackingData = (
	extensionInstallationOptions
) => {
	const installedExtensions = {};
	for ( let [ fieldKey, value ] of Object.entries(
		extensionInstallationOptions
	) ) {
		fieldKey = getPluginSlug( fieldKey );
		const key = getPluginTrackKey( fieldKey );
		if (
			fieldKey !== 'install_extensions' &&
			! installedExtensions[ `install_${ key }` ]
		) {
			installedExtensions[ `install_${ key }` ] = value;
		}
	}
	return installedExtensions;
};

export const prepareExtensionTrackingInstallationData = (
	extensionInstallationOptions,
	installationData
) => {
	const installed = [];
	const data = {};
	for ( let [ fieldKey ] of Object.entries( extensionInstallationOptions ) ) {
		fieldKey = getPluginSlug( fieldKey );
		const key = getPluginTrackKey( fieldKey );
		if (
			installationData &&
			installationData.data &&
			installationData.data.install_time &&
			installationData.data.install_time[ fieldKey ]
		) {
			installed.push( key );
			data[ `install_time_${ key }` ] = getTimeFrame(
				installationData.data.install_time[ fieldKey ]
			);
		}
	}
	data.installed_extensions = installed;
	data.activated_extensions =
		installationData &&
		installationData.data &&
		installationData.data.activated
			? installationData.data.activated
			: [];
	return data;
};

export const isSellingElsewhere = ( selectedOption ) =>
	[
		'other',
		'brick-mortar',
		'brick-mortar-other',
		'other-woocommerce',
	].includes( selectedOption );

export const isSellingOtherPlatformInPerson = ( selectedOption ) =>
	[ 'other', 'brick-mortar-other' ].includes( selectedOption );

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

	async onContinue(
		extensionInstallationOptions,
		installableExtensionsData
	) {
		const {
			createNotice,
			goToNextStep,
			installAndActivatePlugins,
		} = this.props;

		const alreadyActivatedExtensions = installableExtensionsData.reduce(
			( actExtensions, bundle ) => {
				const activated = bundle.plugins
					.filter( ( plugin ) => plugin.is_activated )
					.map( ( plugin ) => plugin.key );
				return [ ...actExtensions, ...activated ];
			},
			[]
		);

		const businessExtensions = filterBusinessExtensions(
			extensionInstallationOptions,
			alreadyActivatedExtensions
		);

		const installedExtensions = prepareExtensionTrackingData(
			extensionInstallationOptions
		);

		recordEvent( 'storeprofiler_store_business_features_continue', {
			all_extensions_installed: Object.values(
				extensionInstallationOptions
			).every( ( val ) => val ),
			...installedExtensions,
		} );

		const promises = [
			this.persistProfileItems( {
				business_extensions: businessExtensions,
			} ),
		];

		if ( businessExtensions.length ) {
			const installationStartTime = window.performance.now();
			promises.push(
				installAndActivatePlugins( businessExtensions )
					.then( ( response ) => {
						const totalInstallationTime =
							window.performance.now() - installationStartTime;
						const installedExtensionsData = prepareExtensionTrackingInstallationData(
							extensionInstallationOptions,
							response
						);

						recordEvent(
							'storeprofiler_store_business_features_installed_and_activated',
							{
								success: true,
								total_time: getTimeFrame(
									totalInstallationTime
								),
								...installedExtensionsData,
							}
						);
						createNoticesFromResponse( response );
					} )
					.catch( ( error ) => {
						recordEvent(
							'storeprofiler_store_business_features_installed_and_activated',
							{
								success: false,
								failed_extensions: Object.keys(
									error.data || {}
								).map( ( key ) => getPluginTrackKey( key ) ),
							}
						);
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
			number_employees: numberEmployees,
			other_platform: otherPlatform,
			other_platform_name: otherPlatformName,
			product_count: productCount,
			revenue,
			selling_venues: sellingVenues,
			setup_client: isSetupClient,
		} = this.state.savedValues;

		const updates = {
			number_employees: numberEmployees,
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
					acc[ key ] = val;
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
			isSellingOtherPlatformInPerson( values.selling_venues )
		) {
			errors.other_platform = __(
				'This field is required',
				'woocommerce-admin'
			);
		}
		if (
			! values.other_platform_name.trim().length &&
			values.other_platform === 'other' &&
			isSellingOtherPlatformInPerson( values.selling_venues )
		) {
			errors.other_platform_name = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		if (
			! values.number_employees.length &&
			isSellingElsewhere( values.selling_venues )
		) {
			errors.number_employees = __(
				'This field is required',
				'woocommerce-admin'
			);
		}

		if (
			! values.revenue.length &&
			isSellingElsewhere( values.selling_venues )
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
		number_employees: numberEmployees,
		other_platform: otherPlatform,
		other_platform_name: otherPlatformName,
		product_count: productCount,
		selling_venues: sellingVenues,
		revenue,
		setup_client: isSetupClient,
	} ) {
		const { getCurrencyConfig } = this.context;

		recordEvent( 'storeprofiler_store_business_details_continue_variant', {
			number_employees: numberEmployees,
			already_selling: sellingVenues,
			currency: getCurrencyConfig().code,
			product_number: productCount,
			revenue,
			used_platform: otherPlatform,
			used_platform_name: otherPlatformName,
			setup_client: isSetupClient,
		} );
	}

	getSelectControlProps( getInputProps, name = '' ) {
		const { className, ...props } = getInputProps( name );
		return {
			...props,
			className: classnames(
				`woocommerce-profile-wizard__${ name.replace( /\_/g, '-' ) }`,
				className
			),
		};
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
										{ ...this.getSelectControlProps(
											getInputProps,
											'product_count'
										) }
									/>

									<SelectControl
										excludeSelectedOptions={ false }
										label={ __(
											'Currently selling elsewhere?',
											'woocommerce-admin'
										) }
										options={ sellingVenueOptions }
										required
										{ ...this.getSelectControlProps(
											getInputProps,
											'selling_venues'
										) }
									/>

									{ isSellingElsewhere(
										values.selling_venues
									) && (
										<SelectControl
											excludeSelectedOptions={ false }
											label={ __(
												'How many employees do you have?',
												'woocommerce-admin'
											) }
											options={ employeeOptions }
											required
											{ ...this.getSelectControlProps(
												getInputProps,
												'number_employees'
											) }
										/>
									) }

									{ isSellingElsewhere(
										values.selling_venues
									) && (
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
											{ ...this.getSelectControlProps(
												getInputProps,
												'revenue'
											) }
										/>
									) }

									{ isSellingOtherPlatformInPerson(
										values.selling_venues
									) && (
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
													{ ...this.getSelectControlProps(
														getInputProps,
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
														{ ...this.getSelectControlProps(
															getInputProps,
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
