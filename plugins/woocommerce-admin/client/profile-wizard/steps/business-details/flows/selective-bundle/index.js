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
	Spinner,
	Notice,
} from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { Form, TextControl, SelectControl } from '@woocommerce/components';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import classnames from 'classnames';
import { CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { platformOptions } from '../../data/platform-options';
import { employeeOptions } from '../../data/employee-options';
import { sellingVenueOptions } from '../../data/selling-venue-options';
import { getRevenueOptions } from '../../data/revenue-options';
import { getProductCountOptions } from '../../data/product-options';
import {
	SelectiveExtensionsBundle,
	getInstallableExtensions,
} from './selective-extensions-bundle';
import { getPluginSlug, getPluginTrackKey, getTimeFrame } from '~/utils';
import './style.scss';

const BUSINESS_DETAILS_TAB_NAME = 'business-details';
const BUSINESS_FEATURES_TAB_NAME = 'business-features';

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

export const isShowWccomMigrationNotice = ( selectedOption ) =>
	[ 'other-woocommerce', 'other', 'brick-mortar-other' ].includes(
		selectedOption
	);

export const isSellingElsewhere = ( selectedOption ) =>
	[
		'other',
		'brick-mortar',
		'brick-mortar-other',
		'other-woocommerce',
	].includes( selectedOption );

const getWccomMigrationUrl = ( selectedOption ) => {
	return `woocommerce.com/migrate/?utm_source=nux&utm_medium=product&utm_campaign=migrate&utm_content=${ selectedOption }`;
};

export const isSellingOtherPlatformInPerson = ( selectedOption ) =>
	[ 'other', 'brick-mortar-other' ].includes( selectedOption );

export const PERSIST_FREE_FEATURES_DATA_STORAGE_KEY =
	'wc-admin-free-features-tab-values';

class BusinessDetails extends Component {
	constructor( props ) {
		super();

		this.state = {
			isPopoverVisible: false,
			isValid: false,
			currentTab: 'business-details',
			savedValues: props.initialValues,
		};

		this.onContinue = this.onContinue.bind( this );
		this.validate = this.validate.bind( this );
		this.persistValues = this.persistValues.bind( this );
		this.persistProfileItems.bind( this );

		props.trackStepValueChanges(
			props.step.key,
			props.initialValues,
			this.state.savedValues,
			this.persistValues
		);
		// Refetch the free extensions data
		props.invalidateResolutionForStoreSelector( 'getFreeExtensions' );
	}

	async onContinue(
		extensionInstallationOptions,
		installableExtensionsData
	) {
		const { createNotice, goToNextStep, installAndActivatePlugins } =
			this.props;

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
				business_extensions: [
					...businessExtensions,
					...alreadyActivatedExtensions,
				],
			} ),
		];

		if ( businessExtensions.length ) {
			const installationStartTime = window.performance.now();
			promises.push(
				installAndActivatePlugins( businessExtensions )
					.then( ( response ) => {
						const totalInstallationTime =
							window.performance.now() - installationStartTime;
						const installedExtensionsData =
							prepareExtensionTrackingInstallationData(
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
				goToNextStep( { step: BUSINESS_FEATURES_TAB_NAME } );
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem updating your business details',
						'woocommerce'
					)
				);
			} );
	}

	async persistValues() {
		await this.persistProfileItems();

		try {
			window.localStorage.setItem(
				PERSIST_FREE_FEATURES_DATA_STORAGE_KEY,
				JSON.stringify( this.state.savedValues.freeFeaturesTab )
			);
		} catch ( error ) {
			this.props.createNotice(
				'error',
				__(
					'There was a problem saving free features state',
					'woocommerce'
				)
			);
		}
	}

	async persistProfileItems( additions = {} ) {
		const { updateProfileItems, createNotice } = this.props;
		const { businessDetailsTab = {} } = this.state.savedValues;

		const {
			number_employees: numberEmployees,
			other_platform: otherPlatform,
			other_platform_name: otherPlatformName,
			product_count: productCount,
			revenue,
			selling_venues: sellingVenues,
			setup_client: isSetupClient,
		} = businessDetailsTab;

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
					'woocommerce'
				)
			);
		} );
	}

	validate( values ) {
		const errors = {};

		if ( ! values.product_count.length ) {
			errors.product_count = __(
				'This field is required',
				'woocommerce'
			);
		}

		if ( ! values.selling_venues.length ) {
			errors.selling_venues = __(
				'This field is required',
				'woocommerce'
			);
		}

		if (
			! values.other_platform.length &&
			isSellingOtherPlatformInPerson( values.selling_venues )
		) {
			errors.other_platform = __(
				'This field is required',
				'woocommerce'
			);
		}
		if (
			! values.other_platform_name.trim().length &&
			values.other_platform === 'other' &&
			isSellingOtherPlatformInPerson( values.selling_venues )
		) {
			errors.other_platform_name = __(
				'This field is required',
				'woocommerce'
			);
		}

		if (
			! values.number_employees.length &&
			isSellingElsewhere( values.selling_venues )
		) {
			errors.number_employees = __(
				'This field is required',
				'woocommerce'
			);
		}

		if (
			! values.revenue.length &&
			isSellingElsewhere( values.selling_venues )
		) {
			errors.revenue = __( 'This field is required', 'woocommerce' );
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
			wp_version: getSetting( 'wpVersion' ),
		} );
		recordEvent( 'storeprofiler_step_complete', {
			step: BUSINESS_DETAILS_TAB_NAME,
			wc_version: getSetting( 'wcVersion' ),
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
				initialValues={ this.state.savedValues.businessDetailsTab }
				onSubmit={ ( values ) => {
					if ( this.props.hasInstallableExtensions ) {
						this.setState( {
							savedValues: {
								...this.state.savedValues,
								businessDetailsTab: values,
							},
							currentTab: BUSINESS_FEATURES_TAB_NAME,
						} );
					} else {
						goToNextStep( {
							step: BUSINESS_FEATURES_TAB_NAME,
						} );
					}

					this.trackBusinessDetailsStep( values );
					recordEvent( 'storeprofiler_step_view', {
						step: BUSINESS_FEATURES_TAB_NAME,
						wc_version: getSetting( 'wcVersion' ),
					} );
				} }
				onChange={ ( _, values, isValid ) => {
					const savedValues = {
						...this.state.savedValues,
						businessDetailsTab: values,
					};
					this.setState( {
						savedValues,
						isValid,
					} );
					this.props.updateCurrentStepValues(
						this.props.step.key,
						savedValues
					);
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
										'woocommerce'
									) }
								</Text>
								<Text variant="body" as="p">
									{ __(
										"We'd love to know if you are just getting started or you already have a business in place.",
										'woocommerce'
									) }
								</Text>
							</div>
							<Card>
								<CardBody>
									<SelectControl
										excludeSelectedOptions={ false }
										label={ __(
											'How many products do you plan to display?',
											'woocommerce'
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
											'woocommerce'
										) }
										options={ sellingVenueOptions }
										required
										{ ...this.getSelectControlProps(
											getInputProps,
											'selling_venues'
										) }
									/>
									{ isShowWccomMigrationNotice(
										values.selling_venues
									) && (
										<Notice
											className="woocommerce-profile-wizard__wccom-migration-notice"
											status="info"
											isDismissible={ false }
										>
											{ __(
												'Need help moving your store?',
												'woocommerce'
											) }
											&nbsp;
											<Button
												href={ getWccomMigrationUrl(
													values.selling_venues
												) }
												target="_blank"
												rel="noopener noreferrer"
												variant="link"
											>
												{ __(
													'Get free expert advice',
													'woocommerce'
												) }
											</Button>
										</Notice>
									) }
									{ isSellingElsewhere(
										values.selling_venues
									) && (
										<SelectControl
											excludeSelectedOptions={ false }
											label={ __(
												'How many employees do you have?',
												'woocommerce'
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
												'woocommerce'
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
														'woocommerce'
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
															'woocommerce'
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
													'woocommerce'
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
										aria-disabled={ ! isValidForm }
										isBusy={ isInstallingActivating }
									>
										{ ! hasInstallActivateError
											? __( 'Continue', 'woocommerce' )
											: __( 'Retry', 'woocommerce' ) }
									</Button>
									{ hasInstallActivateError && (
										<Button
											onClick={ () => {
												this.persistProfileItems();
												goToNextStep( {
													step: BUSINESS_FEATURES_TAB_NAME,
												} );
											} }
										>
											{ __(
												'Continue without installing',
												'woocommerce'
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
		const { isInstallingActivating } = this.props;
		const {
			savedValues: { freeFeaturesTab },
		} = this.state;

		return (
			<>
				<div className="woocommerce-profile-wizard__step-header">
					<Text
						variant="title.small"
						as="h2"
						size="20"
						lineHeight="28px"
					>
						{ __( 'Included business features', 'woocommerce' ) }
					</Text>
					<Text variant="body" as="p">
						{ __(
							'We recommend enhancing your store with these free extensions',
							'woocommerce'
						) }
					</Text>
					<Text variant="body" as="p">
						{ __(
							'No commitment required - you can remove them at any time.',
							'woocommerce'
						) }
					</Text>
				</div>

				<SelectiveExtensionsBundle
					isInstallingActivating={ isInstallingActivating }
					onSubmit={ this.onContinue }
					installableExtensions={ this.props.installableExtensions }
					installExtensionOptions={
						freeFeaturesTab.installExtensionOptions
					}
					setInstallExtensionOptions={ (
						installExtensionOptions
					) => {
						const savedValues = {
							...this.state.savedValues,
							freeFeaturesTab: {
								...freeFeaturesTab,
								installExtensionOptions,
							},
						};
						this.setState( {
							savedValues,
						} );

						if (
							// Only update current step values when current state's installExtensionOptions is not undefined.
							this.state.savedValues.freeFeaturesTab
								.installExtensionOptions
						) {
							this.props.updateCurrentStepValues(
								this.props.step.key,
								savedValues
							);
						}
					} }
				/>
			</>
		);
	}

	render() {
		const tabs = [];
		if ( ! this.props.hasFinishedGetFreeExtensionsResolution ) {
			return (
				<div className="woocommerce-admin__business-details__spinner">
					<Spinner />
				</div>
			);
		}

		tabs.push( {
			name:
				this.state.currentTab === BUSINESS_DETAILS_TAB_NAME
					? 'current-tab'
					: BUSINESS_DETAILS_TAB_NAME,
			id: BUSINESS_DETAILS_TAB_NAME,
			title: __( 'Business details', 'woocommerce' ),
		} );

		if ( this.props.hasInstallableExtensions ) {
			tabs.push( {
				name:
					this.state.currentTab === BUSINESS_FEATURES_TAB_NAME
						? 'current-tab'
						: BUSINESS_FEATURES_TAB_NAME,
				id: BUSINESS_FEATURES_TAB_NAME,
				title: __( 'Free features', 'woocommerce' ),
				className: this.state.isValid ? '' : 'is-disabled',
			} );
		}

		// There is a hack here to help us manage the selected tab programmatically.
		// We set the tab name "current-tab". when its the one we want selected. This tricks
		// the logic in the TabPanel and allows us to switch which tab has the name "current-tab"
		// and force it to re-render with a different tab selected.
		return (
			<TabPanel
				activeClass="is-active"
				initialTabName="current-tab"
				onSelect={ ( tabName ) => {
					if (
						this.state.currentTab !== tabName &&
						// TabPanel calls onSelect on mount when initialTabName is provided, so we need to check if the tabName is valid.
						tabName !== 'current-tab'
					) {
						this.setState( {
							currentTab: tabName,
							savedValues:
								this.state.savedValues ||
								this.props.initialValues,
						} );
						recordEvent( 'storeprofiler_step_view', {
							step: tabName,
							wc_version: getSetting( 'wcVersion' ),
						} );
					}
				} }
				tabs={ tabs }
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
		const {
			getProfileItems,
			getOnboardingError,
			getFreeExtensions,
			hasFinishedResolution,
		} = select( ONBOARDING_STORE_NAME );
		const { getPluginsError, isPluginsRequesting } =
			select( PLUGINS_STORE_NAME );
		const { general: settings = {} } = getSettings( 'general' );

		const freeExtensions = getFreeExtensions();
		const profileItems = getProfileItems();
		const country = settings.woocommerce_default_country
			? settings.woocommerce_default_country
			: null;

		const installableExtensions = freeExtensions
			? getInstallableExtensions( {
					freeExtensionBundleByCategory: freeExtensions,
					country,
					productTypes: profileItems.product_types || [],
			  } )
			: [];
		const hasInstallableExtensions = installableExtensions.some(
			( { plugins } ) => plugins.length > 0
		);

		return {
			hasInstallActivateError:
				getPluginsError( 'installPlugins' ) ||
				getPluginsError( 'activatePlugins' ),
			hasInstallableExtensions,
			hasFinishedGetFreeExtensionsResolution:
				hasFinishedResolution( 'getFreeExtensions' ),
			installableExtensions,
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			isSettingsError: Boolean( getSettingsError( 'general' ) ),
			isInstallingActivating:
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' ) ||
				isPluginsRequesting( 'getJetpackConnectUrl' ),
			profileItems,
			settings,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems, invalidateResolutionForStoreSelector } =
			dispatch( ONBOARDING_STORE_NAME );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			installAndActivatePlugins,
			updateProfileItems,
			invalidateResolutionForStoreSelector,
		};
	} )
)( BusinessDetails );
