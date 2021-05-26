/**
 * External dependencies
 */
import classnames from 'classnames';
import { Fragment } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardMedia,
	CardHeader,
	CardDivider,
} from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PaymentAction } from '../../../components/PaymentAction';
import { RecommendedRibbon } from '../../../components/RecommendedRibbon';
import { SetupRequired } from '../../../components/SetupRequired';

import './RecommendedPaymentGatewayList.scss';

export const RecommendedPaymentGatewayList = ( {
	recommendedMethod,
	heading,
	installedPaymentGateways,
	recommendedPaymentGateways,
	markConfigured,
} ) => (
	<Card>
		<CardHeader as="h2">{ heading }</CardHeader>
		{ recommendedPaymentGateways.map( ( method, index ) => {
			const {
				image,
				content,
				key,
				plugins = [],
				title,
				is_visible: isVisible,
				loading,
			} = method;

			if ( ! isVisible ) {
				return null;
			}

			const installedPaymentGateway = installedPaymentGateways[ key ];
			const {
				enabled: isEnabled = false,
				needs_setup: needsSetup = false,
				required_settings_keys: requiredSettingsKeys = [],
				settings_url: manageUrl,
			} = installedPaymentGateway || {};

			const isConfigured = ! needsSetup;
			const hasSetup = Boolean(
				plugins.length || requiredSettingsKeys.length
			);
			const isRecommended = key === recommendedMethod && ! isConfigured;
			const showRecommendedRibbon = isRecommended;

			const classes = classnames(
				'woocommerce-task-payment',
				'woocommerce-task-card',
				! isConfigured && 'woocommerce-task-payment-not-configured',
				'woocommerce-task-payment-' + key
			);

			return (
				<Fragment key={ key }>
					{ index !== 0 && <CardDivider /> }
					<CardBody
						style={ { paddingLeft: 0, marginBottom: 0 } }
						className={ classes }
					>
						<CardMedia isBorderless>
							<img src={ image } alt={ title } />
						</CardMedia>
						<div className="woocommerce-task-payment__description">
							{ showRecommendedRibbon && <RecommendedRibbon /> }
							<Text
								as="h3"
								className="woocommerce-task-payment__title"
							>
								{ title }
								{ isEnabled && ! isConfigured && (
									<SetupRequired />
								) }
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
										options: recommendedPaymentGateways.map(
											( option ) => option.key
										),
										selected: key,
									} )
								}
								onSetupCallback={ method.onClick }
							/>
						</div>
					</CardBody>
				</Fragment>
			);
		} ) }
	</Card>
);
