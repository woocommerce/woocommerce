export type Entity = {
	name: string;
	kind: string;
	baseURL: string;
	label: string;
	plural: string;
	key: string;
	supportsPagination: boolean;
	getTitle: ( record: unknown ) => string;
};
