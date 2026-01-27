/**
 * Internal dependencies
 */
import type { SearchListItem as SearchListItemProps } from '../search-list-control/types';

export type ProductBrandControlProps = {
	isCompact?: boolean;
	onChange: ( selected: SearchListItemProps[] ) => void;
	onOperatorChange?: ( operator: string ) => void;
	operator?: string;
	// Selected brand ids.
	selected: ( number | string )[];
};
