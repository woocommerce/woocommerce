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

/**
 * Internal dependencies
 */
import { H, Card, SimpleSelectControl } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class BusinessDetails extends Component {
	constructor() {
		super();

		this.state = {
			errors: {},
			fields: {
				other_platform: '',
				product_count: '',
				selling_venues: '',
				extensions: {
					facebook: true,
					mailchimp: true,
				},
			},
		};

		this.onContinue = this.onContinue.bind( this );
	}

	async onContinue() {
		await this.validateForm();
		if ( Object.keys( this.state.errors ).length ) {
			return;
		}

		const { addNotice, goToNextStep, isError, updateProfileItems } = this.props;
		const { extensions, other_platform, product_count, selling_venues } = this.state.fields;
		const businessExtensions = keys( pickBy( extensions ) );

		recordEvent( 'storeprofiler_store_business_details_continue', {
			product_number: product_count,
			already_selling: 'no' !== selling_venues,
			used_platform: other_platform,
			install_facebook: extensions.facebook,
			install_mailchimp: extensions.mailchimp,
		} );

		await updateProfileItems( {
			other_platform,
			product_count,
			selling_venues,
			business_extensions: businessExtensions,
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

	validateField( name ) {
		const { errors, fields } = this.state;

		switch ( name ) {
			case 'extensions':
				break;
			case 'other_platform':
				errors.other_platform =
					[ 'other', 'brick-mortar-other' ].includes( fields.selling_venues ) &&
					! fields.other_platform.length
						? __( 'This field is required', 'woocommerce-admin' )
						: null;
				break;
			default:
				errors[ name ] = ! fields[ name ].length
					? __( 'This field is required', 'woocommerce-admin' )
					: null;
				break;
		}

		this.setState( { errors: pickBy( errors ) } );
	}

	updateValue( name, value ) {
		const fields = { ...this.state.fields, [ name ]: value };
		this.setState( { fields }, () => this.validateField( name ) );
	}

	async validateForm() {
		const { fields } = this.state;
		Object.keys( fields ).forEach( fieldName => this.validateField( fieldName ) );
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
		const { fields } = this.state;

		if ( ! fields[ key ].length ) {
			this.setState( { fields: { ...fields, [ key ]: options[ 0 ].value } }, () =>
				this.validateField( key )
			);
		}
	}

	onExtensionChange( extension ) {
		const { fields } = this.state;
		const extensions = {
			...fields.extensions,
			[ extension ]: ! fields.extensions[ extension ],
		};

		this.setState( { fields: { ...fields, extensions } } );
	}

	renderBusinessExtensionHelpText() {
		const extensionSlugs = {
			facebook: __( 'Facebook for WooCommerce', 'woocommerce-admin' ),
			mailchimp: __( 'Mailchimp for WooCommerce', 'woocommerce-admin' ),
		};

		const extensions = keys( pickBy( this.state.fields.extensions ) );
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
								checked={ this.state.fields.extensions[ benefit.slug ] }
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
		const { errors, fields } = this.state;
		const { other_platform, product_count, selling_venues } = fields;

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
		const showExtensions = '' !== selling_venues;

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Business details', 'woocommerce-admin' ) }
				</H>
				<p>{ __( 'Tell us about the business' ) }</p>

				<Card>
					<SimpleSelectControl
						label={ __( 'How many products will you add?', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'product_count', value ) }
						onFocus={ this.setDefaultValue.bind( this, 'product_count', productCountOptions ) }
						options={ productCountOptions }
						value={ product_count }
						help={ errors.product_count }
						className={ errors.product_count ? 'has-error' : null }
						required
					/>

					<SimpleSelectControl
						label={ __( 'Currently selling elsewhere?', 'woocommerce-admin' ) }
						onChange={ value => this.updateValue( 'selling_venues', value ) }
						onFocus={ this.setDefaultValue.bind( this, 'selling_venues', sellingVenueOptions ) }
						options={ sellingVenueOptions }
						value={ selling_venues }
						help={ errors.selling_venues }
						className={ errors.selling_venues ? 'has-error' : null }
						required
					/>

					{ [ 'other', 'brick-mortar-other' ].includes( selling_venues ) && (
						<SimpleSelectControl
							label={ __( 'Which platform is the store using?', 'woocommerce-admin' ) }
							onChange={ value => this.updateValue( 'other_platform', value ) }
							onFocus={ this.setDefaultValue.bind( this, 'other_platform', otherPlatformOptions ) }
							options={ otherPlatformOptions }
							value={ other_platform }
							help={ errors.other_platform }
							className={ errors.other_platform ? 'has-error' : null }
							required
						/>
					) }

					{ showExtensions && this.renderBusinessExtensions() }

					<Button
						isPrimary
						className="woocommerce-profile-wizard__continue"
						onClick={ this.onContinue }
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
