/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useContext, useEffect, useState } from '@wordpress/element';
import { Popover } from '@wordpress/components';
import { speak } from '@wordpress/a11y';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './quality-badge.scss';
import { Product } from '../product-list/types';
import { MarketplaceContext } from '../../contexts/marketplace-context';

/**
 * Seal-with-checkmark icon, kept identical to the badge icon on WooCommerce.com.
 */
export function QualityBadgeIcon( { size = 12 }: { size?: number } ) {
	return (
		<svg
			className="woocommerce-marketplace__quality-badge-icon"
			fill="none"
			height={ size }
			viewBox="0 0 16 16"
			width={ size }
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
		>
			<path
				d="M8.63342 0.528086L9.86941 0.105681C10.8022 -0.212938 11.8262 0.211045 12.2606 1.09624L12.836 2.26913C13.0272 2.65841 13.342 2.97356 13.7316 3.16473L14.9045 3.74014C15.7894 4.17422 16.2137 5.19853 15.8951 6.13135L15.4727 7.36734C15.3323 7.77776 15.3323 8.22319 15.4727 8.63361L15.8951 9.8696C16.2137 10.8024 15.7897 11.8264 14.9045 12.2608L13.7316 12.8362C13.3423 13.0274 13.0272 13.3422 12.836 13.7318L12.2606 14.9047C11.8265 15.7896 10.8022 16.2139 9.86941 15.8953L8.63342 15.4729C8.223 15.3325 7.77757 15.3325 7.36715 15.4729L6.13116 15.8953C5.19834 16.2139 4.17434 15.7899 3.73995 14.9047L3.16454 13.7318C2.97337 13.3425 2.65854 13.0274 2.26894 12.8362L1.09605 12.2608C0.211172 11.8267 -0.213126 10.8024 0.105492 9.8696L0.527898 8.63361C0.668279 8.22319 0.668279 7.77776 0.527898 7.36734L0.105492 6.13135C-0.213126 5.19853 0.210857 4.17453 1.09605 3.74014L2.26894 3.16473C2.65822 2.97356 2.97337 2.65873 3.16454 2.26913L3.73995 1.09624C4.17371 0.211045 5.19802 -0.213253 6.13085 0.105681L7.36683 0.528086C7.77725 0.668468 8.22269 0.668468 8.6331 0.528086H8.63342Z"
				fill="#1d2327"
			/>
			<path
				d="M7.31385 11.7143L4 7.98002L4.28348 7.14289L7.31385 8.81639L11.4267 4.95924L12.237 5.54286L7.31385 11.7143Z"
				fill="#fff"
			/>
		</svg>
	);
}

/**
 * The docs URL comes from the WooCommerce.com API, which sanitizes it
 * server-side; accept only absolute https URLs anyway before rendering it
 * as a link target.
 */
function getSafeDocsUrl( value?: string ): string | undefined {
	try {
		const url = new URL( value ?? '' );
		return url.protocol === 'https:' ? url.href : undefined;
	} catch {
		return undefined;
	}
}

/**
 * Popover with the badge explanation and, when the WooCommerce.com API
 * provides a docs URL, a "Learn more" link. Shared by the card chip and the
 * filter's info button; a popover (not a tooltip) so the link stays reachable
 * by pointer and keyboard.
 */
export function QualityBadgePopover( props: {
	label: string;
	tooltip: string;
	docsUrl?: string;
	anchor: Element | null;
	source: 'product_card' | 'filter';
	onClose: () => void;
} ) {
	const docsUrl = getSafeDocsUrl( props.docsUrl );
	const { tooltip, anchor, onClose } = props;

	// Screen readers do not announce the popover content on their own.
	useEffect( () => {
		speak( tooltip );
	}, [ tooltip ] );

	// Close when focus lands outside the popover and its trigger, without
	// pulling focus back, so tabbing away flows naturally. The Popover's own
	// focus-outside detection does not cover this: without a link, focus
	// never enters the popover at all.
	useEffect( () => {
		const onFocusin = ( event: FocusEvent ) => {
			const target = event.target as Element | null;
			if (
				! target ||
				target.closest(
					'.woocommerce-marketplace__quality-badge-popover'
				) ||
				anchor?.contains( target )
			) {
				return;
			}
			onClose();
		};

		document.addEventListener( 'focusin', onFocusin );
		return () => document.removeEventListener( 'focusin', onFocusin );
	}, [ anchor, onClose ] );

	return (
		<Popover
			className="woocommerce-marketplace__quality-badge-popover"
			anchor={ props.anchor }
			placement="bottom"
			// Focus the link when there is one; without it, focus stays on the
			// trigger and the content is announced via speak() above.
			focusOnMount={ docsUrl ? 'firstElement' : false }
			// Keep the popover in the page tab order; tabbing out closes it
			// via the focusin listener above.
			constrainTabbing={ false }
			onClose={ onClose }
		>
			<p>{ props.tooltip }</p>
			{ docsUrl && (
				<a
					href={ docsUrl }
					target="_blank"
					rel="noreferrer"
					aria-label={ sprintf(
						// translators: %s: name of the quality badge, supplied by the WooCommerce.com API (e.g. "Excellence Verified").
						__( 'Learn more about the %s badge', 'woocommerce' ),
						props.label
					) }
					onClick={ () =>
						recordEvent(
							'marketplace_quality_badge_learn_more_clicked',
							{ source: props.source }
						)
					}
					onKeyDown={ ( event ) => {
						// Escape returns focus to the trigger. Tab would leave
						// through the portal to the end of the document, so
						// hand focus back to the trigger and let the browser
						// continue the tab order from there.
						if ( event.key === 'Escape' || event.key === 'Tab' ) {
							onClose();
							( anchor as HTMLElement | null )?.focus();
						}
					} }
				>
					{ __( 'Learn more', 'woocommerce' ) }
				</a>
			) }
		</Popover>
	);
}

/**
 * Quality badge chip shown on product cards. Whether it renders and with what
 * copy is fully driven by the WooCommerce.com API: the per-product flag comes
 * with the product data, the label/tooltip/docs URL from the IAM settings
 * endpoint. Clicking the chip opens the explanation popover.
 */
export default function QualityBadge( props: { product: Product } ) {
	const { iamSettings } = useContext( MarketplaceContext );
	const [ isOpen, setIsOpen ] = useState( false );
	const [ anchor, setAnchor ] = useState< HTMLButtonElement | null >( null );

	const badge = iamSettings?.quality_badge;

	if (
		! badge?.enabled ||
		! badge.label ||
		! props.product.hasQualityBadge
	) {
		return null;
	}

	const chipContent = (
		<>
			<QualityBadgeIcon />
			{ badge.label }
		</>
	);

	// Without explanation copy the chip is a plain, inert label.
	if ( ! badge.tooltip ) {
		return (
			<div className="woocommerce-marketplace__quality-badge">
				<span className="woocommerce-marketplace__quality-badge__chip">
					{ chipContent }
				</span>
			</div>
		);
	}

	return (
		<div className="woocommerce-marketplace__quality-badge">
			<button
				ref={ setAnchor }
				type="button"
				className="woocommerce-marketplace__quality-badge__chip"
				aria-expanded={ isOpen }
				onClick={ () => setIsOpen( ! isOpen ) }
				onKeyDown={ ( event ) => {
					// Focus stays on the trigger when the popover has no link.
					if ( event.key === 'Escape' && isOpen ) {
						setIsOpen( false );
					}
				} }
			>
				{ chipContent }
			</button>
			{ isOpen && (
				<QualityBadgePopover
					label={ badge.label }
					tooltip={ badge.tooltip }
					docsUrl={ badge.docs_url }
					anchor={ anchor }
					source="product_card"
					onClose={ () => setIsOpen( false ) }
				/>
			) }
		</div>
	);
}
