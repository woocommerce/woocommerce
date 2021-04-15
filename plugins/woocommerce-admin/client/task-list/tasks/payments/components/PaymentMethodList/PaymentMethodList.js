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
import { PaymentAction } from '../PaymentAction';
import { RecommendedRibbon } from '../RecommendedRibbon';
import { SetupRequired } from '../SetupRequired';

import './PaymentMethodList.scss';

export const PaymentMethodList = ( {
	recommendedMethod,
	heading,
	methods,
	markConfigured,
} ) => (
	<Card>
		<CardHeader as="h2">{ heading }</CardHeader>
		{ methods.map( ( method, index ) => {
			const {
				before,
				content,
				isEnabled,
				isConfigured,
				key,
				title,
				visible,
				loading,
				manageUrl,
			} = method;

			if ( ! visible ) {
				return null;
			}

			const classes = classnames(
				'woocommerce-task-payment',
				'woocommerce-task-card',
				! isConfigured && 'woocommerce-task-payment-not-configured',
				'woocommerce-task-payment-' + key
			);

			const isRecommended = key === recommendedMethod && ! isConfigured;
			const showRecommendedRibbon = isRecommended;

			return (
				<Fragment key={ key }>
					{ index !== 0 && <CardDivider /> }
					<CardBody
						style={ { paddingLeft: 0, marginBottom: 0 } }
						className={ classes }
					>
						<CardMedia isBorderless>{ before }</CardMedia>
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
								hasSetup={ !! method.container }
								isConfigured={ isConfigured }
								isEnabled={ method.isEnabled }
								isRecommended={ isRecommended }
								isLoading={ loading }
								markConfigured={ markConfigured }
								onSetup={ () =>
									recordEvent( 'tasklist_payment_setup', {
										options: methods.map(
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
