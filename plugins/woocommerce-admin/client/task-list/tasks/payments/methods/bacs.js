/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Form, H, TextControl } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

class Bacs extends Component {
	getInitialConfigValues = () => {
		return {
			account_name: '',
			account_number: '',
			bank_name: '',
			sort_code: '',
			iban: '',
			bic: '',
		};
	};

	validate = ( values ) => {
		const errors = {};

		if ( ! values.account_number && ! values.iban ) {
			errors.account_number = errors.iban = __(
				'Please enter an account number or IBAN',
				'woocommerce-admin'
			);
		}

		return errors;
	};

	updateSettings = async ( values ) => {
		const { updateOptions, createNotice, markConfigured } = this.props;

		const update = await updateOptions( {
			woocommerce_bacs_settings: {
				enabled: 'yes',
			},
			woocommerce_bacs_accounts: [ values ],
		} );

		if ( update.success ) {
			markConfigured( 'bacs' );
			createNotice(
				'success',
				__(
					'Direct bank transfer details added successfully',
					'woocommerce-admin'
				)
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

	render() {
		const { isOptionsRequesting } = this.props;

		return (
			<Form
				initialValues={ this.getInitialConfigValues() }
				onSubmitCallback={ this.updateSettings }
				validate={ this.validate }
			>
				{ ( { getInputProps, handleSubmit } ) => {
					return (
						<Fragment>
							<H>
								{ __(
									'Add your bank details',
									'woocommerce-admin'
								) }
							</H>
							<p>
								{ __(
									'These details are required to receive payments via bank transfer',
									'woocommerce-admin'
								) }
							</p>
							<div className="woocommerce-task-payment-method__fields">
								<TextControl
									label={ __(
										'Account name',
										'woocommerce-admin'
									) }
									required
									{ ...getInputProps( 'account_name' ) }
								/>
								<TextControl
									label={ __(
										'Account number',
										'woocommerce-admin'
									) }
									required
									{ ...getInputProps( 'account_number' ) }
								/>
								<TextControl
									label={ __(
										'Bank name',
										'woocommerce-admin'
									) }
									required
									{ ...getInputProps( 'bank_name' ) }
								/>
								<TextControl
									label={ __(
										'Sort code',
										'woocommerce-admin'
									) }
									required
									{ ...getInputProps( 'sort_code' ) }
								/>
								<TextControl
									label={ __( 'IBAN', 'woocommerce-admin' ) }
									required
									{ ...getInputProps( 'iban' ) }
								/>
								<TextControl
									label={ __(
										'BIC / Swift',
										'woocommerce-admin'
									) }
									required
									{ ...getInputProps( 'bic' ) }
								/>
							</div>
							<Button
								isPrimary
								isBusy={ isOptionsRequesting }
								onClick={ handleSubmit }
							>
								{ __( 'Save', 'woocommerce-admin' ) }
							</Button>
						</Fragment>
					);
				} }
			</Form>
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
)( Bacs );
