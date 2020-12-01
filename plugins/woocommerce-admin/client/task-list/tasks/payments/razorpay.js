/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { Form, Link, Stepper, TextControl } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

const INITIAL_CONFIG = {
	key_id: '',
	key_secret: '',
};

const validate = ( values ) => {
	const errors = {};

	if ( ! values.key_id ) {
		errors.key_id = __( 'Please enter your Key ID', 'woocommerce-admin' );
	}

	if ( ! values.key_secret ) {
		errors.key_secret = __(
			'Please enter your Key Secret',
			'woocommerce-admin'
		);
	}

	return errors;
};

const updateSettings = async (
	values,
	createNotice,
	markConfigured,
	updateOptions
) => {
	const update = await updateOptions( {
		woocommerce_razorpay_settings: {
			key_id: values.key_id,
			key_secret: values.key_secret,
			enabled: 'yes',
		},
	} );

	if ( update.success ) {
		markConfigured( 'razorpay' );
		createNotice(
			'success',
			__( 'Razorpay connected successfully', 'woocommerce-admin' )
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

const renderConnectStep = ( {
	createNotice,
	isOptionsRequesting,
	markConfigured,
	updateOptions,
} ) => {
	const helpText = interpolateComponents( {
		mixedString: __(
			'Your key details can be obtained from your {{link}}Razorpay account{{/link}}',
			'woocommerce-admin'
		),
		components: {
			link: (
				<Link
					href="https://dashboard.razorpay.com/#/access/signin"
					target="_blank"
					type="external"
				/>
			),
		},
	} );

	const onSubmit = ( values ) =>
		updateSettings( values, createNotice, markConfigured, updateOptions );

	return (
		<Form
			initialValues={ INITIAL_CONFIG }
			onSubmitCallback={ onSubmit }
			validate={ validate }
		>
			{ ( { getInputProps, handleSubmit } ) => {
				return (
					<>
						<TextControl
							label={ __( 'Key ID', 'woocommerce-admin' ) }
							required
							{ ...getInputProps( 'key_id' ) }
						/>
						<TextControl
							label={ __( 'Key Secret', 'woocommerce-admin' ) }
							required
							{ ...getInputProps( 'key_secret' ) }
						/>
						<Button
							isPrimary
							isBusy={ isOptionsRequesting }
							onClick={ handleSubmit }
						>
							{ __( 'Proceed', 'woocommerce-admin' ) }
						</Button>

						<p>{ helpText }</p>
					</>
				);
			} }
		</Form>
	);
};

export const Razorpay = ( {
	createNotice,
	installStep,
	isOptionsRequesting,
	markConfigured,
	updateOptions,
} ) => {
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
						'Connect your Razorpay account',
						'woocommerce-admin'
					),
					content: renderConnectStep( {
						createNotice,
						isOptionsRequesting,
						markConfigured,
						updateOptions,
					} ),
				},
			] }
		/>
	);
};

export default ( { installStep, markConfigured } ) => {
	const isOptionsUpdating = useSelect(
		( select ) => select( OPTIONS_STORE_NAME ).isOptionsUpdating
	);
	const isOptionsRequesting = isOptionsUpdating();
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	return (
		<Razorpay
			createNotice={ createNotice }
			installStep={ installStep }
			isOptionsRequesting={ isOptionsRequesting }
			markConfigured={ markConfigured }
			updateOptions={ updateOptions }
		/>
	);
};
