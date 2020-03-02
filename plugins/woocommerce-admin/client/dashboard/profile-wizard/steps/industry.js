/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button, CheckboxControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { filter, get, find, findIndex } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce Dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { H, Card, TextControl } from '@woocommerce/components';
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
			textInputListContent: {},
		};
		this.onContinue = this.onContinue.bind( this );
		this.onIndustryChange = this.onIndustryChange.bind( this );
		this.onDetailChange = this.onDetailChange.bind( this );
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
		const selectedIndustriesList = this.state.selected.map(
			( industry ) => industry.slug
		);
		const industriesWithDetail = filter( this.state.selected, ( value ) => {
			return typeof value.detail !== 'undefined';
		} );

		recordEvent( 'storeprofiler_store_industry_continue', {
			store_industry: selectedIndustriesList,
			industries_with_detail: industriesWithDetail,
		} );
		await updateProfileItems( { industry: this.state.selected } );

		if ( ! isError ) {
			goToNextStep();
		} else {
			createNotice(
				'error',
				__(
					'There was a problem updating your industries.',
					'woocommerce-admin'
				)
			);
		}
	}

	async validateField() {
		const error = this.state.selected.length
			? null
			: __( 'Please select at least one industry', 'woocommerce-admin' );
		this.setState( { error } );
	}

	onIndustryChange( slug ) {
		this.setState(
			( state ) => {
				const newSelected = state.selected;
				const selectedIndustry = find( newSelected, { slug } );
				if ( selectedIndustry ) {
					const newTextInputListContent = state.textInputListContent;
					newTextInputListContent[ slug ] = selectedIndustry.detail;
					return {
						selected:
							filter( state.selected, ( value ) => {
								return value.slug !== slug;
							} ) || [],
						textInputListContent: newTextInputListContent,
					};
				}
				newSelected.push( {
					slug,
					detail: state.textInputListContent[ slug ],
				} );
				return {
					selected: newSelected,
				};
			},
			() => this.validateField()
		);
	}

	onDetailChange( value, slug ) {
		this.setState( ( state ) => {
			const newSelected = state.selected;
			const newTextInputListContent = state.textInputListContent;
			const industryIndex = findIndex( newSelected, { slug } );
			newSelected[ industryIndex ].detail = value;
			newTextInputListContent[ slug ] = value;
			return {
				selected: newSelected,
				textInputListContent: newTextInputListContent,
			};
		} );
	}

	render() {
		const { industries } = onboarding;
		const { error, selected, textInputListContent } = this.state;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __(
						'In which industry does the store operate?',
						'woocommerce-admin'
					) }
				</H>
				<p className="woocommerce-profile-wizard__intro-paragraph">
					{ __( 'Choose any that apply' ) }
				</p>
				<Card>
					<div className="woocommerce-profile-wizard__checkbox-group">
						{ Object.keys( industries ).map( ( slug ) => {
							const selectedIndustry = find( selected, { slug } );

							return (
								<div key={ `div-${ slug }` }>
									<CheckboxControl
										key={ `checkbox-control-${ slug }` }
										label={ industries[ slug ].label }
										onChange={ () =>
											this.onIndustryChange( slug )
										}
										checked={ selectedIndustry || false }
										className="woocommerce-profile-wizard__checkbox"
									/>
									{ industries[ slug ].use_description &&
										selectedIndustry && (
											<TextControl
												key={ `text-control-${ selectedIndustry.slug }` }
												label={
													industries[
														selectedIndustry.slug
													].description_label
												}
												value={
													selectedIndustry.detail ||
													textInputListContent[
														slug
													] ||
													''
												}
												onChange={ ( value ) =>
													this.onDetailChange(
														value,
														selectedIndustry.slug
													)
												}
												className="woocommerce-profile-wizard__text"
											/>
										) }
								</div>
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
		const { getProfileItems, getProfileItemsError } = select( 'wc-api' );

		return {
			isError: Boolean( getProfileItemsError() ),
			profileItems: getProfileItems(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( Industry );
