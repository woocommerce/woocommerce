declare global {
	interface Window {
		wcSettings: {
			timeZone?: string;
			admin?: {
				timeZone?: string;
			};
		};
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
