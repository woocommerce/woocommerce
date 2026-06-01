/**
 * External dependencies
 */
import { Combobox as BaseCombobox } from '@base-ui/react/combobox';
import clsx from 'clsx';
import { forwardRef } from 'react';

/**
 * Internal dependencies
 */
import type { SearchableChipSelectItemProps } from './types';

export const Item = forwardRef< HTMLDivElement, SearchableChipSelectItemProps >(
	function Item( { className, ...restProps }, ref ) {
		return (
			<BaseCombobox.Item
				ref={ ref }
				className={ clsx(
					'woocommerce-searchable-chip-select__item',
					className
				) }
				{ ...restProps }
			/>
		);
	}
);
