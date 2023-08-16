export type CoreSelectors = {
	hasEditsForEntityRecord: (
		type: string,
		name: string,
		id: number | string
	) => boolean;
	isSavingEntityRecord: (
		type: string,
		name: string,
		id: number | string
	) => boolean;
	getEntityRecordNonTransientEdits: < T >(
		type: string,
		name: string,
		id: number | string
	) => Partial< T >;
};

export type CoreActions = {
	editEntityRecord: < T >(
		type: string,
		name: string,
		id: number | string,
		data: Partial< T >
	) => T;
	saveEditedEntityRecord: < T >(
		type: string,
		name: string,
		id: number | string,
		options?: unknown
	) => T;
};
