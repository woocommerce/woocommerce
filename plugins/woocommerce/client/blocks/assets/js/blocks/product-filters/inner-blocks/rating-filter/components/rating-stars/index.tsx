/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/components';

export type RatingStarsProps = {
	stars?: number;
	size?: number;
	color?: string;
	gap?: number;
};

/**
 * Render a set of rating stars using a single SVG element.
 *
 * ```tsx
 * <RatingStars stars={ 5 } size={ 24 } color="black" gap={ 4 } />
 * ```
 *
 * @param {RatingStarsProps} props - The component props.
 * @return {JSX.Element} The rendered component.
 */
export default function RatingStars( {
	stars = 5,
	size = 24,
	color = 'currentColor',
	gap = 0,
}: RatingStarsProps ): JSX.Element {
	const starPath =
		'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
	const viewBoxWidth = stars * size + ( stars - 1 ) * gap;

	return (
		<SVG
			width={ viewBoxWidth }
			height={ size }
			viewBox={ `0 0 ${ viewBoxWidth } 24` }
			fill={ color }
			aria-hidden="true"
			focusable="false"
		>
			{ Array.from( { length: stars }, ( _, index ) => (
				<Path
					key={ index }
					d={ starPath }
					transform={ `translate(${ index * ( size + gap ) }, 0)` }
				/>
			) ) }
		</SVG>
	);
}
