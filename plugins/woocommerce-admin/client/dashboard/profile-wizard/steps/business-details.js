/** @format */
/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { FormToggle } from '@wordpress/components';
import { Button, SelectControl } from 'newspack-components';
import { withDispatch } from '@wordpress/data';
import { keys, pickBy } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import { H, Card } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class BusinessDetails extends Component {
	constructor() {
		super();

		this.state = {
			other_platform: '',
			product_count: '',
			selling_venues: '',
			extensions: {
				facebook: true,
				mailchimp: true,
			},
		};

		this.onContinue = this.onContinue.bind( this );
	}

	async onContinue() {
		const { addNotice, goToNextStep, isError, updateProfileItems } = this.props;
		const { other_platform, product_count, selling_venues } = this.state;
		const extensions = keys( pickBy( this.state.extensions ) );

		recordEvent( 'storeprofiler_store_business_details_continue', {
			product_number: product_count,
			already_selling: 'no' !== selling_venues,
			used_platform: other_platform,
			install_facebook: this.state.extensions.facebook,
			install_mailchimp: this.state.extensions.mailchimp,
		} );

		await updateProfileItems( {
			other_platform,
			product_count,
			selling_venues,
			business_extensions: extensions,
		} );

		if ( ! isError ) {
			goToNextStep();
		} else {
			addNotice( {
				status: 'error',
				message: __( 'There was a problem updating your business details.', 'woocommerce-admin' ),
			} );
		}
	}

	isValidForm() {
		const { other_platform, product_count, selling_venues } = this.state;
		const other_platform_valid = [ 'other', 'brick-mortar-other' ].includes( selling_venues )
			? other_platform.length
			: true;

		if ( other_platform_valid && product_count.length && selling_venues.length ) {
			return true;
		}

		return false;
	}

	getNumberRangeString( min, max = false ) {
		if ( ! max ) {
			return sprintf(
				_x( '%s+', 'store product count', 'woocommerce-admin' ),
				numberFormat( min )
			);
		}

		return sprintf(
			_x( '%s - %s', 'store product count', 'woocommerce-admin' ),
			numberFormat( min ),
			numberFormat( max )
		);
	}

	setDefaultValue( key, options ) {
		if ( ! this.state[ key ].length ) {
			this.setState( { [ key ]: options[ 0 ].value } );
		}
	}

	onExtensionChange( extension ) {
		this.setState( {
			extensions: {
				...this.state.extensions,
				[ extension ]: ! this.state.extensions[ extension ],
			},
		} );
	}

	renderBusinessExtensionHelpText() {
		const extensionSlugs = {
			facebook: __( 'Facebook for WooCommerce', 'woocommerce-admin' ),
			mailchimp: __( 'Mailchimp for WooCommerce', 'woocommerce-admin' ),
		};

		const extensions = keys( pickBy( this.state.extensions ) );
		if ( 0 === extensions.length ) {
			return null;
		}

		return (
			<p>
				{ sprintf(
					__( 'The following plugins will be installed for free: %s' ),
					extensions
						.map( extension => {
							return extensionSlugs[ extension ];
						} )
						.join( ', ' )
				) }
			</p>
		);
	}

	renderBusinessExtensions() {
		const extensionBenefits = [
			{
				slug: 'facebook',
				title: __( 'Market on Facebook', 'woocommerce-admin' ),
				icon: 'onboarding/facebook.png',
				description: __(
					'Grow your business by targeting the right people and driving sales with Facebook.',
					'woocommerce-admin'
				),
			},
			{
				slug: 'mailchimp',
				title: __( 'Contact customers with Mailchimp', 'woocommerce-admin' ),
				icon: 'onboarding/mailchimp.png',
				description: __(
					'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.',
					'woocommerce-admin'
				),
			},
		];
		return (
			<div className="woocommerce-profile-wizard__benefits">
				{ extensionBenefits.map( benefit => (
					<div className="woocommerce-profile-wizard__benefit" key={ benefit.title }>
						<div className="woocommerce-profile-wizard__business-extension">
							<img src={ wcSettings.wcAdminAssetUrl + benefit.icon } alt="" />
						</div>
						<div className="woocommerce-profile-wizard__benefit-content">
							<H className="woocommerce-profile-wizard__benefit-title">{ benefit.title }</H>
							<p>{ benefit.description }</p>
						</div>
						<div className="woocommerce-profile-wizard__benefit-toggle">
							<FormToggle
								checked={ this.state.extensions[ benefit.slug ] }
								onChange={ () => this.onExtensionChange( benefit.slug ) }
								className="woocommerce-profile-wizard__toggle"
							/>
						</div>
					</div>
				) ) }
			</div>
		);
	}

	render() {
		const { other_platform, product_count, selling_venues } = this.state;

		const productCountOptions = [
			{
				value: '1-10',
				label: this.getNumberRangeString( 1, 10 ),
			},
			{
				value: '11-100',
				label: this.getNumberRangeString( 11, 100 ),
			},
			{
				value: '101-1000',
				label: this.getNumberRangeString( 101, 1000 ),
			},
			{
				value: '1000+',
				label: this.getNumberRangeString( 1000 ),
			},
		];

		const sellingVenueOptions = [
			{
				value: 'no',
				label: __( 'No', 'woocommerce-admin' ),
			},
			{
				value: 'other',
				label: __( 'Yes, on another platform', 'woocommerce-admin' ),
			},
			{
				value: 'brick-mortar',
				label: __( 'Yes, at a brick and mortar store', 'woocommerce-admin' ),
			},
			{
				value: 'brick-mortar-other',
				label: __(
					'Yes, on another platform and at a brick and mortar store',
					'woocommerce-admin'
				),
			},
		];

		const otherPlatformOptions = [
			{
				value: 'shopify',
				label: __( 'Shopify', 'woocommerce-admin' ),
			},
			{
				value: 'bigcommerce',
				label: __( 'BigCommerce', 'woocommerce-admin' ),
			},
			{
				value: 'magento',
				label: __( 'Magento', 'woocommerce-admin' ),
			},
			{
				value: 'wix',
				label: __( 'Wix', 'woocommerce-admin' ),
			},
			{
				value: 'other',
				label: __( 'Other', 'woocommerce-admin' ),
			},
		];

		// Show extensions when the currently selling elsewhere checkbox has been answered.
		const showExtensions = '' !== this.state.selling_venues;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Business details', 'woocommerce-admin' ) }
				</H>
				<p>{ __( 'Tell us about the business' ) }</p>

				<Card className="woocommerce-profile-wizard__product-types-card">
					<SelectControl
						label={ __( 'How many products will you add?', 'woocommerce-admin' ) }
						onChange={ value => this.setState( { product_count: value } ) }
						onFocus={ this.setDefaultValue.bind( this, 'product_count', productCountOptions ) }
						options={ productCountOptions }
						value={ product_count }
						required
					/>

					<SelectControl
						label={ __( 'Currently selling elsewhere?', 'woocommerce-admin' ) }
						onChange={ value => this.setState( { selling_venues: value } ) }
						onFocus={ this.setDefaultValue.bind( this, 'selling_venues', sellingVenueOptions ) }
						options={ sellingVenueOptions }
						value={ selling_venues }
						required
					/>

					{ [ 'other', 'brick-mortar-other' ].includes( selling_venues ) && (
						<SelectControl
							label={ __( 'Which platform is the store using?', 'woocommerce-admin' ) }
							onChange={ value => this.setState( { other_platform: value } ) }
							onFocus={ this.setDefaultValue.bind( this, 'other_platform', otherPlatformOptions ) }
							options={ otherPlatformOptions }
							value={ other_platform }
							required
						/>
					) }

					{ showExtensions && this.renderBusinessExtensions() }

					<Button
						isPrimary
						className="woocommerce-profile-wizard__continue"
						onClick={ this.onContinue }
						disabled={ ! this.isValidForm() }
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</Card>

				{ showExtensions && this.renderBusinessExtensionHelpText() }
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
)( BusinessDetails );
