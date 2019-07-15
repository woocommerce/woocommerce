/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button, CheckboxControl } from 'newspack-components';
import { includes, filter } from 'lodash';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { H, Card } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class Industry extends Component {
	constructor() {
		super();
		this.state = {
			error: null,
			selected: [],
		};
		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	async onContinue() {
		await this.validateField();
		if ( this.state.error ) {
			return;
		}

		const { addNotice, goToNextStep, isError, updateProfileItems } = this.props;

		recordEvent( 'storeprofiler_store_industry_continue', { store_industry: this.state.selected } );
		await updateProfileItems( { industry: this.state.selected } );

		if ( ! isError ) {
			goToNextStep();
		} else {
			addNotice( {
				status: 'error',
				message: __( 'There was a problem updating your industries.', 'woocommerce-admin' ),
			} );
		}
	}

	async validateField() {
		const error = this.state.selected.length
			? null
			: __( 'Please select at least one industry', 'woocommerce-admin' );
		this.setState( { error } );
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

	render() {
		const { industries } = wcSettings.onboarding;
		const { error } = this.state;
		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'In which industry does the store operate?', 'woocommerce-admin' ) }
				</H>
				<p className="woocommerce-profile-wizard__intro-paragraph">
					{ __( 'Choose any that apply' ) }
				</p>
				<Card className="woocommerce-profile-wizard__industry-card">
					<div className="woocommerce-profile-wizard__checkbox-group">
						{ Object.keys( industries ).map( slug => {
							return (
								<CheckboxControl
									key={ slug }
									label={ industries[ slug ] }
									onChange={ () => this.onChange( slug ) }
								/>
							);
						} ) }
						{ error && <span className="woocommerce-profile-wizard__error">{ error }</span> }
					</div>

					<Button isPrimary onClick={ this.onContinue }>
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
)( Industry );
