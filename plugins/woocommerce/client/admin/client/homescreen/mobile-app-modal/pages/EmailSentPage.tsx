/**
 * External dependencies
 */
import interpolateComponents from '@automattic/interpolate-components';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

interface EmailSentProps {
	returnToSendLinkPage: () => void;
}

export const EmailSentPage: React.FC< EmailSentProps > = ( {
	returnToSendLinkPage: returnToSendLinkPage,
} ) => {
	return (
		<div className="email-sent-modal-body">
			<div className="email-sent-illustration"></div>
			<div className="email-sent-title">
				<h1>{ __( 'Check your email!', 'woocommerce' ) }</h1>
			</div>
			<div className="email-sent-subheader-spacer">
				<div className="email-sent-subheader">
					{ __(
						'We just sent you the magic link. Open it on your mobile device and follow the instructions.',
						'woocommerce'
					) }
				</div>
			</div>
			<div className="email-sent-footer">
				<div className="email-sent-footer-prompt">
					{ __( 'DIDN’T GET IT?', 'woocommerce' ) }
				</div>
				<div className="email-sent-footer-text">
					{ interpolateComponents( {
						mixedString: __(
							'Check your spam/junk email folder or {{ sendAnotherLink /}}.',
							'woocommerce'
						),
						components: {
							sendAnotherLink: (
								<Button
									className="email-sent-send-another-link"
									onClick={ () => {
										returnToSendLinkPage();
									} }
								>
									{ __( 'send another link', 'woocommerce' ) }
								</Button>
							),
						},
					} ) }
				</div>
			</div>
		</div>
	);
};
