/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import { SendMagicLinkButton } from '../components';

interface JetpackAlreadyInstalledPageProps {
	wordpressAccountEmailAddress: string | undefined;
	sendMagicLinkHandler: () => void;
}

export const JetpackAlreadyInstalledPage: React.FC<
	JetpackAlreadyInstalledPageProps
> = ( { wordpressAccountEmailAddress, sendMagicLinkHandler } ) => {
	return (
		<ModalContentLayoutWithTitle>
			<>
				<div className="modal-subheader jetpack-already-installed">
					<h3>
						{ sprintf(
							/* translators: %s: user's WordPress.com account email address */
							__(
								'We’ll send a magic link to %s. Open it on your smartphone or tablet to sign into your store instantly.',
								'woocommerce'
							),
							wordpressAccountEmailAddress
						) }
					</h3>
				</div>
				<SendMagicLinkButton onClickHandler={ sendMagicLinkHandler } />
			</>
		</ModalContentLayoutWithTitle>
	);
};
