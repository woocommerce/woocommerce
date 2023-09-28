/**
 * Internal dependencies
 */
import { ColorPalette } from './types';

export const ColorPalettes = ( {
	colorPalettes,
}: {
	colorPalettes: ColorPalette[];
} ) => {
	return (
		<ul className="theme-card__color-palettes">
			{ colorPalettes.map( ( colorPalette ) => (
				<li key={ colorPalette.title }>
					<div
						className="theme-card__color-palette-left"
						style={ {
							border: colorPalette?.primary_border
								? '1px solid ' + colorPalette.primary_border
								: 'none',
							backgroundColor: colorPalette.primary,
						} }
					></div>
					<div
						className="theme-card__color-palette-right"
						style={ {
							border: colorPalette?.secondary_border
								? '1px solid ' + colorPalette.secondary_border
								: 'none',
							backgroundColor: colorPalette.secondary,
						} }
					></div>
				</li>
			) ) }
		</ul>
	);
};
