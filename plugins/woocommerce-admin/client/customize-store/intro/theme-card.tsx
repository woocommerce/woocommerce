/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ThemeCard as TypeThemeCard } from './types';
import { ColorPalettes } from './color-palettes';

export const ThemeCard = ( {
	slug,
	description,
	image,
	name,
	colorPalettes = [],
	link = '',
	isActive = false,
}: TypeThemeCard ) => {
	return (
		<div className="theme-card" key={ slug }>
			<div>
				{ link ? (
					<Link href={ link }>
						<img src={ image } alt={ description } />
					</Link>
				) : (
					<img src={ image } alt={ description } />
				) }
			</div>
			<div className="theme-card__info">
				<h2 className="theme-card__title">{ name }</h2>
				{ colorPalettes && (
					<ColorPalettes colorPalettes={ colorPalettes } />
				) }
			</div>
			<div>
				{ isActive && (
					<span className="theme-card__active">
						{ __( 'Active theme', 'woocommerce' ) }
					</span>
				) }
				<span className="theme-card__free">Free</span>
			</div>
		</div>
	);
};
