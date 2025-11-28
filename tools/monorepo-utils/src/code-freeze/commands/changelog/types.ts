export type Options = {
	owner?: string;
	name?: string;
	version: string;
	branch?: string;
	devRepoPath?: string;
	commitDirectToBase?: boolean;
	override?: string;
	appendChangelog?: boolean;
};
