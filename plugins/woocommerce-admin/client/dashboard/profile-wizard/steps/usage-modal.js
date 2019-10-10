/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl } from 'newspack-components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import { get } from 'lodash';
import interpolateComponents from 'interpolate-components';
import { FormToggle, Modal } from '@wordpress/components';

/**
 * Internal depdencies
 */
import { Link } from '@woocommerce/components';
import withSelect from 'wc-api/with-select';

class UsageModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			allowTracking: props.allowTracking,
		};

		this.onTrackingChange = this.onTrackingChange.bind( this );
	}

	onTrackingChange() {
		this.setState( {
			allowTracking: ! this.state.allowTracking,
		} );
	}

	async componentDidUpdate( prevProps ) {
		const { hasErrors, isRequesting, onClose, onContinue, createNotice } = this.props;
		const isRequestSuccessful = ! isRequesting && prevProps.isRequesting && ! hasErrors;
		const isRequestError = ! isRequesting && prevProps.isRequesting && hasErrors;

		if ( isRequestSuccessful ) {
			onClose();
			onContinue();
		}

		if ( isRequestError ) {
			createNotice(
				'error',
				__( 'There was a problem updating your preferences.', 'woocommerce-admin' )
			);
			onClose();
		}
	}

	updateTracking() {
		const { updateOptions } = this.props;
		const allowTracking = this.state.allowTracking ? 'yes' : 'no';
		updateOptions( {
			woocommerce_allow_tracking: allowTracking,
		} );
	}

	render() {
		const { allowTracking } = this.state;
		const { isRequesting } = this.props;
		const trackingMessage = interpolateComponents( {
			mixedString: __(
				"Learn more about how usage tracking works, and how you'll be helping {{link}}here{{/link}}.",
				'woocommerce-admin'
			),
			components: {
				link: (
					<Link href="https://woocommerce.com/usage-tracking" target="_blank" type="external" />
				),
			},
		} );

		return (
			<Modal
				title={ __( 'Help improve WooCommerce with usage tracking', 'woocommerce-admin' ) }
				onRequestClose={ () => this.props.onClose() }
				className="woocommerce-profile-wizard__usage-modal"
			>
				<div className="woocommerce-profile-wizard__usage-wrapper">
					<div className="woocommerce-profile-wizard__usage-modal-message">{ trackingMessage }</div>
					<div className="woocommerce-profile-wizard__tracking">
						<CheckboxControl
							className="woocommerce-profile-wizard__tracking-checkbox"
							checked={ allowTracking }
							label={ __(
								'Enable usage tracking and help improve WooCommerce',
								'woocommerce-admin'
							) }
							onChange={ this.onTrackingChange }
						/>

						<FormToggle
							aria-hidden="true"
							checked={ allowTracking }
							onChange={ this.onTrackingChange }
							onClick={ e => e.stopPropagation() }
							tabIndex="-1"
						/>
					</div>
					<Button
						isPrimary
						isDefault
						isBusy={ isRequesting }
						onClick={ () => this.updateTracking() }
					>
						{ __( 'Continue', 'woocommerce-admin' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getOptions, getOptionsError, isUpdateOptionsRequesting } = select( 'wc-api' );

		const options = getOptions( [ 'woocommerce_allow_tracking' ] );
		const allowTracking = 'yes' === get( options, [ 'woocommerce_allow_tracking' ], false );
		const isRequesting = Boolean( isUpdateOptionsRequesting( [ 'woocommerce_allow_tracking' ] ) );
		const hasErrors = Boolean( getOptionsError( [ 'woocommerce_allow_tracking' ] ) );

		return {
			allowTracking,
			isRequesting,
			hasErrors,
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
)( UsageModal );
