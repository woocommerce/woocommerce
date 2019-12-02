/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button, CheckboxControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { filter, get, includes } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce Dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { H, Card } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

const onboarding = getSetting( 'onboarding', {} );

class Industry extends Component {
	constructor( props ) {
		const profileItems = get( props, 'profileItems', {} );

		super();
		this.state = {
			error: null,
			selected: profileItems.industry || [],
		};
		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	async onContinue() {
		await this.validateField();
		if ( this.state.error ) {
			return;
		}

		const { createNotice, goToNextStep, isError, updateProfileItems } = this.props;

		recordEvent( 'storeprofiler_store_industry_continue', { store_industry: this.state.selected } );
		await updateProfileItems( { industry: this.state.selected } );

		if ( ! isError ) {
			goToNextStep();
		} else {
			createNotice(
				'error',
				__( 'There was a problem updating your industries.', 'woocommerce-admin' )
			);
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
		const { industries } = onboarding;
		const { error, selected } = this.state;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'In which industry does the store operate?', 'woocommerce-admin' ) }
				</H>
				<p className="woocommerce-profile-wizard__intro-paragraph">
					{ __( 'Choose any that apply' ) }
				</p>
				<Card>
					<div className="woocommerce-profile-wizard__checkbox-group">
						{ Object.keys( industries ).map( slug => {
							return (
								<CheckboxControl
									key={ slug }
									label={ industries[ slug ] }
									onChange={ () => this.onChange( slug ) }
									checked={ selected.includes( slug ) }
									className="woocommerce-profile-wizard__checkbox"
								/>
							);
						} ) }
						{ error && <span className="woocommerce-profile-wizard__error">{ error }</span> }
					</div>

					<Button isPrimary onClick={ this.onContinue } disabled={ ! selected.length }>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getProfileItems, getProfileItemsError } = select( 'wc-api' );

		return {
			isError: Boolean( getProfileItemsError() ),
			profileItems: getProfileItems(),
		};
	} ),
	withDispatch( dispatch => {
		const { updateProfileItems } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( Industry );
