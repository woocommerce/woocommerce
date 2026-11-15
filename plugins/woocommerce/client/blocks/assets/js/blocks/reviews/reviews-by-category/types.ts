export interface ReviewsByCategoryEditorProps {
	attributes: {
		editMode: boolean;
		categoryIds: number[];
		offset: number;
		showProductName: boolean;
	};
	setAttributes: ( attributes: {
		editMode?: boolean;
		categoryIds?: number[];
		offset?: number;
		showProductName?: boolean;
	} ) => void;
	debouncedSpeak: ( message: string ) => void;
}
