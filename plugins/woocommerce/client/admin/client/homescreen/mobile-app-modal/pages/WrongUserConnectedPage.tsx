/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from '@wordpress/element';

interface WrongUserConnectedPageProps {
	wordpressAccountEmailAddress?: string | undefined;
}

export const WrongUserConnectedPage: React.FC<
	WrongUserConnectedPageProps
> = () => {
	useEffect( () => {
		recordEvent( 'magic_prompt_mismatched_wpcom_user_view' );
	}, [] );

	// The user may see this screen if he clicks on the additional task and there is already another wpcom user connected to this site.
	return (
		<div className="wrong-user-connected-modal-body">
			<div className="wrong-user-connected-title">
				<h1>{ __( 'Oops!', 'woocommerce' ) }</h1>
			</div>
			<div className="wrong-user-connected-subheader-spacer">
				<div className="wrong-user-connected-subheader">
					{ __(
						'It looks like this site is connected to another WordPress.com account.',
						'woocommerce'
					) }
				</div>
				<br />
				<div className="wrong-user-connected-subheader">
					{ __(
						'WooCommerce Mobile App can currently only be registered to a single WordPress.com account.',
						'woocommerce'
					) }
				</div>
			</div>
		</div>
	);
};
