/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './grid-item-placeholder.scss';

/**
 * A placeholder component for a grid item in the payment gateways section.
 * This component is typically used to indicate loading or empty states
 * while the actual grid item content is being fetched or rendered.
 */
export const GridItemPlaceholder = () => {
	return (
		<div className="other-payment-gateways__content__grid-item">
			<div className="grid-item-placeholder__img" />
			<div className="other-payment-gateways__content__grid-item__content grid-item-placeholder__content">
				<span className="grid-item-placeholder__title"></span>
				<span className="grid-item-placeholder__description"></span>
				<div className="grid-item-placeholder__actions"></div>
			</div>
		</div>
	);
};
