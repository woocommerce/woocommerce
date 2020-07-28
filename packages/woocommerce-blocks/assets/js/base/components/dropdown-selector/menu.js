/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';

const DropdownSelectorMenu = ( {
	checked,
	getItemProps,
	getMenuProps,
	highlightedIndex,
	options,
} ) => {
	return (
		<ul
			{ ...getMenuProps( {
				className:
					'wc-block-dropdown-selector__list wc-block-components-dropdown-selector__list',
			} ) }
		>
			{ options.map( ( option, index ) => {
				const selected = checked.includes( option.value );
				return (
					// eslint-disable-next-line react/jsx-key
					<li
						{ ...getItemProps( {
							key: option.value,
							className: classNames(
								'wc-block-dropdown-selector__list-item',
								'wc-block-components-dropdown-selector__list-item',
								{
									'is-selected': selected,
									'is-highlighted':
										highlightedIndex === index,
								}
							),
							index,
							item: option.value,
							'aria-label': selected
								? sprintf(
										__(
											'Remove %s filter',
											'woocommerce'
										),
										option.name
								  )
								: null,
						} ) }
					>
						{ option.label }
					</li>
				);
			} ) }
		</ul>
	);
};

export default DropdownSelectorMenu;
