/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Link, Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
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

import { Action } from '../Action';

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

export const Suggestion = ( { paymentGateway, onSetupCallback = null } ) => {
	const {
		description,
		id,
		needsSetup,
		installed,
		enabled: isEnabled,
		installed: isInstalled,
	} = paymentGateway;

	return (
		<WCPayCard>
			<WCPayCardHeader>
				{ installed && needsSetup ? (
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
					<Text lineHeight="1.5em">
						<TosPrompt />
					</Text>
					<Action
						id={ id }
						hasSetup={ true }
						needsSetup={ needsSetup }
						isEnabled={ isEnabled }
						isRecommended={ true }
						isInstalled={ isInstalled }
						hasPlugins={ true }
						setupButtonText={ __(
							'Get started',
							'woocommerce-admin'
						) }
						onSetupCallback={ onSetupCallback }
					/>
				</>
			</WCPayCardFooter>
		</WCPayCard>
	);
};
