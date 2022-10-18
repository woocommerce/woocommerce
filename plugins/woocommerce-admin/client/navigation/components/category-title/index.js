/**
 * Internal dependencies
 */
import './style.scss';
import { FavoriteButton } from '../favorite-button';
import { FavoritesTooltip } from '../favorites-tooltip';

export const CategoryTitle = ( { category } ) => {
	const { id, menuId, title } = category;

	const className = 'woocommerce-navigation-category-title';

	if ( [ 'plugins', 'favorites' ].includes( menuId ) ) {
		return (
			<span className={ className }>
				<span className={ `${ className }__text` }>{ title }</span>
				<FavoriteButton id={ id } />
				<FavoritesTooltip />
			</span>
		);
	}

	return <span className={ className }>{ title }</span>;
};

export default CategoryTitle;
