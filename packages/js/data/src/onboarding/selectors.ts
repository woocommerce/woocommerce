/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { TaskType, TaskListType } from './types';
import { WPDataSelectors } from '../types';
import { Plugin } from '../plugins/types';

export const getFreeExtensions = (
	state: OnboardingState
): ExtensionList[] => {
	return state.freeExtensions || [];
};

export const getProfileItems = (
	state: OnboardingState
): ProfileItemsState | Record< string, never > => {
	return state.profileItems || {};
};

const EMPTY_ARRAY: Product[] = [];

export const getTaskLists = createSelector(
	( state: OnboardingState ): TaskListType[] => {
		return Object.values( state.taskLists );
	},
	( state: OnboardingState ) => [ state.taskLists ]
);

export const getTaskListsByIds = createSelector(
	( state: OnboardingState, ids: string[] ): TaskListType[] => {
		return ids.map( ( id ) => state.taskLists[ id ] );
	},
	( state: OnboardingState, ids: string[] ) =>
		ids.map( ( id ) => state.taskLists[ id ] )
);

export const getTaskList = (
	state: OnboardingState,
	selector: string
): TaskListType | undefined => {
	return state.taskLists[ selector ];
};

export const getTask = (
	state: OnboardingState,
	selector: string
): TaskType | undefined => {
	return Object.keys( state.taskLists ).reduce(
		( value: TaskType | undefined, listId: string ) => {
			return (
				value ||
				state.taskLists[ listId ].tasks.find(
					( task ) => task.id === selector
				)
			);
		},
		undefined
	);
};

export const getPaymentGatewaySuggestions = (
	state: OnboardingState
): Plugin[] => {
	return state.paymentMethods || [];
};

export const getOnboardingError = (
	state: OnboardingState,
	selector: string
): unknown | false => {
	return state.errors[ selector ] || false;
};

export const isOnboardingRequesting = (
	state: OnboardingState,
	selector: string
): boolean => {
	return state.requesting[ selector ] || false;
};

export const getEmailPrefill = ( state: OnboardingState ): string => {
	return state.emailPrefill || '';
};

export const getProductTypes = ( state: OnboardingState ): Product[] => {
	return state.productTypes || EMPTY_ARRAY;
};

// Types
export type OnboardingSelectors = {
	getProfileItems: () => ReturnType< typeof getProfileItems >;
	getPaymentGatewaySuggestions: () => ReturnType<
		typeof getPaymentGatewaySuggestions
	>;
	getOnboardingError: () => ReturnType< typeof getOnboardingError >;
	isOnboardingRequesting: () => ReturnType< typeof isOnboardingRequesting >;
	getTaskListsByIds: (
		ids: string[]
	) => ReturnType< typeof getTaskListsByIds >;
	getTaskLists: () => ReturnType< typeof getTaskLists >;
	getTaskList: ( id: string ) => ReturnType< typeof getTaskList >;
	getFreeExtensions: () => ReturnType< typeof getFreeExtensions >;
} & WPDataSelectors;

export type OnboardingState = {
	freeExtensions: ExtensionList[];
	profileItems: ProfileItemsState;
	taskLists: Record< string, TaskListType >;
	paymentMethods: Plugin[];
	productTypes: Product[];
	emailPrefill: string;
	// TODO clarify what the error record's type is
	errors: Record< string, unknown >;
	requesting: Record< string, boolean >;
};

export type Industry = {
	slug: string;
};

export type ProductCount = '0' | '1-10' | '11-100' | '101 - 1000' | '1000+';

export type ProductTypeSlug =
	| 'physical'
	| 'bookings'
	| 'download'
	| 'memberships'
	| 'product-add-ons'
	| 'product-bundles'
	| 'subscriptions';

export type OtherPlatformSlug =
	| 'shopify'
	| 'bigcommerce'
	| 'wix'
	| 'amazon'
	| 'ebay'
	| 'etsy'
	| 'squarespace'
	| 'other';

export type RevenueTypeSlug =
	| 'none'
	| 'rather-not-say'
	| 'up-to-2500'
	| '2500-10000'
	| '10000-50000'
	| '50000-250000'
	| 'more-than-250000';

export type ProfileItemsState = {
	business_extensions: [  ] | null;
	completed: boolean | null;
	industry: Industry[] | null;
	number_employees: string | null;
	other_platform: OtherPlatformSlug | null;
	other_platform_name: string | null;
	product_count: ProductCount | null;
	product_types: ProductTypeSlug[] | null;
	revenue: RevenueTypeSlug | null;
	selling_venues: string | null;
	setup_client: boolean | null;
	skipped: boolean | null;
	theme: string | null;
	wccom_connected: boolean | null;
	is_agree_marketing: boolean | null;
	store_email: string | null;
};

export type FieldLocale = {
	locale: string;
	label: string;
};

export type MethodFields = {
	name: string;
	option?: string;
	label?: string;
	locales?: FieldLocale[];
	type?: string;
	value?: string;
};

export type Product = {
	default?: boolean;
	label: string;
	product?: number;
};

export type ExtensionList = {
	key: string;
	title: string;
	plugins: Extension[];
};

export type Extension = {
	description: string;
	key: string;
	image_url: string;
	manage_url: string;
	name: string;
	is_built_by_wc: boolean;
};
