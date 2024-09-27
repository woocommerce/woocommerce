declare global {
	interface Window {
		productBlockEditorSettings: {
			productTemplates: ProductTemplate[];
			maxUploadFileSize: number;
		};
		wcAdminFeatures: Record< string, boolean >;
		wcTracks: {
			isEnabled: boolean;
			validateEvent: ( name: string, properties: unknown ) => void;
			recordEvent: ( name: string, properties: unknown ) => void;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
