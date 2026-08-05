/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default function LockLabel( { message }: { message: string } ) {
	return (
		<div className="woocommerce-fulfillment-lock-label">
			<span className="woocommerce-fulfillment-lock-label__icon">
				<svg
					width="10"
					height="15"
					viewBox="0 0 10 15"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					aria-hidden="true"
				>
					<path d="M9.16667 6.33341H8.16667V3.83341C8.16667 2.08341 6.75 0.666748 5 0.666748C3.25 0.666748 1.83333 2.08341 1.83333 3.83341V6.33341H0.833333C0.333333 6.33341 0 6.66675 0 7.16675V13.8334C0 14.3334 0.333333 14.6667 0.833333 14.6667H9.16667C9.66667 14.6667 10 14.3334 10 13.8334V7.16675C10 6.66675 9.66667 6.33341 9.16667 6.33341ZM3.16667 3.83341C3.16667 2.83341 4 2.00008 5 2.00008C6 2.00008 6.83333 2.83341 6.83333 3.83341V6.33341H3.16667V3.83341ZM8.75 13.4167H1.25V7.58341H8.75V13.4167Z" />
				</svg>
			</span>
			<span className="woocommerce-fulfillment-lock-label__text">
				{ message ||
					__(
						'This item is locked and cannot be edited.',
						'woocommerce'
					) }
			</span>
		</div>
	);
}
