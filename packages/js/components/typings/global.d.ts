declare global {
	interface Window {
		wcSettings: {
			variationTitleAttributesSeparator?: string;
			countries: Record< string, string >;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};

