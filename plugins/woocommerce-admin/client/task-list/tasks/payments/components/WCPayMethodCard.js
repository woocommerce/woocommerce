/**
 * External dependencies
 */
import {
	WCPayCard,
	WCPayCardHeader,
	WCPayCardFooter,
	WCPayCardBody,
	SetupRequired,
} from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { Link, Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import interpolateComponents from 'interpolate-components';

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

const ButtonComponent = ( {
	method: { key: methodKey, isConfigured, loading, onClick },
	isEnabled,
} ) => (
	<PaymentAction
		methodKey={ methodKey }
		hasSetup={ true }
		isConfigured={ isConfigured }
		isEnabled={ isEnabled }
		isRecommended={ true }
		isLoading={ loading }
		onSetup={ () => {} }
		onSetupCallback={ onClick }
		setupButtonText={ __( 'Get started', 'woocommerce-admin' ) }
		onManageButtonClick={ () => recordEvent( 'tasklist_payment_manage' ) }
	/>
);

export const WCPayMethodCard = ( { method, isEnabled } ) => {
	const { description } = method;

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
					<ButtonComponent
						isEnabled={ isEnabled }
						method={ method }
					/>
				</>
			</WCPayCardFooter>
		</WCPayCard>
	);
};
