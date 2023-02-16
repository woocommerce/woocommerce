/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component } from '@wordpress/element';
import { Button, Card, CardBody } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { difference, filter } from 'lodash';
import interpolateComponents from '@automattic/interpolate-components';
import { plugins, withDispatch, withSelect } from '@wordpress/data';
import { Link, Stepper, Plugins } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import {
	SETTINGS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	COUNTRIES_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import Connect from '../../../dashboard/components/connect';
import { getCountryCode } from '../../../dashboard/utils';
import StoreLocation from '../steps/location';
import ShippingRates from './rates';
import { getShippingProviders } from './shipping-providers/shipping-providers';
import { createNoticesFromResponse } from '../../../lib/notices';
import './shipping.scss';

export class Shipping extends Component {
	constructor( props ) {
		super( props );

		this.initialState = {
			isPending: false,
			step: 'store_location',
			shippingZones: [],
		};

		// Cache active plugins to prevent removal mid-step.
		this.activePlugins = props.activePlugins;
		this.state = this.initialState;
		this.completeStep = this.completeStep.bind( this );

		this.shippingSmartDefaultsEnabled =
			window.wcAdminFeatures &&
			window.wcAdminFeatures[ 'shipping-smart-defaults' ];

		this.storeLocationCompleted = false;
	}

	componentDidMount() {
		this.reset();
	}

	reset() {
		this.setState( this.initialState );
	}

	async fetchShippingZones() {
		const { countryCode, countryName } = this.props;

		// @todo The following fetches for shipping information should be moved into
		// @woocommerce/data to make these methods and states more readily available.
		const shippingZones = [];
		const zones = await apiFetch( { path: '/wc/v3/shipping/zones' } );
		let hasCountryZone = false;

		await Promise.all(
			zones.map( async ( zone ) => {
				// "Rest of the world zone"
				if ( zone.id === 0 ) {
					zone.methods = await apiFetch( {
						path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
					} );
					zone.name = __( 'Rest of the world', 'woocommerce' );
					zone.toggleable = true;
					shippingZones.push( zone );
					return;
				}

				// Return any zone with a single location matching the country zone.
				zone.locations = await apiFetch( {
					path: `/wc/v3/shipping/zones/${ zone.id }/locations`,
				} );
				const countryLocation = zone.locations.find(
					( location ) => countryCode === location.code
				);
				if ( countryLocation ) {
					zone.methods = await apiFetch( {
						path: `/wc/v3/shipping/zones/${ zone.id }/methods`,
					} );
					shippingZones.push( zone );
					hasCountryZone = true;
				}
			} )
		);

		// Create the default store country zone if it doesn't exist.
		if ( ! hasCountryZone ) {
			const zone = await apiFetch( {
				method: 'POST',
				path: '/wc/v3/shipping/zones',
				data: { name: countryName },
			} );
			zone.locations = await apiFetch( {
				method: 'POST',
				path: `/wc/v3/shipping/zones/${ zone.id }/locations`,
				data: [ { code: countryCode, type: 'country' } ],
			} );
			shippingZones.push( zone );
		}

		shippingZones.reverse();

		this.setState( { isPending: false, shippingZones } );
	}

	componentDidUpdate( prevProps, prevState ) {
		const { countryCode, countryName, settings } = this.props;
		const {
			woocommerce_store_address: storeAddress,
			woocommerce_default_country: defaultCountry,
			woocommerce_store_postcode: storePostCode,
		} = settings;
		const { step } = this.state;

		if (
			step === 'rates' &&
			( prevProps.countryCode !== countryCode ||
				prevProps.countryName !== countryName ||
				prevState.step !== 'rates' )
		) {
			this.setState( { isPending: true } );
			if ( countryName ) {
				this.fetchShippingZones();
			}
		}

		const isCompleteAddress = Boolean(
			storeAddress && defaultCountry && storePostCode
		);

		if ( step === 'store_location' && isCompleteAddress ) {
			if (
				this.shippingSmartDefaultsEnabled &&
				! this.storeLocationCompleted
			) {
				this.completeStep();
				this.storeLocationCompleted = true;
			} else if ( ! this.shippingSmartDefaultsEnabled ) {
				this.completeStep();
			}
		}
	}

	completeStep() {
		const { createNotice, onComplete } = this.props;
		const { step } = this.state;
		const steps = this.getSteps();
		const currentStepIndex = steps.findIndex( ( s ) => s.key === step );
		const nextStep = steps[ currentStepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { step: nextStep.key } );
		} else {
			createNotice(
				'success',
				__(
					"📦 Shipping is done! Don't worry, you can always change it later",
					'woocommerce'
				)
			);
			onComplete();
		}
	}

	getSteps() {
		const {
			countryCode,
			createNotice,
			invalidateResolutionForStoreSelector,
			isJetpackConnected,
			onComplete,
			optimisticallyCompleteTask,
			settings,
			task,
			updateAndPersistSettingsForGroup,
		} = this.props;
		const pluginsToPromote = getShippingProviders( this.props.countryCode );
		const pluginsToActivate = pluginsToPromote.map( ( pluginToPromote ) => {
			return pluginToPromote.slug;
		} );

		// Add jetpack to the list if the list includes woocommerce-services
		if ( pluginsToActivate.includes( 'woocommerce-services' ) ) {
			pluginsToActivate.push( 'jetpack' );
		}

		const onShippingPluginInstalltionSkip = () => {
			recordEvent( 'tasklist_shipping_label_printing', {
				install: false,
				plugins_to_activate: pluginsToActivate,
			} );
			getHistory().push( getNewPath( {}, '/', {} ) );
			onComplete();
		};

		const getSinglePluginDescription = ( name, slug ) => {
			return interpolateComponents( {
				mixedString: sprintf(
					/* translators: %s = plugin name */
					__(
						'Save time and money by printing your shipping labels right from your computer with %1$s. Try %2$s for free. {{link}}Learn more{{/link}}',
						'woocommerce'
					),
					name,
					name
				),
				components: {
					link: (
						<Link
							href={ 'https://wordpress.org/plugins/' + slug }
							target="_blank"
							type="external"
						/>
					),
				},
			} );
		};

		const requiresJetpackConnection =
			! isJetpackConnected && countryCode === 'US';

		let steps = [
			{
				key: 'store_location',
				label: __( 'Set store location', 'woocommerce' ),
				description: __(
					'The address from which your business operates',
					'woocommerce'
				),
				content: (
					<StoreLocation
						createNotice={ createNotice }
						updateAndPersistSettingsForGroup={
							updateAndPersistSettingsForGroup
						}
						settings={ settings }
						onComplete={ ( values ) => {
							const country = getCountryCode(
								values.countryState
							);
							recordEvent( 'tasklist_shipping_set_location', {
								country,
							} );

							// Don't need to trigger completeStep here as it's triggered by the address updates in the componentDidUpdate function.
							if ( this.shippingSmartDefaultsEnabled ) {
								this.completeStep();
							}
						} }
					/>
				),
				visible: true,
			},
			{
				key: 'rates',
				label: __( 'Set shipping costs', 'woocommerce' ),
				description: __(
					'Define how much customers pay to ship to different destinations',
					'woocommerce'
				),
				content: (
					<ShippingRates
						buttonText={
							pluginsToActivate.length ||
							requiresJetpackConnection
								? __( 'Proceed', 'woocommerce' )
								: __( 'Complete task', 'woocommerce' )
						}
						shippingZones={ this.state.shippingZones }
						onComplete={ () => {
							const { id } = task;
							optimisticallyCompleteTask( id );
							invalidateResolutionForStoreSelector();
							this.completeStep();
						} }
						createNotice={ createNotice }
					/>
				),
				visible:
					settings.woocommerce_ship_to_countries === 'disabled'
						? false
						: true,
			},
			{
				key: 'label_printing',
				label: __( 'Enable shipping label printing', 'woocommerce' ),
				description: pluginsToActivate.includes(
					'woocommerce-shipstation-integration'
				)
					? interpolateComponents( {
							mixedString: __(
								'We recommend using ShipStation to save time at the post office by printing your shipping ' +
									'labels at home. Try ShipStation free for 30 days. {{link}}Learn more{{/link}}.',
								'woocommerce'
							),
							components: {
								link: (
									<Link
										href="https://woocommerce.com/products/shipstation-integration?utm_medium=product"
										target="_blank"
										type="external"
									/>
								),
							},
					  } )
					: __(
							'With WooCommerce Shipping you can save time ' +
								'by printing your USPS and DHL Express shipping labels at home',
							'woocommerce'
					  ),
				content: (
					<Plugins
						onComplete={ ( plugins, response ) => {
							createNoticesFromResponse( response );
							recordEvent( 'tasklist_shipping_label_printing', {
								install: true,
								plugins_to_activate: pluginsToActivate,
							} );
							this.completeStep();
						} }
						onError={ ( errors, response ) =>
							createNoticesFromResponse( response )
						}
						onSkip={ () => {
							recordEvent( 'tasklist_shipping_label_printing', {
								install: false,
								plugins_to_activate: pluginsToActivate,
							} );
							getHistory().push( getNewPath( {}, '/', {} ) );
							onComplete();
						} }
						pluginSlugs={ pluginsToActivate }
					/>
				),
				visible: pluginsToActivate.length,
			},

			// Only needed for WooCommerce Shipping
			{
				key: 'connect',
				label: __( 'Connect your store', 'woocommerce' ),
				description: __(
					'Connect your store to WordPress.com to enable label printing',
					'woocommerce'
				),
				content: (
					<Connect
						redirectUrl={ getAdminLink(
							'admin.php?page=wc-admin'
						) }
						completeStep={ this.completeStep }
						onConnect={ () => {
							recordEvent( 'tasklist_shipping_connect_store' );
						} }
					/>
				),
				visible: requiresJetpackConnection,
			},
		];

		// Override the step fields for the smart shipping defaults.
		if ( this.shippingSmartDefaultsEnabled ) {
			const agreementText = pluginsToActivate.includes(
				'woocommerce-services'
			)
				? __(
						'By installing Jetpack and WooCommerce Shipping you agree to the {{link}}Terms of Service{{/link}}.',
						'woocommerce'
				  )
				: __(
						'By installing Jetpack you agree to the {{link}}Terms of Service{{/link}}.',
						'woocommerce'
				  );
			const shippingSmartDefaultsSteps = {
				rates: {
					label: __( 'Review your shipping options', 'woocommerce' ),
					description: __(
						'We recommend the following shipping options based on your location. You can manage your shipping options again at any time in WooCommerce Shipping settings.',
						'woocommerce'
					),
					onClick:
						this.state.step !== 'rates'
							? () => {
									this.setState( { step: 'rates' } );
							  }
							: undefined,
					content: (
						<ShippingRates
							buttonText={ __(
								'Save shipping options',
								'woocommerce'
							) }
							shippingZones={ this.state.shippingZones }
							onComplete={ () => {
								const { id } = task;
								optimisticallyCompleteTask( id );
								invalidateResolutionForStoreSelector();
								this.completeStep();
							} }
							createNotice={ createNotice }
						/>
					),
				},
				label_printing: {
					label: __(
						'Enable shipping label printing and discounted rates',
						'woocommerce'
					),
					description:
						pluginsToPromote.length === 1
							? getSinglePluginDescription(
									pluginsToPromote[ 0 ].name,
									pluginsToPromote[ 0 ].slug
							  )
							: __(
									'Save time and money by printing your shipping labels right from your computer with one of these shipping solutions.',
									'woocommerce'
							  ),

					content: (
						<>
							{ pluginsToPromote.length === 1 ? (
								pluginsToPromote[ 0 ][
									'single-partner-layout'
								]()
							) : (
								<div className="woocommerce-task-shipping-recommendation_plugins-install-container">
									{ pluginsToPromote.map(
										( pluginToPromote ) => {
											const pluginsForPartner = [
												pluginToPromote?.slug,
												pluginToPromote?.dependencies,
											].filter(
												( element ) =>
													element !== undefined
											); // remove undefineds
											// TODO: if pluginsForPartner is empty then we show a CTA with the URL instead
											return pluginToPromote[
												'dual-partner-layout'
											]( {
												children: (
													<div className="woocommerce-task-shipping-recommendations_plugins-buttons">
														<Plugins
															onComplete={ (
																response
															) => {
																createNoticesFromResponse(
																	response
																);
																recordEvent(
																	'tasklist_shipping_label_printing',
																	{
																		install: true,
																		plugins_to_activate:
																			pluginsForPartner,
																	}
																);
																this.completeStep();
															} }
															onError={ (
																errors,
																response
															) =>
																createNoticesFromResponse(
																	response
																)
															}
															installText={ __(
																'Install and enable',
																'woocommerce'
															) }
															learnMore={
																pluginToPromote.url
															}
															onLearnMore={ () => {
																recordEvent(
																	'tasklist_shipping_label_printing_learn_more',
																	{
																		plugin: pluginToPromote.slug,
																	}
																);
															} }
															pluginSlugs={
																pluginsForPartner
															}
															installButtonVariant={
																'secondary'
															}
														/>
													</div>
												),
											} );
										}
									) }
								</div>
							) }
							{ pluginsToPromote.length === 1 ? (
								<Plugins
									onComplete={ ( plugins, response ) => {
										createNoticesFromResponse( response );
										recordEvent(
											'tasklist_shipping_label_printing',
											{
												install: true,
												plugins_to_activate:
													pluginsToActivate,
											}
										);
										this.completeStep();
									} }
									onError={ ( errors, response ) =>
										createNoticesFromResponse( response )
									}
									onSkip={ onShippingPluginInstalltionSkip }
									pluginSlugs={ pluginsToActivate }
								/>
							) : (
								<Button
									isTertiary
									onClick={ onShippingPluginInstalltionSkip }
									className="woocommerce-task-shipping-recommendations_skip-button"
								>
									{ __( 'No Thanks', 'woocommerce' ) }
								</Button>
							) }

							{ ! isJetpackConnected &&
								pluginsToActivate.includes(
									'woocommerce-services'
								) && (
									<Text
										variant="caption"
										className="woocommerce-task__caption"
										size="12"
										lineHeight="16px"
										style={ { display: 'block' } }
									>
										{ interpolateComponents( {
											mixedString: agreementText,
											components: {
												link: (
													<Link
														href={
															'https://wordpress.com/tos/'
														}
														target="_blank"
														type="external"
													>
														<></>
													</Link>
												),
											},
										} ) }
									</Text>
								) }
						</>
					),
				},
				store_location: {
					label: __( 'Set your store location', 'woocommerce' ),
					description: __(
						'Add your store location to help us calculate shipping rates and the best shipping options for you. You can manage your store location again at any time in WooCommerce Settings General.',
						'woocommerce'
					),
					onClick:
						this.state.step !== 'store_location'
							? () => {
									this.setState( { step: 'store_location' } );
							  }
							: undefined,
					buttonText: __( 'Save store location', 'woocommerce' ),
				},
			};

			steps = steps.map( ( step ) => {
				if ( shippingSmartDefaultsSteps.hasOwnProperty( step.key ) ) {
					step = {
						...step,
						...shippingSmartDefaultsSteps[ step.key ],
					};
				}
				// Empty description field if it's not the current step.
				if ( step.key !== this.state.step ) {
					step.description = '';
				}
				return step;
			} );
		}
		return filter( steps, ( step ) => step.visible );
	}

	render() {
		const { isPending, step } = this.state;
		const { isUpdateSettingsRequesting } = this.props;
		const steps = this.getSteps();

		return (
			<div className="woocommerce-task-shipping">
				<Card className="woocommerce-task-card">
					<CardBody>
						<Stepper
							isPending={
								isPending || isUpdateSettingsRequesting
							}
							isVertical
							currentStep={ step }
							steps={ steps }
						/>
					</CardBody>
				</Card>
			</div>
		);
	}
}

const ShippingWrapper = compose(
	withSelect( ( select ) => {
		const { getSettings, isUpdateSettingsRequesting } =
			select( SETTINGS_STORE_NAME );
		const { getActivePlugins, isJetpackConnected } =
			select( PLUGINS_STORE_NAME );
		const { getCountry } = select( COUNTRIES_STORE_NAME );

		const { general: settings = {} } = getSettings( 'general' );
		const countryCode = getCountryCode(
			settings.woocommerce_default_country
		);

		const country = countryCode ? getCountry( countryCode ) : null;
		const countryName = country ? country.name : null;
		const activePlugins = getActivePlugins();

		return {
			countryCode,
			countryName,
			isUpdateSettingsRequesting: isUpdateSettingsRequesting( 'general' ),
			settings,
			activePlugins,
			isJetpackConnected: isJetpackConnected(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateAndPersistSettingsForGroup } =
			dispatch( SETTINGS_STORE_NAME );
		const {
			invalidateResolutionForStoreSelector,
			optimisticallyCompleteTask,
		} = dispatch( ONBOARDING_STORE_NAME );

		return {
			createNotice,
			invalidateResolutionForStoreSelector,
			optimisticallyCompleteTask,
			updateAndPersistSettingsForGroup,
		};
	} )
)( Shipping );

registerPlugin( 'wc-admin-onboarding-task-shipping', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="shipping">
			{ ( { onComplete, task } ) => {
				return (
					<ShippingWrapper onComplete={ onComplete } task={ task } />
				);
			} }
		</WooOnboardingTask>
	),
} );
