/**
 * External dependencies
 */
import { Notice, ExternalLink } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useCompatibilityNotice } from './use-compatibility-notice';
import { SwitchToClassicShortcodeButton } from '../switch-to-classic-shortcode-button';

interface CartCheckoutSidebarCompatibilityNoticeProps {
	block: 'cart' | 'checkout';
	clientId: string;
}

export const CartCheckoutSidebarCompatibilityNotice = ( {
	block,
	clientId,
}: CartCheckoutSidebarCompatibilityNoticeProps ) => {
	const [ isVisible, dismissNotice ] = useCompatibilityNotice( block );

	if ( ! isVisible ) {
		return null;
	}

	const noticeText = createInterpolateElement(
		__(
			"Some extensions don't yet support this block, which may impact the shopper experience. <a>Learn more</a>",
			'woocommerce'
		),
		{
			a: (
				// Suppress the warning as this <a> will be interpolated into the string with content.
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<ExternalLink href="https://woocommerce.com/document/cart-checkout-blocks-support-status/#section-3" />
			),
		}
	);

	const blockName =
		block === 'cart' ? 'woocommerce/cart' : 'woocommerce/checkout';

	return (
		<Notice
			onRemove={ dismissNotice }
			className={ classnames( [
				'wc-blocks-sidebar-compatibility-notice',
				{ 'is-hidden': ! isVisible },
			] ) }
		>
			{ noticeText }
			<SwitchToClassicShortcodeButton
				block={ blockName }
				clientId={ clientId }
				type={ 'generic' }
			/>
		</Notice>
	);
};
