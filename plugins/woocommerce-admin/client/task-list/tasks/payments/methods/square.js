/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { getQuery } from '@woocommerce/navigation';
import { compose } from '@wordpress/compose';
import { Stepper } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { OPTIONS_STORE_NAME, WC_ADMIN_NAMESPACE } from '@woocommerce/data';

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
		const {
			createNotice,
			hasCbdIndustry,
			options,
			recordConnectStartEvent,
			updateOptions,
		} = this.props;
		this.setState( { isPending: true } );

		updateOptions( {
			woocommerce_square_credit_card_settings: {
				...options.woocommerce_square_credit_card_settings,
				enabled: 'yes',
			},
		} );

		const errorMessage = __(
			'There was an error connecting to Square. Please try again or skip to connect later in store settings.',
			'woocommerce-admin'
		);

		recordConnectStartEvent( 'square' );

		try {
			let newWindow = null;
			if ( hasCbdIndustry ) {
				// It's necessary to declare the new tab before the async call,
				// otherwise, it won't be possible to open it.
				newWindow = window.open( '/', '_blank' );
			}

			const result = await apiFetch( {
				path: WC_ADMIN_NAMESPACE + '/plugins/connect-square',
				method: 'POST',
			} );

			if ( ! result || ! result.connectUrl ) {
				this.setState( { isPending: false } );
				createNotice( 'error', errorMessage );
				if ( hasCbdIndustry ) {
					newWindow.close();
				}
				return;
			}

			this.setState( { isPending: true } );
			this.redirect( result.connectUrl, newWindow );
		} catch ( error ) {
			this.setState( { isPending: false } );
			createNotice( 'error', errorMessage );
		}
	}

	redirect( connectUrl, newWindow ) {
		if ( newWindow ) {
			newWindow.location.href = connectUrl;
			window.location = getAdminLink( 'admin.php?page=wc-admin' );
		} else {
			window.location = connectUrl;
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
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );
		const options = getOption( 'woocommerce_square_credit_card_settings' );
		const optionsIsRequesting = isResolving( 'getOption', [
			'woocommerce_square_credit_card_settings',
		] );

		return {
			options,
			optionsIsRequesting,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		return {
			createNotice,
			updateOptions,
		};
	} )
)( Square );
