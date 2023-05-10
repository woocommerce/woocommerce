declare global {
	interface Window {
		wcAdminFeatures: Record< string, boolean >;
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
