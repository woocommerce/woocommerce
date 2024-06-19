/**
 * External dependencies
 */
import { Dropdown } from '@wordpress/components';
import { chevronDown, chevronUp, Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import clsx from 'clsx';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Category } from './types';
import { ProductType } from '../product-list/types';

function DropdownContent( props: {
	readonly categories: Category[];
	readonly selected?: Category;
	readonly onClick: () => void;
} ): JSX.Element {
	function updateCategorySelection(
		event: React.MouseEvent< HTMLButtonElement >
	) {
		const slug = event.currentTarget.value;

		if ( ! slug ) {
			return;
		}

		/**
		 * Trigger the onClick event on the parent component to close the dropdown.
		 * This closes the dropdown automatically when a user clicks on an item.
		 */
		props.onClick();

		navigateTo( {
			url: getNewPath( { category: slug } ),
		} );
	}

	return (
		<ul className="woocommerce-marketplace__category-dropdown-list">
			{ props.categories.map( ( category ) => (
				<li
					className="woocommerce-marketplace__category-dropdown-item"
					key={ category.slug }
				>
					<button
						className={ clsx(
							'woocommerce-marketplace__category-dropdown-item-button',
							{
								'woocommerce-marketplace__category-dropdown-item-button--selected':
									category.slug === props.selected?.slug,
							}
						) }
						value={ category.slug }
						onClick={ updateCategorySelection }
					>
						{ category.label }
					</button>
				</li>
			) ) }
		</ul>
	);
}

type CategoryDropdownProps = {
	label: string;
	categories: Category[];
	className?: string;
	buttonClassName?: string;
	contentClassName?: string;
	arrowIconSize?: number;
	selected?: Category;
	type?: ProductType;
};

export default function CategoryDropdown(
	props: CategoryDropdownProps
): JSX.Element {
	function dropDownTracksEvent() {
		recordEvent( 'marketplace_category_dropdown_opened', {
			type: props.type,
		} );
	}

	return (
		<Dropdown
			renderToggle={ ( { isOpen, onToggle } ) => (
				<button
					onClick={ () => {
						if ( ! isOpen ) {
							dropDownTracksEvent();
						}
						onToggle();
					} }
					className={ props.buttonClassName }
					aria-label={ __(
						'Toggle category dropdown',
						'woocommerce'
					) }
				>
					{ props.label }
					<Icon
						icon={ isOpen ? chevronUp : chevronDown }
						size={ props.arrowIconSize }
					/>
				</button>
			) }
			className={ props.className }
			renderContent={ ( { onToggle } ) => (
				<DropdownContent
					categories={ props.categories }
					selected={ props.selected }
					onClick={ onToggle }
				/>
			) }
			contentClassName={ props.contentClassName }
		/>
	);
}
