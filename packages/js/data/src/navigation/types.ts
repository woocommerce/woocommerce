/**
 * External dependencies
 */
import { getPersistedQuery } from '@woocommerce/navigation';

export type MenuItem = {
	id: string;
	title: string;
	url: string;
	order: number;
	migrate: boolean;
	menuId: string;
	isCategory?: boolean;
	badge?: number;
	backButtonLabel?: string;
	parent?: string;
	capability?: string;
	matchExpression?: string;
};

export type NavigationState = {
	error: null | unknown;
	menuItems: MenuItem[];
	favorites: string[];
	requesting: {
		[ key: string ]: boolean | unknown;
	};
	persistedQuery: ReturnType< typeof getPersistedQuery >;
};
