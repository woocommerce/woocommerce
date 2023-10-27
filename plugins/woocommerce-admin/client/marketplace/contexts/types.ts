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
	loadSubscriptions: ( toggleLoading?: boolean ) => Promise< void >;
	isLoading: boolean;
	setIsLoading: ( isLoading: boolean ) => void;
};

export type InstallContextType = {
	installingProducts: string[];
	isInstalling: ( productKey: string ) => boolean;
	addInstalling: ( productKey: string ) => void;
	removeInstalling: ( productKey: string ) => void;
};
