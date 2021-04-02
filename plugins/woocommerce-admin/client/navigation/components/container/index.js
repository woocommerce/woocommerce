/**
 * External dependencies
 */
import { useEffect, useMemo, useState, useRef } from '@wordpress/element';
import classnames from 'classnames';
import { Navigation } from '@woocommerce/experimental';
import { NAVIGATION_STORE_NAME, useUser } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { addHistoryListener } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getMappedItemsCategories, getMatchingItem } from '../../utils';
import Header from '../header';
import { PrimaryMenu } from './primary-menu';
import { SecondaryMenu } from './secondary-menu';

const Container = () => {
	const { menuItems } = useSelect( ( select ) => {
		return {
			menuItems: select( NAVIGATION_STORE_NAME ).getMenuItems(),
		};
	} );

	useEffect( () => {
		// Collapse the original WP Menu.
		document.documentElement.classList.remove( 'wp-toolbar' );
		document.body.classList.add( 'has-woocommerce-navigation' );
		const adminMenu = document.getElementById( 'adminmenumain' );

		if ( ! adminMenu ) {
			return;
		}

		adminMenu.classList.add( 'folded' );
	}, [] );

	const [ activeItem, setActiveItem ] = useState( 'woocommerce-home' );
	const [ activeLevel, setActiveLevel ] = useState( 'woocommerce' );

	useEffect( () => {
		const initialMatchedItem = getMatchingItem( menuItems );
		if ( initialMatchedItem && activeItem !== initialMatchedItem ) {
			setActiveItem( initialMatchedItem );
			setActiveLevel( initialMatchedItem.parent );
		}

		const removeListener = addHistoryListener( () => {
			setTimeout( () => {
				const matchedItem = getMatchingItem( menuItems );
				if ( matchedItem ) {
					setActiveItem( matchedItem );
					setActiveLevel( matchedItem.parent );
				}
			}, 0 );
		} );

		return removeListener;
	}, [ menuItems ] );

	const { currentUserCan } = useUser();

	const { categories, items } = useMemo(
		() => getMappedItemsCategories( menuItems, currentUserCan ),
		[ menuItems, currentUserCan ]
	);

	const navDomRef = useRef( null );

	const onBackClick = ( id ) => {
		recordEvent( 'navigation_back_click', {
			category: id,
		} );
	};

	const isRoot = activeLevel === 'woocommerce';

	const classes = classnames( 'woocommerce-navigation', {
		'is-root': isRoot,
	} );

	return (
		<div className={ classes }>
			<Header />
			<div className="woocommerce-navigation__wrapper" ref={ navDomRef }>
				<Navigation
					activeItem={ activeItem ? activeItem.id : null }
					activeMenu={ activeLevel }
					onActivateMenu={ ( ...args ) => {
						if ( navDomRef && navDomRef.current ) {
							navDomRef.current.scrollTop = 0;
						}

						setActiveLevel( ...args );
					} }
				>
					{ Object.values( categories ).map( ( category ) => {
						const categoryItems = items[ category.id ];

						return (
							!! categoryItems && [
								<PrimaryMenu
									key={ category.id }
									category={ category }
									onBackClick={ onBackClick }
									primaryItems={ [
										...categoryItems.primary,
										...categoryItems.favorites,
									] }
									pluginItems={ categoryItems.plugins }
								/>,
								<SecondaryMenu
									key={ `secondary/${ category.id }` }
									category={ category }
									onBackClick={ onBackClick }
									items={ categoryItems.secondary }
								/>,
							]
						);
					} ) }
				</Navigation>
			</div>
		</div>
	);
};

export default Container;
