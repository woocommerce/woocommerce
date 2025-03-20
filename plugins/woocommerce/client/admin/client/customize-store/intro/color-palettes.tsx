/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ColorPalette } from './types';

const MAX_COLOR_PALETTES = 4;

export const ColorPalettes = ( {
	colorPalettes,
	totalPalettes,
}: {
	colorPalettes: ColorPalette[];
	totalPalettes: number;
} ) => {
	const canFit = totalPalettes <= MAX_COLOR_PALETTES;

	const descriptionId = useInstanceId(
		ColorPalettes,
		'color-palettes-description'
	) as string;

	function renderMore() {
		if ( canFit ) return null;
		return (
			<li aria-hidden="true" className="more_palettes">
				+{ totalPalettes - 4 }
			</li>
		);
	}

	function renderDescription() {
		if ( canFit ) return null;
		return (
			<p
				id={ descriptionId }
				className="theme-card__color-palettes-description"
			>
				{ sprintf(
					/* translators: $d is the total amount of color palettes */
					__(
						'There are a total of %d color palettes',
						'woocommerce'
					),
					totalPalettes
				) }
			</p>
		);
	}

	return (
		<>
			<ul
				className="theme-card__color-palettes"
				aria-label={ __( 'Color palettes', 'woocommerce' ) }
				aria-describedby={ descriptionId }
			>
				{ colorPalettes.map( ( colorPalette ) => (
					<li
						key={ colorPalette.title }
						aria-label={ colorPalette.title }
						style={ {
							background:
								'linear-gradient(to right, ' +
								colorPalette.primary +
								' 0px, ' +
								colorPalette.primary +
								' 50%, ' +
								colorPalette.secondary +
								' 50%, ' +
								colorPalette.secondary +
								' 100%' +
								')',
						} }
					/>
				) ) }
				{ renderMore() }
			</ul>

			{ renderDescription() }
		</>
	);
};
