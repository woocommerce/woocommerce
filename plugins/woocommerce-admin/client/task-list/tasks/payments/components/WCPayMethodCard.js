/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from 'interpolate-components';
import { Link, Pill } from '@woocommerce/components';
import { PAYMENT_GATEWAYS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useSelect } from '@wordpress/data';
import {
	WCPayCard,
	WCPayCardHeader,
	WCPayCardFooter,
	WCPayCardBody,
	SetupRequired,
} from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */

import { PaymentAction } from './PaymentAction';

const TosPrompt = () =>
	interpolateComponents( {
		mixedString: __(
			'Upon clicking "Get started", you agree to the {{link}}Terms of service{{/link}}. Next we’ll ask you to share a few details about your business to create your account.',
			'woocommerce-admin'
		),
		components: {
			link: (
				<Link
					href={ 'https://wordpress.com/tos/' }
					target="_blank"
					type="external"
				/>
			),
		},
	} );

export const WCPayMethodCard = ( { suggestion } ) => {
	const { description, id } = suggestion;
	const paymentGateway = useSelect( ( select ) => {
		return (
			select( PAYMENT_GATEWAYS_STORE_NAME ).getPaymentGateway( id ) || {}
		);
	} );
	const { enabled: isEnabled, needs_setup: needsSetup } = paymentGateway;

	return (
		<WCPayCard>
			<WCPayCardHeader>
				{ isEnabled ? (
					<SetupRequired />
				) : (
					<Pill>{ __( 'Recommended', 'woocommerce-admin' ) }</Pill>
				) }
			</WCPayCardHeader>

			<WCPayCardBody
				description={ description }
				onLinkClick={ () => {
					recordEvent( 'tasklist_payment_learn_more' );
				} }
			/>
			<WCPayCardFooter>
				<>
					<Text>
						<TosPrompt />
					</Text>
					<PaymentAction
						id={ id }
						hasSetup={ true }
						needsSetup={ needsSetup }
						isEnabled={ isEnabled }
						isRecommended={ true }
						setupButtonText={ __(
							'Get started',
							'woocommerce-admin'
						) }
					/>
				</>
			</WCPayCardFooter>
		</WCPayCard>
	);
};
