/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button } from 'newspack-components';
import { getQuery } from '@woocommerce/navigation';
import { withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * WooCommerce dependencies
 */
import { WC_ADMIN_NAMESPACE } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

class Square extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			showSkipButton: false,
		};

		this.connect = this.connect.bind( this );
	}

	componentDidMount() {
		const query = getQuery();
		// Handle redirect back from Square
		if ( query[ 'square-connect' ] ) {
			if ( '1' === query[ 'square-connect' ] ) {
				recordEvent( 'tasklist_payment_connect_method', { payment_method: 'square' } );
				this.props.markConfigured( 'square' );
				this.props.createNotice(
					'success',
					__( 'Square connected successfully.', 'woocommerce-admin' )
				);
				return;
			}
		}
	}

	componentDidUpdate( prevProps ) {
		if ( false === prevProps.optionsIsRequesting && true === this.props.optionsIsRequesting ) {
			this.props.setRequestPending( true );
		}

		if ( true === prevProps.optionsIsRequesting && false === this.props.optionsIsRequesting ) {
			this.props.setRequestPending( false );
		}
	}

	async connect() {
		const { updateOptions } = this.props;
		this.props.setRequestPending( true );

		updateOptions( {
			woocommerce_stripe_settings: {
				...this.props.options.woocommerce_stripe_settings,
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
				this.props.setRequestPending( false );
				this.setState( { showSkipButton: true } );
				this.props.createNotice( 'error', errorMessage );
				return;
			}

			this.props.setRequestPending( false );
			window.location = result.connectUrl;
		} catch ( error ) {
			this.props.setRequestPending( false );
			this.setState( { showSkipButton: true } );
			this.props.createNotice( 'error', errorMessage );
		}
	}

	render() {
		const { showSkipButton } = this.state;

		return (
			<Fragment>
				<Button isPrimary isDefault onClick={ this.connect }>
					{ __( 'Connect', 'woocommerce-admin' ) }
				</Button>
				{ showSkipButton && (
					<Button
						onClick={ () => {
							this.props.markConfigured( 'square' );
						} }
					>
						{ __( 'Skip', 'woocommerce-admin' ) }
					</Button>
				) }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
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
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( 'wc-api' );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( Square );
