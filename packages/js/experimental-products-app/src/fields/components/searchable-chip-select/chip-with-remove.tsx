/**
 * External dependencies
 */
import { Combobox as BaseCombobox } from '@base-ui/react/combobox';
import clsx from 'clsx';
import { forwardRef } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { SearchableChipSelectChipWithRemoveProps } from './types';

export const ChipWithRemove = forwardRef<
	HTMLDivElement,
	SearchableChipSelectChipWithRemoveProps
>( function ChipWithRemove(
	{ className, children, prefix, ...restProps },
	ref
) {
	return (
		<BaseCombobox.Chip
			ref={ ref }
			className={ clsx(
				'woocommerce-searchable-chip-select__chip',
				className
			) }
			{ ...restProps }
		>
			{ prefix && (
				<span className="woocommerce-searchable-chip-select__chip-prefix">
					{ prefix }
				</span>
			) }
			<span className="woocommerce-searchable-chip-select__chip-content">
				{ children }
			</span>
			<BaseCombobox.ChipRemove
				className="woocommerce-searchable-chip-select__chip-remove"
				aria-label={ __( 'Remove', 'woocommerce' ) }
			>
				<span aria-hidden="true">×</span>
			</BaseCombobox.ChipRemove>
		</BaseCombobox.Chip>
	);
} );
