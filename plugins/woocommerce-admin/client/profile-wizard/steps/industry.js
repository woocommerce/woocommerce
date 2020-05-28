/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button, CheckboxControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { filter, find, findIndex, get } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce Dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import { ONBOARDING_STORE_NAME, SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { H, Card, TextControl } from '@woocommerce/components';
import { getCurrencyRegion } from 'dashboard/utils';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

const onboarding = getSetting( 'onboarding', {} );

class Industry extends Component {
	constructor( props ) {
		const profileItems = get( props, 'profileItems', {} );
		let selected = profileItems.industry || [];

		/**
		 * @todo Remove block on `updateProfileItems` refactor to wp.data dataStores.
		 *
		 * The following block is a side effect of wc-api not being truly async
		 * and is a temporary fix until a refactor to wp.data can take place.
		 *
		 * Calls to `updateProfileItems` in the previous screen happen async
		 * and won't be updated in wc-api's state when this component is initialized.
		 * As such, we need to make sure cbd is not initialized as selected when a
		 * user has changed location to non-US based.
		 */
		const { locationSettings } = props;
		const region = getCurrencyRegion(
			locationSettings.woocommerce_default_country
		);

		if ( region !== 'US' ) {
			const cbdSlug = 'cbd-other-hemp-derived-products';
			selected = selected.filter( ( industry ) => {
				return cbdSlug !== industry && cbdSlug !== industry.slug;
			} );
		}
		/**
		 * End block to be removed after refactor.
		 */

		super();
		this.state = {
			error: null,
			selected,
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

		// Here the selected industries are converted to a string that is a comma separated list
		const industriesWithDetail = this.state.selected
			.map( ( industry ) => industry.detail )
			.filter( ( n ) => n )
			.join( ',' );

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
		const { locationSettings } = this.props;
		const region = getCurrencyRegion(
			locationSettings.woocommerce_default_country
		);
		const industryKeys = Object.keys( industries );

		const filteredIndustryKeys =
			region === 'US'
				? industryKeys
				: industryKeys.filter(
						( slug ) => slug !== 'cbd-other-hemp-derived-products'
				  );

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
						{ filteredIndustryKeys.map( ( slug ) => {
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
		const { getProfileItems, getOnboardingError } = select( ONBOARDING_STORE_NAME );
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: locationSettings = {} } = getSettings( 'general' );

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			profileItems: getProfileItems(),
			locationSettings,
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
)( Industry );
