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
	CheckboxControl,
	FormToggle,
	Spinner,
} from '@wordpress/components';
import { includes, filter } from 'lodash';
import { recordEvent } from '@woocommerce/tracks';
import { withDispatch, withSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { getCountryCode } from '../../../dashboard/utils';
import ProductTypeLabel from './label';
import './style.scss';

export class ProductTypes extends Component {
	constructor() {
		super();

		this.state = {
			error: null,
			isMonthlyPricing: true,
			selected: [],
			isWCPayInstalled: null,
		};

		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	componentDidMount() {
		const { installedPlugins, invalidateResolution } = this.props;
		const { isWCPayInstalled } = this.state;

		invalidateResolution( 'getProductTypes', [] );

		if ( isWCPayInstalled === null && installedPlugins ) {
			this.setState( {
				isWCPayInstalled: installedPlugins.includes(
					'woocommerce-payments'
				),
			} );
		}
	}

	componentDidUpdate( prevProps, prevState ) {
		const { profileItems, productTypes } = this.props;

		if ( this.state.selected !== prevState.selected ) {
			this.props.updateCurrentStepValues(
				this.props.step.key,
				this.state.selected
			);
		}

		if ( prevProps.productTypes !== productTypes ) {
			const defaultProductTypes = Object.keys( productTypes ).filter(
				( key ) => !! productTypes[ key ].default
			);
			this.setState(
				{
					selected: profileItems.product_types || defaultProductTypes,
				},
				() => {
					this.props.trackStepValueChanges(
						this.props.step.key,
						[ ...this.state.selected ],
						this.state.selected,
						this.onContinue
					);
				}
			);
		}
	}

	validateField() {
		const error = this.state.selected.length
			? null
			: __(
					'Please select at least one product type',
					'woocommerce-admin'
			  );
		this.setState( { error } );
		return ! error;
	}

	onContinue( onSuccess ) {
		const { selected } = this.state;
		const { installedPlugins = [] } = this.props;

		if ( ! this.validateField() ) {
			return;
		}

		const {
			countryCode,
			createNotice,
			installAndActivatePlugins,
			updateProfileItems,
		} = this.props;

		const eventProps = {
			product_type: selected,
			wcpay_installed: false,
		};

		const promises = [ updateProfileItems( { product_types: selected } ) ];

		if (
			window.wcAdminFeatures &&
			window.wcAdminFeatures.subscriptions &&
			countryCode === 'US' &&
			! installedPlugins.includes( 'woocommerce-payments' ) &&
			selected.includes( 'subscriptions' )
		) {
			promises.push(
				installAndActivatePlugins( [ 'woocommerce-payments' ] )
					.then( ( response ) => {
						eventProps.wcpay_installed = true;
						if (
							response.data &&
							response.data.install_time &&
							response.data.install_time[ 'woocommerce-payments' ]
						) {
							eventProps.install_time_wcpay =
								response.data.install_time[
									'woocommerce-payments'
								];
						}
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
				recordEvent(
					'storeprofiler_store_product_type_continue',
					eventProps
				);
				if ( typeof onSuccess === 'function' ) {
					onSuccess();
				}
			} )
			.catch( () =>
				createNotice(
					'error',
					__(
						'There was a problem updating your product types',
						'woocommerce-admin'
					)
				)
			);
	}

	onChange( slug ) {
		this.setState(
			( state ) => {
				if ( includes( state.selected, slug ) ) {
					return {
						selected:
							filter( state.selected, ( value ) => {
								return value !== slug;
							} ) || [],
					};
				}
				const newSelected = state.selected;
				newSelected.push( slug );
				return {
					selected: newSelected,
				};
			},
			() => this.validateField()
		);
	}

	render() {
		const { productTypes = [] } = this.props;
		const {
			error,
			isMonthlyPricing,
			isWCPayInstalled,
			selected,
		} = this.state;
		const {
			countryCode,
			isInstallingActivating,
			isProductTypesRequesting,
			isProfileItemsRequesting,
		} = this.props;

		if ( isProductTypesRequesting ) {
			return (
				<div className="woocommerce-profile-wizard__product-types__spinner">
					<Spinner />
				</div>
			);
		}

		return (
			<div className="woocommerce-profile-wizard__product-types">
				<div className="woocommerce-profile-wizard__step-header">
					<Text
						variant="title.small"
						as="h2"
						size="20"
						lineHeight="28px"
					>
						{ __(
							'What type of products will be listed?',
							'woocommerce-admin'
						) }
					</Text>
					<Text variant="body" as="p">
						{ __( 'Choose any that apply', 'woocommerce-admin' ) }
					</Text>
				</div>

				<Card>
					<CardBody size={ null }>
						{ Object.keys( productTypes ).map( ( slug ) => {
							return (
								<CheckboxControl
									key={ slug }
									label={
										<ProductTypeLabel
											description={
												productTypes[ slug ].description
											}
											label={ productTypes[ slug ].label }
											annualPrice={
												productTypes[ slug ]
													.yearly_price
											}
											isMonthlyPricing={
												isMonthlyPricing
											}
											moreUrl={
												productTypes[ slug ].more_url
											}
											slug={ slug }
										/>
									}
									onChange={ () => this.onChange( slug ) }
									checked={ selected.includes( slug ) }
									className="woocommerce-profile-wizard__checkbox"
								/>
							);
						} ) }
						{ error && (
							<span className="woocommerce-profile-wizard__error">
								{ error }
							</span>
						) }
					</CardBody>
					<CardFooter isBorderless justify="center">
						<Button
							isPrimary
							onClick={ () => {
								this.onContinue( this.props.goToNextStep );
							} }
							isBusy={
								isProfileItemsRequesting ||
								isInstallingActivating
							}
							disabled={
								! selected.length ||
								isProfileItemsRequesting ||
								isInstallingActivating
							}
						>
							{ __( 'Continue', 'woocommerce-admin' ) }
						</Button>
					</CardFooter>
				</Card>
				<div className="woocommerce-profile-wizard__card-help-footnote">
					<div className="woocommerce-profile-wizard__product-types-pricing-toggle woocommerce-profile-wizard__checkbox">
						<label htmlFor="woocommerce-product-types__pricing-toggle">
							<Text variant="body" as="p">
								{ __(
									'Display monthly prices',
									'woocommerce-admin'
								) }
							</Text>
							<FormToggle
								id="woocommerce-product-types__pricing-toggle"
								checked={ isMonthlyPricing }
								onChange={ () =>
									this.setState( {
										isMonthlyPricing: ! isMonthlyPricing,
									} )
								}
							/>
						</label>
					</div>
					<Text variant="caption" size="12" lineHeight="16px">
						{ __(
							'Billing is annual. All purchases are covered by our 30 day money back guarantee and include access to support and updates. Extensions will be added to a cart for you to purchase later.',
							'woocommerce-admin'
						) }
					</Text>
					{ window.wcAdminFeatures &&
						window.wcAdminFeatures.subscriptions &&
						countryCode === 'US' &&
						! isWCPayInstalled &&
						selected.includes( 'subscriptions' ) && (
							<Text
								variant="body"
								size="12"
								lineHeight="16px"
								as="p"
							>
								{ __(
									'The following extensions will be added to your site for free: WooCommerce Payments. An account is required to use this feature.',
									'woocommerce-admin'
								) }
							</Text>
						) }
				</div>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getProfileItems,
			getProductTypes,
			getOnboardingError,
			hasFinishedResolution,
			isOnboardingRequesting,
		} = select( ONBOARDING_STORE_NAME );
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { getInstalledPlugins, isPluginsRequesting } = select(
			PLUGINS_STORE_NAME
		);
		const { general: settings = {} } = getSettings( 'general' );

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			profileItems: getProfileItems(),
			isProfileItemsRequesting: isOnboardingRequesting(
				'updateProfileItems'
			),
			installedPlugins: getInstalledPlugins(),
			isInstallingActivating:
				isPluginsRequesting( 'installPlugins' ) ||
				isPluginsRequesting( 'activatePlugins' ),
			countryCode: getCountryCode( settings.woocommerce_default_country ),
			productTypes: getProductTypes(),
			isProductTypesRequesting: ! hasFinishedResolution(
				'getProductTypes'
			),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );
		const { installAndActivatePlugins } = dispatch( PLUGINS_STORE_NAME );
		const { invalidateResolution } = dispatch( ONBOARDING_STORE_NAME );

		return {
			createNotice,
			installAndActivatePlugins,
			invalidateResolution,
			updateProfileItems,
		};
	} )
)( ProductTypes );
