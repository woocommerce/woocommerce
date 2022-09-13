/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

interface WrongUserConnectedPageProps {
	wordpressAccountEmailAddress?: string | undefined;
}

export const WrongUserConnectedPage: React.FC<
	WrongUserConnectedPageProps
> = () => {
	// The user shouldn't see this screen at all unless he's messing with the page URL manually to get here when he's not allowed to
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
