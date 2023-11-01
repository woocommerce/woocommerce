/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FrontSide } from './imgs/front-side';
import { CloseUp } from './imgs/close-up';
import { Variants } from './imgs/variants';
import { LifestyleScene } from './imgs/lifestyle-scene';

export function PlaceHolder() {
	const placeHolderImages = [
		{ id: 'front-side', image: <FrontSide />, text: 'Front side' },
		{ id: 'close-up', image: <CloseUp />, text: 'Close-up' },
		{ id: 'variants', image: <Variants />, text: 'Variants' },
		{
			id: 'lifestyle-scene',
			image: <LifestyleScene />,
			text: 'Lifestyle scene',
		},
	];

	return (
		<div className="woocommerce-image-placeholder__wrapper">
			{ placeHolderImages.map( ( { id, image, text } ) => {
				return (
					<div
						key={ id }
						className="woocommerce-image-placeholder__item"
					>
						{ image }
						<p>{ text }</p>
					</div>
				);
			} ) }
		</div>
	);
}
