/**
 * Internal dependencies
 */
import { Subscription } from '../components/my-subscriptions/types';

export type MarketplaceContextType = {
	isLoading: boolean;
	setIsLoading: ( isLoading: boolean ) => void;
	selectedTab: string;
	setSelectedTab: ( tab: string ) => void;
};

export type SubscriptionsContextType = {
	subscriptions: Subscription[];
	setSubscriptions: ( subscriptions: Subscription[] ) => void;
	loadSubscriptions: ( toggleLoading?: boolean ) => void;
	isLoading: boolean;
	setIsLoading: ( isLoading: boolean ) => void;
};
