declare global {
	interface Window {
		wcTracks: {
			isEnabled: boolean;
			recordEvent: (
				name: string,
				properties: unknown,
			) => void;
		};
		_tkq: {
			[key: string]: number;
		}
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
