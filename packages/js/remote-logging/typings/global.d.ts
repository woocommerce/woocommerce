declare global {
	interface Window {
		wcTracks: {
			isEnabled: boolean;
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
