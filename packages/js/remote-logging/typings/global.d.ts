declare global {
	interface Window {
		wcSettings?: {
			isRemoteLoggingEnabled: boolean;
		}
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
