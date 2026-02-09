declare global {
	interface Window {
		wcSettings: {
			isRemoteLoggingEnabled: boolean;
		};
		wc: {
			wcSettings: {
				WC_VERSION: string;
			}
		}
	}
}

export {};
