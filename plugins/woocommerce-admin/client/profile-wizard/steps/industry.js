/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CheckboxControl,
	Spinner,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { filter, find, findIndex, get } from 'lodash';
import { withDispatch, withSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, SETTINGS_STORE_NAME } from '@woocommerce/data';
import { TextControl } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { getCurrencyRegion } from '../../dashboard/utils';
import { getAdminSetting } from '~/utils/admin-settings';

const onboarding = getAdminSetting( 'onboarding', {} );

const Loader = ( props ) => {
	if ( props.isLoading ) {
		return (
			<div
				className="woocommerce-admin__industry__spinner"
				style={ { textAlign: 'center' } }
			>
				<Spinner />
			</div>
		);
	}

	return <Industry { ...props } />;
};

class Industry extends Component {
	constructor( props ) {
		const profileItems = get( props, 'profileItems', {} );
		let selected = Array.isArray( profileItems.industry )
			? [ ...profileItems.industry ]
			: [];
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
		const selectedSlugs = this.getSelectedSlugs();
		props.trackStepValueChanges(
			props.step.key,
			selectedSlugs,
			selectedSlugs,
			this.onContinue
		);
	}

	getSelectedSlugs() {
		return this.state.selected.map( ( industry ) => industry.slug );
	}

	componentDidMount() {
		recordEvent( 'onboarding_site_heuristics', {
			page_count: onboarding.pageCount,
			post_count: onboarding.postCount,
			is_block_theme: onboarding.isBlockTheme,
		} );
	}

	componentDidUpdate() {
		this.props.updateCurrentStepValues(
			this.props.step.key,
			this.getSelectedSlugs()
		);
	}

	async onContinue() {
		await this.validateField();
		if ( this.state.error ) {
			return;
		}

		const { createNotice, isError, updateProfileItems } = this.props;
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

		if ( isError ) {
			createNotice(
				'error',
				__(
					'There was a problem updating your industries',
					'woocommerce'
				)
			);

			return Promise.reject();
		}

		return true;
	}

	async validateField() {
		const error = this.state.selected.length
			? null
			: __( 'Please select at least one industry', 'woocommerce' );
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

	renderIndustryLabel( slug, industry, selectedIndustry ) {
		const { textInputListContent } = this.state;

		return (
			<>
				{ industry.label }
				{ industry.use_description && selectedIndustry && (
					<TextControl
						key={ `text-control-${ slug }` }
						label={ industry.description_label }
						value={
							selectedIndustry.detail ||
							textInputListContent[ slug ] ||
							''
						}
						onChange={ ( value ) =>
							this.onDetailChange( value, slug )
						}
						className="woocommerce-profile-wizard__text"
					/>
				) }
			</>
		);
	}

	render() {
		const { industries } = onboarding;
		const { error, selected } = this.state;
		const { locationSettings, isProfileItemsRequesting } = this.props;
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
				<div className="woocommerce-profile-wizard__step-header">
					<Text
						variant="title.small"
						as="h2"
						size="20"
						lineHeight="28px"
					>
						{ __(
							'In which industry does the store operate?',
							'woocommerce'
						) }
					</Text>
					<Text variant="body" as="p">
						{ __( 'Choose any that apply', 'woocommerce' ) }
					</Text>
				</div>
				<Card>
					<CardBody size={ null }>
						<div className="woocommerce-profile-wizard__checkbox-group">
							{ filteredIndustryKeys.map( ( slug ) => {
								const selectedIndustry = find( selected, {
									slug,
								} );

								return (
									<CheckboxControl
										key={ `checkbox-control-${ slug }` }
										label={ this.renderIndustryLabel(
											slug,
											industries[ slug ],
											selectedIndustry
										) }
										onChange={ () =>
											this.onIndustryChange( slug )
										}
										checked={ selectedIndustry || false }
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
					</CardBody>
					<CardFooter isBorderless justify="center">
						<Button
							isPrimary
							onClick={ () => {
								this.onContinue().then(
									this.props.goToNextStep
								);
							} }
							isBusy={ isProfileItemsRequesting }
							disabled={
								! selected.length || isProfileItemsRequesting
							}
							aria-disabled={
								! selected.length || isProfileItemsRequesting
							}
						>
							{ __( 'Continue', 'woocommerce' ) }
						</Button>
					</CardFooter>
				</Card>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getProfileItems,
			getOnboardingError,
			isOnboardingRequesting,
			hasFinishedResolution: hasOnboardingFinishedResolution,
		} = select( ONBOARDING_STORE_NAME );
		const {
			getSettings,
			hasFinishedResolution: hasSettingsFinishedResolution,
		} = select( SETTINGS_STORE_NAME );
		const { general: locationSettings = {} } = getSettings( 'general' );

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			profileItems: getProfileItems(),
			locationSettings,
			isProfileItemsRequesting:
				isOnboardingRequesting( 'updateProfileItems' ),
			isLoading:
				! hasOnboardingFinishedResolution( 'getProfileItems', [] ) ||
				! hasSettingsFinishedResolution( 'getSettings', [ 'general' ] ),
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
)( Loader );
