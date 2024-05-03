/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Notice, ExternalLink } from '@wordpress/components';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { Alert } from '@woocommerce/icons';
import { Icon, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useCombinedIncompatibilityNotice } from './use-combined-incompatibility-notice';
import { SwitchToClassicShortcodeButton } from '../switch-to-classic-shortcode-button';
import './editor.scss';

interface ExtensionNoticeProps {
	toggleDismissedStatus: ( status: boolean ) => void;
	block: 'woocommerce/cart' | 'woocommerce/checkout';
	clientId: string;
}

/**
 * Shows a notice when there are incompatible extensions.
 *
 * Tracks events:
 * - switch_to_classic_shortcode_click
 * - switch_to_classic_shortcode_confirm
 * - switch_to_classic_shortcode_cancel
 * - switch_to_classic_shortcode_undo
 */
export function IncompatibleExtensionsNotice( {
	toggleDismissedStatus,
	block,
	clientId,
}: ExtensionNoticeProps ) {
	const [
		isVisible,
		dismissNotice,
		incompatibleExtensions,
		incompatibleExtensionsCount,
	] = useCombinedIncompatibilityNotice( block );

	useEffect( () => {
		toggleDismissedStatus( ! isVisible );
	}, [ isVisible, toggleDismissedStatus ] );

	if ( ! isVisible ) {
		return null;
	}

	const noticeContent = (
		<>
			{ incompatibleExtensionsCount > 1
				? createInterpolateElement(
						__(
							'Some active extensions do not yet support this block. This may impact the shopper experience. <a>Learn more</a>',
							'woocommerce'
						),
						{
							a: (
								<ExternalLink href="https://woo.com/document/cart-checkout-blocks-status/" />
							),
						}
				  )
				: createInterpolateElement(
						sprintf(
							// translators: %s is the name of the extension.
							__(
								'<strong>%s</strong> does not yet support this block. This may impact the shopper experience. <a>Learn more</a>',
								'woocommerce'
							),
							Object.values( incompatibleExtensions )[ 0 ]
						),
						{
							strong: <strong />,
							a: (
								<ExternalLink href="https://woo.com/document/cart-checkout-blocks-status/" />
							),
						}
				  ) }
		</>
	);

	const entries = Object.entries( incompatibleExtensions );
	const remainingEntries = entries.length - 2;

	return (
		<Notice
			className="wc-blocks-incompatible-extensions-notice"
			status={ 'warning' }
			onRemove={ dismissNotice }
			spokenMessage={ noticeContent }
		>
			<div className="wc-blocks-incompatible-extensions-notice__content">
				<Icon
					className="wc-blocks-incompatible-extensions-notice__warning-icon"
					icon={ <Alert /> }
				/>
				<div>
					<p>{ noticeContent }</p>
					{ incompatibleExtensionsCount > 1 && (
						<ul>
							{ entries.slice( 0, 2 ).map( ( [ id, title ] ) => (
								<li
									key={ id }
									className="wc-blocks-incompatible-extensions-notice__element"
								>
									{ title }
								</li>
							) ) }
						</ul>
					) }

					{ entries.length > 2 && (
						<details>
							<summary>
								<span>
									{ sprintf(
										// translators: %s is the number of incompatible extensions.
										_n(
											'%s more incompatibility',
											'%s more incompatibilites',
											remainingEntries,
											'woocommerce'
										),
										remainingEntries
									) }
								</span>
								<Icon icon={ chevronDown } />
							</summary>
							<ul>
								{ entries.slice( 2 ).map( ( [ id, title ] ) => (
									<li
										key={ id }
										className="wc-blocks-incompatible-extensions-notice__element"
									>
										{ title }
									</li>
								) ) }
							</ul>
						</details>
					) }
					<SwitchToClassicShortcodeButton
						block={ block }
						clientId={ clientId }
						type={ 'incompatible' }
					/>
				</div>
			</div>
		</Notice>
	);
}
