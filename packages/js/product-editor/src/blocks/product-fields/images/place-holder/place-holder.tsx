/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FrontSide } from './imgs/front-side';
import { CloseUp } from './imgs/close-up';
import { Variants } from './imgs/variants';
import { LifestyleScene } from './imgs/lifestyle-scene';

export function PlaceHolder() {
	const placeHolderImages = [
		{
			id: 'front-side',
			image: <FrontSide />,
			text: __( 'Front side', 'woocommerce' ),
		},
		{
			id: 'close-up',
			image: <CloseUp />,
			text: __( 'Close-up', 'woocommerce' ),
		},
		{
			id: 'variants',
			image: <Variants />,
			text: __( 'Variants', 'woocommerce' ),
		},
		{
			id: 'lifestyle-scene',
			image: <LifestyleScene />,
			text: __( 'Lifestyle scene', 'woocommerce' ),
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
