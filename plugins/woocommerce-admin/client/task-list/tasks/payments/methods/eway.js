/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Form, Link, Stepper, TextControl } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

class EWay extends Component {
	getInitialConfigValues = () => {
		return {
			customer_api: '',
			customer_password: '',
		};
	};

	validate = ( values ) => {
		const errors = {};

		if ( ! values.customer_api ) {
			errors.customer_api = __(
				'Please enter your customer API key ',
				'woocommerce-admin'
			);
		}

		if ( ! values.customer_password ) {
			errors.customer_password = __(
				'Please enter your customer password',
				'woocommerce-admin'
			);
		}

		return errors;
	};

	updateSettings = async ( values ) => {
		const { updateOptions, createNotice, markConfigured } = this.props;

		const update = await updateOptions( {
			woocommerce_eway_settings: {
				customer_api: values.customer_api,
				customer_password: values.customer_password,
				enabled: 'yes',
			},
		} );

		if ( update.success ) {
			markConfigured( 'eway' );
			createNotice(
				'success',
				__( 'eWAY connected successfully', 'woocommerce-admin' )
			);
		} else {
			createNotice(
				'error',
				__(
					'There was a problem saving your payment settings',
					'woocommerce-admin'
				)
			);
		}
	};

	renderConnectStep() {
		const { isOptionsRequesting, recordConnectStartEvent } = this.props;
		const helpText = interpolateComponents( {
			mixedString: __(
				'Your API details can be obtained from your {{link}}eWAY account{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link: (
					<Link
						href="https://www.eway.com.au/"
						target="_blank"
						type="external"
					/>
				),
			},
		} );

		return (
			<Form
				initialValues={ this.getInitialConfigValues() }
				onSubmitCallback={ this.updateSettings }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit } ) => {
					return (
						<Fragment>
							<TextControl
								label={ __(
									'Customer API Key',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'customer_api' ) }
							/>
							<TextControl
								label={ __(
									'Customer Password',
									'woocommerce-admin'
								) }
								required
								{ ...getInputProps( 'customer_password' ) }
							/>
							<Button
								isPrimary
								isBusy={ isOptionsRequesting }
								onClick={ ( event ) => {
									recordConnectStartEvent( 'eway' );
									handleSubmit( event );
								} }
							>
								{ __( 'Proceed', 'woocommerce-admin' ) }
							</Button>

							<p>{ helpText }</p>
						</Fragment>
					);
				} }
			</Form>
		);
	}

	render() {
		const { installStep, isOptionsRequesting } = this.props;

		return (
			<Stepper
				isVertical
				isPending={ ! installStep.isComplete || isOptionsRequesting }
				currentStep={ installStep.isComplete ? 'connect' : 'install' }
				steps={ [
					installStep,
					{
						key: 'connect',
						label: __(
							'Connect your eWAY account',
							'woocommerce-admin'
						),
						content: this.renderConnectStep(),
					},
				] }
			/>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );
		const isOptionsRequesting = isOptionsUpdating();

		return {
			isOptionsRequesting,
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
)( EWay );
