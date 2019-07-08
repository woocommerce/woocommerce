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
			selected: [],
		};
		this.onContinue = this.onContinue.bind( this );
		this.onChange = this.onChange.bind( this );
	}

	async onContinue() {
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
		const { industries } = wcSettings.onboarding;
		const { selected } = this.state;
		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'In which industry does the store operate?', 'woocommerce-admin' ) }
				</H>
				<p>{ __( 'Choose any that apply' ) }</p>
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
					</div>

					{ selected.length > 0 && (
						<Button isPrimary onClick={ this.onContinue }>
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
		const { updateProfileItems } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( Industry );
