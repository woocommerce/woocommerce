/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Button, CheckboxControl } from '@wordpress/components';
import { includes, filter, get } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import { H, Card, Link } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { recordEvent } from 'lib/tracks';

class ProductTypes extends Component {
	constructor( props ) {
		super();
		const profileItems = get( props, 'profileItems', {} );

		this.state = {
			error: null,
			selected: profileItems.product_types || [],
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
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __(
						'What type of products will be listed?',
						'woocommerce-admin'
					) }
				</H>
				<p>{ __( 'Choose any that apply' ) }</p>

				<Card>
					<div className="woocommerce-profile-wizard__checkbox-group">
						{ Object.keys( productTypes ).map( ( slug ) => {
							const helpText =
								productTypes[ slug ].description &&
								interpolateComponents( {
									mixedString:
										productTypes[ slug ].description +
										( productTypes[ slug ].more_url
											? ' {{moreLink/}}'
											: '' ),
									components: {
										moreLink: productTypes[ slug ]
											.more_url ? (
												<Link
													href={
														productTypes[ slug ]
															.more_url
													}
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
									label={ productTypes[ slug ].label }
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

					<Button
						isPrimary
						className="woocommerce-profile-wizard__continue"
						onClick={ this.onContinue }
						disabled={ ! selected.length }
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getOnboardingError } = select( ONBOARDING_STORE_NAME );

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
