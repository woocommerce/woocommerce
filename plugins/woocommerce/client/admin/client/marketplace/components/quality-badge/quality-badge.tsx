/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';

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
 * Quality badge chip shown on product cards. Whether it renders and with what
 * copy is fully driven by the WooCommerce.com API: the per-product flag comes
 * with the product data, the label/tooltip from the IAM settings endpoint.
 */
export default function QualityBadge( props: { product: Product } ) {
	const { iamSettings } = useContext( MarketplaceContext );
	const badge = iamSettings?.quality_badge;

	if (
		! badge?.enabled ||
		! badge.label ||
		! props.product.hasQualityBadge
	) {
		return null;
	}

	const chip = (
		<span
			className="woocommerce-marketplace__quality-badge__chip"
			// Focusable only when there is a tooltip to reveal.
			tabIndex={ badge.tooltip ? 0 : undefined }
		>
			<QualityBadgeIcon />
			{ badge.label }
		</span>
	);

	return (
		<div className="woocommerce-marketplace__quality-badge">
			{ badge.tooltip ? (
				<Tooltip text={ badge.tooltip }>{ chip }</Tooltip>
			) : (
				chip
			) }
		</div>
	);
}
