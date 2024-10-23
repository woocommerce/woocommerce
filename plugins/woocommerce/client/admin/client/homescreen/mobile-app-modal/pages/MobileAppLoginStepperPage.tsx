/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import { SendMagicLinkStates } from '../components';
import { MobileAppLoginStepper } from '../components/MobileAppLoginStepper';

interface MobileAppLoginStepperPageProps {
	appInstalledClicked: boolean;
	isJetpackPluginInstalled: boolean;
	wordpressAccountEmailAddress: string | undefined;
	completeInstallationHandler: () => void;
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
}

export const MobileAppLoginStepperPage: React.FC<
	MobileAppLoginStepperPageProps
> = ( {
	appInstalledClicked,
	isJetpackPluginInstalled,
	wordpressAccountEmailAddress,
	completeInstallationHandler,
	sendMagicLinkHandler,
	sendMagicLinkStatus,
} ) => (
	<ModalContentLayoutWithTitle>
		<div className="modal-subheader">
			<h3>
				{ __(
					'Run your store from anywhere in two easy steps.',
					'woocommerce'
				) }
			</h3>
		</div>
		<MobileAppLoginStepper
			step={ appInstalledClicked ? 'second' : 'first' }
			isJetpackPluginInstalled={ isJetpackPluginInstalled }
			wordpressAccountEmailAddress={ wordpressAccountEmailAddress }
			completeInstallationStepHandler={ completeInstallationHandler }
			sendMagicLinkHandler={ sendMagicLinkHandler }
			sendMagicLinkStatus={ sendMagicLinkStatus }
		/>
	</ModalContentLayoutWithTitle>
);
