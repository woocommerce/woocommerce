/**
 * Internal dependencies
 */
import type { SearchListItem as SearchListItemProps } from '../search-list-control/types';

export type ProductTagControlProps = {
	isCompact?: boolean;
	onChange: ( selected: SearchListItemProps[] ) => void;
	onOperatorChange?: ( operator: string ) => void;
	operator?: string;
	// Selected tag ids.
	selected: ( number | string )[];
};
