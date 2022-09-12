/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { JetpackInstallationStepper } from '../components/JetpackInstallationStepper';
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';

interface JetpackInstallStepperPageProps {
	isReturningFromWordpressConnection: boolean;
	sendMagicLinkHandler: () => void;
}

export const JetpackInstallStepperPage: React.FC<
	JetpackInstallStepperPageProps
> = ( { isReturningFromWordpressConnection, sendMagicLinkHandler } ) => {
	return (
		<ModalContentLayoutWithTitle>
			<div className="modal-subheader">
				<h3>
					{ __(
						'Run your store from anywhere in two easy steps.',
						'woocommerce'
					) }
				</h3>
			</div>
			<JetpackInstallationStepper
				step={ isReturningFromWordpressConnection ? 'second' : 'first' }
				sendMagicLinkHandler={ sendMagicLinkHandler }
			/>
		</ModalContentLayoutWithTitle>
	);
};
