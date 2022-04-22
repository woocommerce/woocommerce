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
};

export type TaskListSection = {
	id: string;
	title: string;
	description: string;
	image: string;
	tasks: string[];
	isComplete: boolean;
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
	sections?: TaskListSection[];
	isToggleable?: boolean;
	isCollapsible?: boolean;
	isExpandable?: boolean;
};
