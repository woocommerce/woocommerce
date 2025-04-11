/**
 * External dependencies
 */
import * as React from '@wordpress/element';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const CategoryMenu = ( {
	groupedTags,
	activeCategory,
	onCategorySelect,
}: {
	groupedTags: Record< string, unknown[] >;
	activeCategory: string | null;
	onCategorySelect: ( category: string | null ) => void;
} ) => {
	const getMenuItemClass = ( category: string | null ) =>
		category === activeCategory
			? 'woocommerce-personalization-tags-modal-menu-item-active'
			: '';

	return (
		<MenuGroup className="woocommerce-personalization-tags-modal-menu">
			<MenuItem
				onClick={ () => onCategorySelect( null ) }
				className={ getMenuItemClass( null ) }
			>
				{ __( 'All', 'woocommerce' ) }
			</MenuItem>
			<div
				className="woocommerce-personalization-tags-modal-menu-separator"
				aria-hidden="true"
			></div>
			{ Object.keys( groupedTags ).map( ( category, index, array ) => (
				<React.Fragment key={ category }>
					<MenuItem
						onClick={ () => onCategorySelect( category ) }
						className={ getMenuItemClass( category ) }
					>
						{ category }
					</MenuItem>
					{ index < array.length - 1 && (
						<div
							className="woocommerce-personalization-tags-modal-menu-separator"
							aria-hidden="true"
						></div>
					) }
				</React.Fragment>
			) ) }
		</MenuGroup>
	);
};

export { CategoryMenu };
