export type SyncStatusType = 'synced' | 'syncing' | 'failed';
export type IssueTypeType = 'error' | 'warning' | 'none';

export type RegisteredChannel = {
	slug: string;
	title: string;
	description: string;
	icon: string;
	isSetupCompleted: boolean;
	setupUrl: string;
	manageUrl: string;
	syncStatus: SyncStatusType;
	issueType: IssueTypeType;
	issueText: string;
};
