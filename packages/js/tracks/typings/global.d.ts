declare global {
	interface Window {
		wcTracks: {
			isEnabled: boolean;
			validateEvent: (
				name: string,
				properties: unknown,
			) => void;
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
