/**
 * Internal dependencies
 */
import type {
	NoticeAction as BaseNoticeAction,
	NoticeOptions as BaseNoticeOptions,
} from '~/lib/notices/types';
import { Subscription } from '../components/my-subscriptions/types';

/** Notice can't set a link target, so `openInNewTab` actions are rendered separately as external links. */
export type NoticeAction = BaseNoticeAction & { openInNewTab?: boolean };

export type NoticeOptions = Omit< BaseNoticeOptions, 'actions' > & {
	actions?: NoticeAction[];
};

/**
 * Notice key shared by everything that refreshes subscriptions, so a refresh
 * result always replaces the previous one instead of stacking. Lives here
 * rather than in refresh-button so SubscriptionsContext can use it without
 * importing a component that imports the context back.
 */
export const REFRESH_SUBSCRIPTIONS_NOTICE_ID =
	'woocommerce-marketplace-refresh-subscriptions';

export interface SearchResultsCountType {
	extensions: number;
	themes: number;
	'business-services': number;
}

export type MarketplaceContextType = {
	isLoading: boolean;
	setIsLoading: ( isLoading: boolean ) => void;
	selectedTab: string;
	setSelectedTab: ( tab: string ) => void;
	isProductInstalled: ( slug: string ) => boolean;
	addInstalledProduct: ( slug: string ) => void;
	searchResultsCount: SearchResultsCountType;
	setSearchResultsCount: (
		updatedCounts: Partial< SearchResultsCountType >
	) => void;
	iamSettings: {
		product_previews?: 'modal' | 'none';
		quality_badge?: {
			enabled?: boolean;
			label?: string;
			tooltip?: string;
			docs_url?: string;
		};
	};
	/**
	 * Variation of the product preview experiment, or null when it hasn't
	 * loaded or the store isn't in the experiment.
	 */
	productPreviewVariation: string | null;
};

export type SubscriptionsContextType = {
	subscriptions: Subscription[];
	setSubscriptions: ( subscriptions: Subscription[] ) => void;
	loadSubscriptions: ( toggleLoading?: boolean ) => Promise< void >;
	refreshSubscriptions: ( toggleLoading?: boolean ) => Promise< void >;
	isLoading: boolean;
	setIsLoading: ( isLoading: boolean ) => void;
};

export enum NoticeStatus {
	Success = 'success',
	Error = 'error',
}

export interface Notice {
	productKey: string;
	message: string;
	status: NoticeStatus;
	options?: Partial< NoticeOptions > | undefined;
}

export interface NoticeState {
	notices: {
		[ key: string ]: Notice;
	};
}

export interface InstallingState {
	installingProducts: string[];
}
