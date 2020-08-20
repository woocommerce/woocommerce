/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Button, CheckboxControl, Tooltip } from '@wordpress/components';
import { includes, filter, get } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { H, Card, Link, Pill } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './product-types.scss';

function getLabel( description, yearlyPrice ) {
	if ( ! yearlyPrice ) {
		return description;
	}

	const monthlyPrice = ( yearlyPrice / 12.0 ).toFixed( 2 );
	const priceDescription = sprintf(
		__( '$%f per month, billed annually', 'woocommerce-admin' ),
		monthlyPrice
	);
	/* eslint-disable @wordpress/i18n-no-collapsible-whitespace */
	const toolTipText = __(
		"This product type requires a paid extension.\nWe'll add this to a cart so that\nyou can purchase and install it later.",
		'woocommerce-admin'
	);
	/* eslint-enable @wordpress/i18n-no-collapsible-whitespace */

	return (
		<Fragment>
			<span className="woocommerce-product-wizard__product-types__label">
				{ description }
			</span>
			<Tooltip text={ toolTipText } position="bottom center">
				<span>
					<Pill>
						<span className="screen-reader-text">
							{ toolTipText }
						</span>
						{ priceDescription }
					</Pill>
				</span>
			</Tooltip>
		</Fragment>
	);
}

class ProductTypes extends Component {
	constructor( props ) {
		super();
		const profileItems = get( props, 'profileItems', {} );

		const { productTypes = {} } = getSetting( 'onboarding', {} );
		const defaultProductTypes = Object.keys( productTypes ).filter(
			( key ) => !! productTypes[ key ].default
		);

		this.state = {
			error: null,
			selected: profileItems.product_types || defaultProductTypes,
		};

		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	async validateField() {
		const error = this.state.selected.length
			? null
			: __(
					'Please select at least one product type',
					'woocommerce-admin'
			  );
		this.setState( { error } );
	}

	async onContinue() {
		await this.validateField();
		if ( this.state.error ) {
			return;
		}

		const {
			createNotice,
			goToNextStep,
			isError,
			updateProfileItems,
		} = this.props;

		recordEvent( 'storeprofiler_store_product_type_continue', {
			product_type: this.state.selected,
		} );
		await updateProfileItems( { product_types: this.state.selected } );

		if ( ! isError ) {
			goToNextStep();
		} else {
			createNotice(
				'error',
				__(
					'There was a problem updating your product types.',
					'woocommerce-admin'
				)
			);
		}
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

	onLearnMore( slug ) {
		recordEvent( 'storeprofiler_store_product_type_learn_more', {
			product_type: slug,
		} );
	}

	render() {
		const { productTypes = {} } = getSetting( 'onboarding', {} );
		const { error, selected } = this.state;

		return (
			<div className="woocommerce-profile-wizard__product-types">
				<H className="woocommerce-profile-wizard__header-title">
					{ __(
						'What type of products will be listed?',
						'woocommerce-admin'
					) }
				</H>
				<H className="woocommerce-profile-wizard__header-subtitle">
					{ __( 'Choose any that apply' ) }
				</H>

				<Card>
					<div className="woocommerce-profile-wizard__checkbox-group">
						{ Object.keys( productTypes ).map( ( slug ) => {
							const label = getLabel(
								productTypes[ slug ].label,
								productTypes[ slug ].yearly_price
							);
							const moreUrl = productTypes[ slug ].more_url;
							const helpText =
								productTypes[ slug ].description &&
								interpolateComponents( {
									mixedString:
										productTypes[ slug ].description +
										( productTypes[ slug ].more_url
											? ' {{moreLink/}}'
											: '' ),
									components: {
										moreLink: moreUrl ? (
											<Link
												href={ moreUrl }
												target="_blank"
												type="external"
												onClick={ () =>
													this.onLearnMore( slug )
												}
											>
												{ __(
													'Learn more',
													'woocommerce-admin'
												) }
											</Link>
										) : (
											''
										),
									},
								} );

							return (
								<CheckboxControl
									key={ slug }
									label={ label }
									help={ helpText }
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
					</div>

					<div className="woocommerce-profile-wizard__card-actions">
						<Button
							isPrimary
							onClick={ this.onContinue }
							disabled={ ! selected.length }
						>
							{ __( 'Continue', 'woocommerce-admin' ) }
						</Button>
					</div>
				</Card>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getOnboardingError } = select(
			ONBOARDING_STORE_NAME
		);

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			profileItems: getProfileItems(),
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
