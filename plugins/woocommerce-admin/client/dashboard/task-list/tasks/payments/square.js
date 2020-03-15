/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * WooCommerce dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { WC_ADMIN_NAMESPACE } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import { Stepper } from '@woocommerce/components';

class Square extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			isPending: false,
		};

		this.connect = this.connect.bind( this );
	}

	componentDidMount() {
		const { createNotice, markConfigured } = this.props;
		const query = getQuery();
		// Handle redirect back from Square
		if ( query[ 'square-connect' ] ) {
			if ( query[ 'square-connect' ] === '1' ) {
				createNotice(
					'success',
					__( 'Square connected successfully.', 'woocommerce-admin' )
				);
				markConfigured( 'square' );
			}
		}
	}

	async connect() {
		const { createNotice, options, updateOptions } = this.props;
		this.setState( { isPending: true } );

		updateOptions( {
			woocommerce_stripe_settings: {
				...options.woocommerce_stripe_settings,
				enabled: 'yes',
			},
		} );

		const errorMessage = __(
			'There was an error connecting to Square. Please try again or skip to connect later in store settings.',
			'woocommerce-admin'
		);

		try {
			const result = await apiFetch( {
				path: WC_ADMIN_NAMESPACE + '/onboarding/plugins/connect-square',
				method: 'POST',
			} );

			if ( ! result || ! result.connectUrl ) {
				this.setState( { isPending: false } );
				createNotice( 'error', errorMessage );
				return;
			}

			this.setState( { isPending: true } );
			window.location = result.connectUrl;
		} catch ( error ) {
			this.setState( { isPending: false } );
			createNotice( 'error', errorMessage );
		}
	}

	render() {
		const { installStep } = this.props;
		const { isPending } = this.state;

		return (
			<Stepper
				isVertical
				isPending={ ! installStep.isComplete || isPending }
				currentStep={ installStep.isComplete ? 'connect' : 'install' }
				steps={ [
					installStep,
					{
						key: 'connect',
						label: __(
							'Connect your Square account',
							'woocommerce-admin'
						),
						description: __(
							'A Square account is required to process payments. You will be redirected to the Square website to create the connection.',
							'woocommerce-admin'
						),
						content: (
							<Fragment>
								<Button
									isPrimary
									isDefault
									isBusy={ isPending }
									onClick={ this.connect }
								>
									{ __( 'Connect', 'woocommerce-admin' ) }
								</Button>
							</Fragment>
						),
					},
				] }
			/>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getOptions, isGetOptionsRequesting } = select( 'wc-api' );
		const options = getOptions( [ 'woocommerce_stripe_settings' ] );
		const optionsIsRequesting = Boolean(
			isGetOptionsRequesting( [ 'woocommerce_stripe_settings' ] )
		);

		return {
			options,
			optionsIsRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( Square );
