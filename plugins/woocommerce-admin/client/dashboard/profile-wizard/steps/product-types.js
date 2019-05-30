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

class ProductTypes extends Component {
	constructor() {
		super();

		this.state = {
			selected: [],
		};

		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	async onContinue() {
		const { addNotice, goToNextStep, isError, updateProfileItems } = this.props;

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
		this.setState( state => {
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
		} );
	}

	render() {
		const { productTypes } = wcSettings.onboarding;
		const { selected } = this.state;
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
										<Link href={ productTypes[ slug ].more_url } target="_blank" type="external">
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
					</div>

					{ selected.length > 0 && (
						<Button
							isPrimary
							className="woocommerce-profile-wizard__continue"
							onClick={ this.onContinue }
						>
							{ __( 'Continue', 'woocommerce-admin' ) }
						</Button>
					) }
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
