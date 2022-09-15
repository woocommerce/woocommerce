/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const SendMagicLinkButton = ( {
	onClickHandler,
}: {
	onClickHandler: () => void;
} ) => (
	<Button className="send-magic-link-button" onClick={ onClickHandler }>
		{ __( '✨️ Send the sign-in link', 'woocommerce' ) }
	</Button>
);
