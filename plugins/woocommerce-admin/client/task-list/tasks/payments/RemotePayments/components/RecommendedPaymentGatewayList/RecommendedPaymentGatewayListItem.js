/**
 * External dependencies
 */
import classnames from 'classnames';
import { Fragment } from '@wordpress/element';
import { CardBody, CardMedia, CardDivider } from '@wordpress/components';
import { PAYMENT_GATEWAYS_STORE_NAME } from '@woocommerce/data';
import { RecommendedRibbon, SetupRequired } from '@woocommerce/onboarding';
import { recordEvent } from '@woocommerce/tracks';
import { Text, useSlot } from '@woocommerce/experimental';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PaymentAction } from '../../../components/PaymentAction';

import './RecommendedPaymentGatewayList.scss';

export const RecommendedPaymentGatewayListItem = ( {
	isRecommended,
	paymentGateway,
	markConfigured,
	recommendedPaymentGatewayKeys,
} ) => {
	const {
		image,
		content,
		key,
		plugins = [],
		title,
		is_visible: isVisible,
		loading,
	} = paymentGateway;

	const slot = useSlot( `woocommerce_remote_payment_${ key }` );

	const installedPaymentGateway = useSelect( ( select ) => {
		return (
			select( PAYMENT_GATEWAYS_STORE_NAME ).getPaymentGateway( key ) || {}
		);
	} );

	if ( ! isVisible ) {
		return null;
	}

	const {
		enabled: isEnabled = false,
		needs_setup: needsSetup = false,
		required_settings_keys: requiredSettingsKeys = [],
		settings_url: manageUrl,
	} = installedPaymentGateway;

	const isConfigured = ! needsSetup;
	const hasFills = Boolean( slot?.fills?.length );
	const hasSetup = Boolean(
		plugins.length || requiredSettingsKeys.length || hasFills
	);
	const showRecommendedRibbon = isRecommended && ! isConfigured;

	const classes = classnames(
		'woocommerce-task-payment',
		'woocommerce-task-card',
		! isConfigured && 'woocommerce-task-payment-not-configured',
		'woocommerce-task-payment-' + key
	);

	return (
		<Fragment key={ key }>
			<CardBody
				style={ { paddingLeft: 0, marginBottom: 0 } }
				className={ classes }
			>
				<CardMedia isBorderless>
					<img src={ image } alt={ title } />
				</CardMedia>
				<div className="woocommerce-task-payment__description">
					{ showRecommendedRibbon && <RecommendedRibbon /> }
					<Text as="h3" className="woocommerce-task-payment__title">
						{ title }
						{ isEnabled && ! isConfigured && <SetupRequired /> }
					</Text>
					<div className="woocommerce-task-payment__content">
						{ content }
					</div>
				</div>
				<div className="woocommerce-task-payment__footer">
					<PaymentAction
						manageUrl={ manageUrl }
						methodKey={ key }
						hasSetup={ hasSetup }
						isConfigured={ isConfigured }
						isEnabled={ isEnabled }
						isRecommended={ isRecommended }
						isLoading={ loading }
						markConfigured={ markConfigured }
						onSetup={ () =>
							recordEvent( 'tasklist_payment_setup', {
								options: recommendedPaymentGatewayKeys,
								selected: key,
							} )
						}
					/>
				</div>
			</CardBody>
			<CardDivider />
		</Fragment>
	);
};
