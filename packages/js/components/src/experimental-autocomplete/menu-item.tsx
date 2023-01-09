/**
 * External dependencies
 */
import interpolateComponents from '@automattic/interpolate-components';
import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';
import classNames from 'classnames';
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { useMenuItem } from './hooks/use-menu-item';
import { Menu } from './menu';
import { MenuItemProps } from './types';

export const MenuItem = forwardRef( function ForwardedMenuItem(
	{
		item,
		selected,
		inputValue,
		level,
		multiple,
		className,
		onSelect,
		onRemove,
		...props
	}: MenuItemProps,
	ref: React.ForwardedRef< HTMLLIElement >
) {
	const {
		expanded,
		checkedStatus,
		highlighted,
		onToggleExpand,
		onSelectChild,
		onSelectChildren,
		onRemoveChildren,
		onKeyDown,
	} = useMenuItem( {
		item,
		selected,
		inputValue,
		level,
		multiple,
		onSelect,
		onRemove,
	} );

	return (
		<li
			{ ...props }
			ref={ ref }
			className={ classNames(
				className,
				'experimental-woocommerce-autocomplete__menu-item',
				{
					'experimental-woocommerce-autocomplete__menu-item--highlighted':
						highlighted,
				}
			) }
			role="none"
		>
			<div
				onKeyDown={ onKeyDown }
				style={ { paddingLeft: ( level - 1 ) * 28 + 12 } }
				className={ classNames(
					'experimental-woocommerce-autocomplete__menu-item-heading'
				) }
				aria-selected={ checkedStatus !== 'unchecked' }
				aria-expanded={ expanded }
				role="treeitem"
			>
				<label className="experimental-woocommerce-autocomplete__menu-item-label">
					{ multiple ? (
						<CheckboxControl
							indeterminate={ checkedStatus === 'indeterminate' }
							checked={ checkedStatus === 'checked' }
							onChange={ onSelectChild }
						/>
					) : (
						<input
							type="radio"
							checked={ checkedStatus === 'checked' }
							className="components-radio-control__input"
							onChange={ ( event ) =>
								onSelectChild( event.currentTarget.checked )
							}
						/>
					) }
					<span>
						{ inputValue
							? interpolateComponents( {
									mixedString: item.label.replace(
										new RegExp( inputValue, 'ig' ),
										( substring ) =>
											`{{bold}}${ substring }{{/bold}}`
									),
									components: {
										bold: <b />,
									},
							  } )
							: item.label }
					</span>
				</label>
				{ item.children && item.children.length > 0 && (
					<div className="experimental-woocommerce-autocomplete__menu-item-expander">
						<Button
							icon={ expanded ? chevronUp : chevronDown }
							onClick={ onToggleExpand }
							className="experimental-woocommerce-autocomplete__menu-item-expander"
							aria-label={
								expanded
									? __( 'Collapse', 'woocommerce' )
									: __( 'Expand', 'woocommerce' )
							}
						/>
					</div>
				) }
			</div>
			{ item.children && item.children.length > 0 && expanded && (
				<Menu
					items={ item.children }
					selected={ selected }
					inputValue={ inputValue }
					level={ level + 1 }
					multiple={ multiple }
					onSelect={ onSelectChildren }
					onRemove={ onRemoveChildren }
					aria-label={ item.label }
				/>
			) }
		</li>
	);
} );
