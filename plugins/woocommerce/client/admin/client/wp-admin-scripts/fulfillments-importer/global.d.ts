import type { ImporterSettings } from './data/types';

declare global {
	interface Window {
		wcFulfillmentsImporterSettings?: ImporterSettings;
	}
}

export {};
