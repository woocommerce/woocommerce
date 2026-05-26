/**
 * External dependencies
 */
import { forwardRef, useId } from 'react';

/**
 * Internal dependencies
 */
import { SearchableChipSelect } from './searchable-chip-select';
import type { SearchableChipSelectControlProps } from './types';

function mergeIds( ...ids: Array< string | undefined > ) {
	return ids.filter( Boolean ).join( ' ' ) || undefined;
}

/**
 * A searchable multi-selection field with label and description wiring.
 */
export const SearchableChipSelectControl = forwardRef<
	HTMLDivElement,
	SearchableChipSelectControlProps
>( function SearchableChipSelectControl(
	{
		className,
		label,
		description,
		'aria-labelledby': ariaLabelledby,
		'aria-describedby': ariaDescribedby,
		...restProps
	},
	ref
) {
	const id = useId();
	const labelId = `${ id }-label`;
	const descriptionId = description ? `${ id }-description` : undefined;

	return (
		<div className={ className }>
			<div
				id={ labelId }
				className="woocommerce-searchable-chip-select__label"
			>
				{ label }
			</div>
			<SearchableChipSelect
				ref={ ref }
				aria-labelledby={ mergeIds( ariaLabelledby, labelId ) }
				aria-describedby={ mergeIds( ariaDescribedby, descriptionId ) }
				{ ...restProps }
			/>
			{ description && (
				<div
					id={ descriptionId }
					className="woocommerce-searchable-chip-select__description"
				>
					{ description }
				</div>
			) }
		</div>
	);
} );
