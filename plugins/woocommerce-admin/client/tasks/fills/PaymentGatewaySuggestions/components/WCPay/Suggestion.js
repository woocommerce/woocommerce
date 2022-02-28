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
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */

import { Action } from '../Action';
import { connectWcpay } from './utils';

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

	const { createNotice } = useDispatch( 'core/notices' );
	// When the WC Pay is installed and onSetupCallback is null
	// Overwrite onSetupCallback to redirect to the setup page
	// when the user clicks on the "Finish setup" button.
	// WC Pay doesn't need to be configured in WCA.
	// It should be configured in its onboarding flow.
	if ( installed && onSetupCallback === null ) {
		onSetupCallback = () => {
			connectWcpay( createNotice );
		};
	}

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
