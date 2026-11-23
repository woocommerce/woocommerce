/**
 * External dependencies
 */
import type { SelectableItem } from '@woocommerce/types';

export function getSelectionSummary(
	items: readonly SelectableItem[],
	placeholder: string
): string {
	const selected = items.filter( ( item ) => item.selected );
	if ( ! selected.length ) {
		return placeholder;
	}
	const labels = selected
		.slice( 0, 2 )
		.map( ( item ) => item.label )
		.join( ', ' );
	return selected.length > 2
		? `${ labels } +${ selected.length - 2 }`
		: labels;
}
