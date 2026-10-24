/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import './suggestion.scss';

const recordTrack = () => {
	recordEvent( 'tasklist_payments_wcpay_bnpl_click' );
};

export const Suggestion = ( { paymentGateway } ) => {
	const { id, title, content, settingsUrl, image } = paymentGateway;

	// If there is no settingsUrl, bail.
	if ( ! settingsUrl ) {
		return null;
	}

	const customizedSettingsUrl = addQueryArgs( settingsUrl, {
		from: 'WCADMIN_PAYMENT_TASK',
	} );

	return (
		<Card
			className="woocommerce-wcpay-bnpl-suggestion"
			size="medium"
			key={ id }
		>
			<div className="woocommerce-wcpay-bnpl-suggestion__contents-container">
				<CardHeader as="h2" isBorderless style={ { padding: 0 } }>
					{ title }
				</CardHeader>
				<CardBody
					className="woocommerce-wcpay-bnpl-suggestion__body"
					style={ { padding: 0 } }
				>
					<div
						className="woocommerce-wcpay-bnpl-suggestion__contents"
						style={ ! image ? { maxWidth: '100%' } : {} }
					>
						<p className="woocommerce-wcpay-bnpl-suggestion__description">
							{ content }
						</p>
						<Button
							className="woocommerce-wcpay-bnpl-suggestion__button"
							variant="primary"
							href={ customizedSettingsUrl }
							onClick={ recordTrack }
						>
							{ __( 'Get started', 'woocommerce' ) }
						</Button>
					</div>
				</CardBody>
			</div>
			{ image && (
				<img
					alt={ __( 'WooPayments BNPL illustration', 'woocommerce' ) }
					src={ image }
					className="svg-background"
				/>
			) }
		</Card>
	);
};
