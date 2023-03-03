/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

export const SendMagicLinkButton = ( {
	onClickHandler,
	isFetching,
}: {
	onClickHandler: () => void;
	isFetching: boolean;
} ) => (
	<Button className="send-magic-link-button" onClick={ onClickHandler }>
		{ isFetching && <Spinner className="send-magic-link-spinner" /> }
		<div
			style={ {
				visibility: isFetching ? 'hidden' : 'visible',
			} }
			className="send-magic-link-button-contents"
		>
			<div className="send-magic-link-button-text">
				{ __( '✨️ Send the sign-in link', 'woocommerce' ) }
			</div>
		</div>
	</Button>
);
