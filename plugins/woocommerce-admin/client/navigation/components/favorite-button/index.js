/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { NAVIGATION_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { Icon, starEmpty, starFilled } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

export const FavoriteButton = ( { id } ) => {
	const { favorites, isResolving } = useSelect( ( select ) => {
		return {
			favorites: select( NAVIGATION_STORE_NAME ).getFavorites(),
			isResolving: select( NAVIGATION_STORE_NAME ).isResolving(
				'getFavorites'
			),
		};
	} );

	const { addFavorite, removeFavorite } = useDispatch(
		NAVIGATION_STORE_NAME
	);

	const isFavorited = favorites.includes( id );

	const toggleFavorite = () => {
		const toggle = isFavorited ? removeFavorite : addFavorite;
		toggle( id );
		recordEvent( 'navigation_favorite', {
			id,
			action: isFavorited ? 'unfavorite' : 'favorite',
		} );
	};

	if ( isResolving ) {
		return null;
	}

	return (
		<Button
			id="woocommerce-navigation-favorite-button"
			className="woocommerce-navigation-favorite-button"
			isTertiary
			onClick={ toggleFavorite }
			aria-label={
				isFavorited
					? __( 'Add this item to your favorites.', 'woocommerce' )
					: __(
							'Remove this item from your favorites.',
							'woocommerce'
					  )
			}
		>
			<Icon
				icon={ isFavorited ? starFilled : starEmpty }
				className={ `star-${ isFavorited ? 'filled' : 'empty' }-icon` }
			/>
		</Button>
	);
};

export default FavoriteButton;
