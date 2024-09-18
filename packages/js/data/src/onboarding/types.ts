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
	recordViewEvent: boolean;
	badge?: string;
	additionalData?: {
		woocommerceTaxCountries?: string[];
		stripeTaxCountries?: string[];
		taxJarActivated?: boolean;
		avalaraActivated?: boolean;
		stripeTaxActivated?: boolean;
		woocommerceTaxActivated?: boolean;
		woocommerceShippingActivated?: boolean;
		wooPaymentsIncentiveId?: string;
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
	productTypes: OnboardingProductTypes;
	emailPrefill: string;
	// TODO clarify what the error record's type is
	errors: Record< string, unknown >;
	requesting: Record< string, boolean >;
	jetpackAuthUrls: Record< string, GetJetpackAuthUrlResponse >;
};

export type Industry = {
	slug: string;
};

export type GetJetpackAuthUrlResponse = {
	url: string;
	success: boolean;
	errors: string[];
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
	business_extensions?: string[] | null;
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
	is_plugins_page_skipped?: boolean | null;
	/** @deprecated This is always null, the theme step has been removed since WC 7.7. */
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
	label: string;
	default?: boolean;
	product?: number;
	id?: number;
	title?: string;
	yearly_price?: number;
	description?: string;
	more_url?: string;
	slug?: string;
};

export type OnboardingProductTypes =
	| Record< ProductTypeSlug, OnboardingProductType >
	| Record< string, never >;

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
	label?: string;
	is_built_by_wc: boolean;
	is_visible: boolean;
	is_installed?: boolean;
	is_activated?: boolean;
	learn_more_link?: string;
	install_priority?: number;
};

export type InstallAndActivatePluginsAsyncResponse = {
	job_id: string;
	status: 'pendi<ng' | 'in-progress' | 'completed' | 'failed';
	plugins: Array< {
		status: 'pending' | 'installing' | 'installed' | 'activated' | 'failed';
		errors: string[];
		install_duration?: number;
	} >;
};
