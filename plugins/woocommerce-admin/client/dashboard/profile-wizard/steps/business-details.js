/** @format */
/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { FormToggle } from '@wordpress/components';
import { Button } from 'newspack-components';
import { withDispatch } from '@wordpress/data';
import { keys, pickBy } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { numberFormat } from '@woocommerce/number';
import { getSetting, CURRENCY as currency } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { H, Card, SelectControl, Form } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';
import { formatCurrency } from '@woocommerce/currency';
import Plugins from 'dashboard/task-list/tasks/steps/plugins';
import { pluginNames } from 'wc-api/onboarding/constants';

const wcAdminAssetUrl = getSetting( 'wcAdminAssetUrl', '' );

class BusinessDetails extends Component {
	constructor() {
		super();

		this.initialValues = {
			other_platform: '',
			product_count: '',
			selling_venues: '',
			revenue: '',
			'facebook-for-woocommerce': true,
			'mailchimp-for-woocommerce': true,
		};

		this.state = {
			installExtensions: false,
			isInstallingExtensions: false,
			extensionInstallError: false,
		};

		this.extensions = [ 'facebook-for-woocommerce', 'mailchimp-for-woocommerce' ];

		this.onContinue = this.onContinue.bind( this );
		this.validate = this.validate.bind( this );
	}

	async onContinue( values ) {
		const { createNotice, goToNextStep, isError, updateProfileItems } = this.props;
		const { other_platform, product_count, revenue, selling_venues } = values;
		const businessExtensions = this.getBusinessExtensions( values );

		recordEvent( 'storeprofiler_store_business_details_continue', {
			product_number: product_count,
			already_selling: 'no' !== selling_venues,
			currency: currency.code,
			revenue,
			used_platform: other_platform,
			install_facebook: values[ 'facebook-for-woocommerce' ],
			install_mailchimp: values[ 'mailchimp-for-woocommerce' ],
		} );

		const _updates = {
			other_platform,
			product_count,
			revenue,
			selling_venues,
			business_extensions: businessExtensions,
		};

		// Remove possible empty values like `revenue` and `other_platform`.
		const updates = {};
		Object.keys( _updates ).forEach( key => {
			if ( _updates[ key ] !== '' ) {
				updates[ key ] = _updates[ key ];
			}
		} );

		await updateProfileItems( updates );

		if ( ! isError ) {
			if ( 0 === businessExtensions.length ) {
				goToNextStep();
				return;
			}

			this.setState( {
				installExtensions: true,
				isInstallingExtensions: true,
			} );
		} else {
			createNotice(
				'error',
				__( 'There was a problem updating your business details.', 'woocommerce-admin' )
			);
		}
	}

	validate( values ) {
		const errors = {};

		Object.keys( values ).map( name => {
			if ( 'other_platform' === name ) {
				if (
					! values.other_platform.length &&
					[ 'other', 'brick-mortar-other' ].includes( values.selling_venues )
				) {
					errors.other_platform = __( 'This field is required', 'woocommerce-admin' );
				}
			} else if ( 'revenue' === name ) {
				if (
					! values.revenue.length &&
					[ 'other', 'brick-mortar', 'brick-mortar-other' ].includes( values.selling_venues )
				) {
					errors.revenue = __( 'This field is required', 'woocommerce-admin' );
				}
			} else if ( ! this.extensions.includes( name ) && ! values[ name ].length ) {
				errors[ name ] = __( 'This field is required', 'woocommerce-admin' );
			}
		} );

		return errors;
	}

	getBusinessExtensions( values ) {
		return keys( pickBy( values ) ).filter( name => this.extensions.includes( name ) );
	}

	getNumberRangeString( min, max = false, format = numberFormat ) {
		if ( ! max ) {
			return sprintf(
				_x( '%s+', 'store product count or revenue', 'woocommerce-admin' ),
				format( min )
			);
		}

		return sprintf(
			_x( '%1$s - %2$s', 'store product count or revenue range', 'woocommerce-admin' ),
			format( min ),
			format( max )
		);
	}

	renderBusinessExtensionHelpText( values ) {
		const { isInstallingExtensions } = this.state;
		const extensions = this.getBusinessExtensions( values );

		if ( 0 === extensions.length ) {
			return null;
		}

		const extensionsList = extensions
			.map( extension => {
				return pluginNames[ extension ];
			} )
			.join( ', ' );

		if ( isInstallingExtensions ) {
			return <p>{ sprintf( __( 'Installing the following plugins: %s' ), extensionsList ) }</p>;
		}

		return (
			<p>
				{ sprintf( __( 'The following plugins will be installed for free: %s' ), extensionsList ) }
			</p>
		);
	}

	renderBusinessExtensions( values, getInputProps ) {
		const { installExtensions } = this.state;
		const { goToNextStep } = this.props;
		const extensionsToInstall = this.getBusinessExtensions( values );
		const extensionBenefits = [
			{
				slug: 'facebook-for-woocommerce',
				title: __( 'Market on Facebook', 'woocommerce-admin' ),
				icon: 'onboarding/facebook.png',
				description: __(
					'Grow your business by targeting the right people and driving sales with Facebook.',
					'woocommerce-admin'
				),
			},
			{
				slug: 'mailchimp-for-woocommerce',
				title: __( 'Contact customers with Mailchimp', 'woocommerce-admin' ),
				icon: 'onboarding/mailchimp.png',
				description: __(
					'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.',
					'woocommerce-admin'
				),
			},
		];

		return (
			<Fragment>
				<div className="woocommerce-profile-wizard__benefits">
					{ extensionBenefits.map( benefit => (
						<div className="woocommerce-profile-wizard__benefit" key={ benefit.title }>
							<div className="woocommerce-profile-wizard__business-extension">
								<img src={ wcAdminAssetUrl + benefit.icon } alt="" />
							</div>
							<div className="woocommerce-profile-wizard__benefit-content">
								<H className="woocommerce-profile-wizard__benefit-title">{ benefit.title }</H>
								<p>{ benefit.description }</p>
							</div>
							<div className="woocommerce-profile-wizard__benefit-toggle">
								<FormToggle
									checked={ values[ benefit.slug ] }
									{ ...getInputProps( benefit.slug ) }
								/>
							</div>
						</div>
					) ) }
				</div>

				{ installExtensions && (
					<Plugins
						onComplete={ () => {
							goToNextStep();
						} }
						onSkip={ () => {
							goToNextStep();
						} }
						onError={ () => {
							this.setState( {
								extensionInstallError: true,
								isInstallingExtensions: false,
							} );
						} }
						autoInstall
						pluginSlugs={ extensionsToInstall }
					/>
				) }
			</Fragment>
		);
	}

	render() {
		const { isInstallingExtensions, extensionInstallError } = this.state;
		const productCountOptions = [
			{
				key: '0',
				label: __( "I don't have any products yet.", 'woocommerce-admin' ),
			},
			{
				key: '1-10',
				label: this.getNumberRangeString( 1, 10 ),
			},
			{
				key: '11-100',
				label: this.getNumberRangeString( 11, 100 ),
			},
			{
				key: '101-1000',
				label: this.getNumberRangeString( 101, 1000 ),
			},
			{
				key: '1000+',
				label: this.getNumberRangeString( 1000 ),
			},
		];

		const revenueOptions = [
			{
				key: 'none',
				label: sprintf(
					_x( "%s (I'm just getting started)", '$0 revenue amount', 'woocommerce-admin' ),
					formatCurrency( 0 )
				),
			},
			{
				key: 'up-to-2500',
				label: sprintf(
					_x( 'Up to %s', 'Up to a certain revenue amount', 'woocommerce-admin' ),
					formatCurrency( 2500 )
				),
			},
			{
				key: '2500-10000',
				label: this.getNumberRangeString( 2500, 10000, formatCurrency ),
			},
			{
				key: '10000-50000',
				label: this.getNumberRangeString( 10000, 50000, formatCurrency ),
			},
			{
				key: '50000-250000',
				label: this.getNumberRangeString( 50000, 250000, formatCurrency ),
			},
			{
				key: 'more-than-250000',
				label: sprintf(
					_x( 'More than %s', 'More than a certain revenue amount', 'woocommerce-admin' ),
					formatCurrency( 250000 )
				),
			},
		];

		const sellingVenueOptions = [
			{
				key: 'no',
				label: __( 'No', 'woocommerce-admin' ),
			},
			{
				key: 'other',
				label: __( 'Yes, on another platform', 'woocommerce-admin' ),
			},
			{
				key: 'brick-mortar',
				label: __( 'Yes, in person at physical stores and/or events', 'woocommerce-admin' ),
			},
			{
				key: 'brick-mortar-other',
				label: __(
					'Yes, on another platform and in person at physical stores and/or events',
					'woocommerce-admin'
				),
			},
		];

		const otherPlatformOptions = [
			{
				key: 'shopify',
				label: __( 'Shopify', 'woocommerce-admin' ),
			},
			{
				key: 'bigcommerce',
				label: __( 'BigCommerce', 'woocommerce-admin' ),
			},
			{
				key: 'magento',
				label: __( 'Magento', 'woocommerce-admin' ),
			},
			{
				key: 'wix',
				label: __( 'Wix', 'woocommerce-admin' ),
			},
			{
				key: 'other',
				label: __( 'Other', 'woocommerce-admin' ),
			},
		];

		return (
			<Form
				initialValues={ this.initialValues }
				onSubmitCallback={ this.onContinue }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit, values, isValidForm } ) => {
					// Show extensions when the currently selling elsewhere checkbox has been answered.
					const showExtensions = '' !== values.selling_venues;
					return (
						<Fragment>
							<H className="woocommerce-profile-wizard__header-title">
								{ __( 'Tell us about your business', 'woocommerce-admin' ) }
							</H>
							<p>
								{ __(
									"We'd love to know if you are just getting started or you already have a business in place.",
									'woocommerce-admin'
								) }
							</p>
							<Card>
								<Fragment>
									<SelectControl
										label={ __( 'How many products do you plan to add?', 'woocommerce-admin' ) }
										options={ productCountOptions }
										required
										{ ...getInputProps( 'product_count' ) }
									/>

									<SelectControl
										label={ __( 'Currently selling elsewhere?', 'woocommerce-admin' ) }
										options={ sellingVenueOptions }
										required
										{ ...getInputProps( 'selling_venues' ) }
									/>

									{ [ 'other', 'brick-mortar', 'brick-mortar-other' ].includes(
										values.selling_venues
									) && (
										<SelectControl
											label={ __( "What's your current annual revenue?", 'woocommerce-admin' ) }
											options={ revenueOptions }
											required
											{ ...getInputProps( 'revenue' ) }
										/>
									) }

									{ [ 'other', 'brick-mortar-other' ].includes( values.selling_venues ) && (
										<SelectControl
											label={ __( 'Which platform is the store using?', 'woocommerce-admin' ) }
											options={ otherPlatformOptions }
											required
											{ ...getInputProps( 'other_platform' ) }
										/>
									) }

									{ showExtensions && this.renderBusinessExtensions( values, getInputProps ) }

									{ ! extensionInstallError && (
										<Button
											isPrimary
											className="woocommerce-profile-wizard__continue"
											onClick={ handleSubmit }
											disabled={ ! isValidForm }
											isBusy={ isInstallingExtensions }
										>
											{ __( 'Continue', 'woocommerce-admin' ) }
										</Button>
									) }
								</Fragment>
							</Card>

							{ showExtensions && this.renderBusinessExtensionHelpText( values ) }
						</Fragment>
					);
				} }
			</Form>
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
)( BusinessDetails );
