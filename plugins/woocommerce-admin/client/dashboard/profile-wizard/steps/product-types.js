/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Button, CheckboxControl } from 'newspack-components';
import { includes, filter } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { H, Card, Link } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class ProductTypes extends Component {
	constructor() {
		super();

		this.state = {
			error: null,
			selected: [],
		};

		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	async validateField() {
		const error = this.state.selected.length
			? null
			: __( 'Please select at least one product type', 'woocommerce-admin' );
		this.setState( { error } );
	}

	async onContinue() {
		await this.validateField();
		if ( this.state.error ) {
			return;
		}

		const { addNotice, goToNextStep, isError, updateProfileItems } = this.props;

		recordEvent( 'storeprofiler_store_product_type_continue', {
			product_type: this.state.selected,
		} );
		await updateProfileItems( { product_types: this.state.selected } );

		if ( ! isError ) {
			goToNextStep();
		} else {
			addNotice( {
				status: 'error',
				message: __( 'There was a problem updating your product types.', 'woocommerce-admin' ),
			} );
		}
	}

	onChange( slug ) {
		this.setState(
			state => {
				if ( includes( state.selected, slug ) ) {
					return {
						selected:
							filter( state.selected, value => {
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
		recordEvent( 'storeprofiler_store_product_type_learn_more', { product_type: slug } );
	}

	render() {
		const { productTypes } = wcSettings.onboarding;
		const { error } = this.state;
		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'What type of products will be listed?', 'woocommerce-admin' ) }
				</H>
				<p>{ __( 'Choose any that apply' ) }</p>

				<Card className="woocommerce-profile-wizard__product-types-card">
					<div className="woocommerce-profile-wizard__checkbox-group">
						{ Object.keys( productTypes ).map( slug => {
							const helpText = interpolateComponents( {
								mixedString:
									productTypes[ slug ].description +
									( productTypes[ slug ].more_url ? ' {{moreLink/}}' : '' ),
								components: {
									moreLink: productTypes[ slug ].more_url ? (
										<Link
											href={ productTypes[ slug ].more_url }
											target="_blank"
											type="external"
											onClick={ () => this.onLearnMore( slug ) }
										>
											{ __( 'Learn more', 'woocommerce-admin' ) }
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
								/>
							);
						} ) }
						{ error && <span className="woocommerce-profile-wizard__error">{ error }</span> }
					</div>

					<Button
						isPrimary
						className="woocommerce-profile-wizard__continue"
						onClick={ this.onContinue }
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getProfileItemsError } = select( 'wc-api' );

		const isError = Boolean( getProfileItemsError() );

		return { isError };
	} ),
	withDispatch( dispatch => {
		const { addNotice, updateProfileItems } = dispatch( 'wc-api' );

		return {
			addNotice,
			updateProfileItems,
		};
	} )
)( ProductTypes );
