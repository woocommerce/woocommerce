export interface ReviewsByCategoryEditorProps {
	attributes: {
		editMode: boolean;
		categoryIds: number[];
		showProductName: boolean;
	};
	setAttributes: ( attributes: {
		editMode?: boolean;
		categoryIds?: number[];
		showProductName?: boolean;
	} ) => void;
	debouncedSpeak: ( message: string ) => void;
}
