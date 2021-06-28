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
} from '@wordpress/components';
import { includes, filter, get } from 'lodash';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { recordEvent } from '@woocommerce/tracks';
import { withDispatch, withSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import ProductTypeLabel from './label';
import './style.scss';

export class ProductTypes extends Component {
	constructor( props ) {
		super();
		const profileItems = get( props, 'profileItems', {} );

		const { productTypes = {} } = getSetting( 'onboarding', {} );
		const defaultProductTypes = Object.keys( productTypes ).filter(
			( key ) => !! productTypes[ key ].default
		);

		this.state = {
			error: null,
			isMonthlyPricing: true,
			selected: profileItems.product_types || defaultProductTypes,
		};

		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
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

	onContinue() {
		if ( ! this.validateField() ) {
			return;
		}

		const { createNotice, goToNextStep, updateProfileItems } = this.props;

		recordEvent( 'storeprofiler_store_product_type_continue', {
			product_type: this.state.selected,
		} );

		updateProfileItems( { product_types: this.state.selected } )
			.then( () => goToNextStep() )
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
		const { productTypes = {} } = getSetting( 'onboarding', {} );
		const { error, isMonthlyPricing, selected } = this.state;
		const { isProfileItemsRequesting } = this.props;

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
							onClick={ this.onContinue }
							isBusy={ isProfileItemsRequesting }
							disabled={
								! selected.length || isProfileItemsRequesting
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
				</div>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getProfileItems,
			getOnboardingError,
			isOnboardingRequesting,
		} = select( ONBOARDING_STORE_NAME );

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			profileItems: getProfileItems(),
			isProfileItemsRequesting: isOnboardingRequesting(
				'updateProfileItems'
			),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( ProductTypes );
