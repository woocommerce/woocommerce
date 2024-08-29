export interface AllReviewsEditorProps {
	attributes: {
		showProductName: boolean;
	};
	setAttributes: ( attributes: { showProductName?: boolean } ) => void;
	debouncedSpeak: ( message: string ) => void;
	idBase: unknown;
	instance: {
		raw: {
			number: number;
		};
	};
}
