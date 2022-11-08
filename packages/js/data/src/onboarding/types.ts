/**
 * Internal dependencies
 */
import { Plugin } from '../plugins/types';

export type TaskType = {
	actionLabel?: string;
	actionUrl?: string;
	content: string;
	id: string;
	parentId: string;
	isComplete: boolean;
	isDismissable: boolean;
	isDismissed: boolean;
	isSnoozed: boolean;
	isVisible: boolean;
	isSnoozeable: boolean;
	isDisabled: boolean;
	snoozedUntil: number;
	time: string;
	title: string;
	isVisited: boolean;
	additionalInfo: string;
	canView: boolean;
	isActioned: boolean;
	eventPrefix: string;
	level: number;
	additionalData?: {
		woocommerceTaxCountries?: string[];
		taxJarActivated?: boolean;
		avalaraActivated?: boolean;
	};
	// Possibly added in DeprecatedTasks.mergeDeprecatedCallbackFunctions
	isDeprecated?: boolean;
};

// reference: https://github.com/woocommerce/woocommerce-admin/blob/75cf5292f66bf69202f67356d143743a8796a7f6/docs/examples/extensions/add-task/js/index.js#L77-L101
export type DeprecatedTaskType = {
	key: string;
	title: string;
	content: string;
	container: React.ReactNode;
	completed: boolean;
	visible: boolean;
	additionalInfo: string;
	time: string;
	isDismissable: boolean;
	onDelete: () => void;
	onDismiss: () => void;
	allowRemindMeLater: string;
	remindMeLater: () => () => void;
	level?: string;
	type?: string;
};

export type TaskListType = {
	id: string;
	title: string;
	isHidden: boolean;
	isVisible: boolean;
	isComplete: boolean;
	tasks: TaskType[];
	eventPrefix: string;
	displayProgressHeader: boolean;
	keepCompletedTaskList: 'yes' | 'no';
	showCESFeedback?: boolean;
	isToggleable?: boolean;
	isCollapsible?: boolean;
	isExpandable?: boolean;
};

export type OnboardingState = {
	freeExtensions: ExtensionList[];
	profileItems: ProfileItems;
	taskLists: Record< string, TaskListType >;
	paymentMethods: Plugin[];
	productTypes: OnboardingProductType[];
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
	| 'downloads'
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

export type ProfileItems = {
	business_extensions?: [] | null;
	completed?: boolean | null;
	industry?: Industry[] | null;
	number_employees?: string | null;
	other_platform?: OtherPlatformSlug | null;
	other_platform_name?: string | null;
	product_count?: ProductCount | null;
	product_types?: ProductTypeSlug[] | null;
	revenue?: RevenueTypeSlug | null;
	selling_venues?: string | null;
	setup_client?: boolean | null;
	skipped?: boolean | null;
	theme?: string | null;
	wccom_connected?: boolean | null;
	is_agree_marketing?: boolean | null;
	store_email?: string | null;
	is_store_country_set?: boolean | null;
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

export type OnboardingProductType = {
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
	is_visible: boolean;
};
