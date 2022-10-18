/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { NAVIGATION_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { HighlightTooltip } from '~/activity-panel/highlight-tooltip';

const tooltipHiddenOption = 'woocommerce_navigation_favorites_tooltip_hidden';

export const FavoritesTooltip = () => {
	const { isFavoritesResolving, isOptionResolving, isTooltipHidden } =
		useSelect( ( select ) => {
			const { getOption, isResolving } = select( OPTIONS_STORE_NAME );
			return {
				isFavoritesResolving: select(
					NAVIGATION_STORE_NAME
				).isResolving( 'getFavorites' ),
				isOptionResolving: isResolving( 'getOption', [
					tooltipHiddenOption,
				] ),
				isTooltipHidden: getOption( tooltipHiddenOption ) === 'yes',
			};
		} );

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	if ( isFavoritesResolving || isTooltipHidden || isOptionResolving ) {
		return null;
	}

	if ( document.body.classList.contains( 'is-wc-nav-folded' ) ) {
		return null;
	}

	return (
		<HighlightTooltip
			delay={ 1000 }
			title={ __( 'Introducing favorites', 'woocommerce' ) }
			content={ __(
				'You can now favorite your extensions to pin them in the top level of the navigation.',
				'woocommerce'
			) }
			closeButtonText={ __( 'Got it', 'woocommerce' ) }
			id="woocommerce-navigation-favorite-button"
			onClose={ () =>
				updateOptions( {
					[ tooltipHiddenOption ]: 'yes',
				} )
			}
			useAnchor
		/>
	);
};

export default FavoritesTooltip;
