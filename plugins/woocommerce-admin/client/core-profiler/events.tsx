/**
 * External dependencies
 */
import { CountryStateOption } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { IndustryChoice } from './pages/BusinessInfo';
import {
	InstallationCompletedResult,
	PluginInstallError,
} from './services/installAndActivatePlugins';
import { CoreProfilerStateMachineContext } from '.';

export type InitializationCompleteEvent = {
	type: 'INITIALIZATION_COMPLETE';
	payload: { optInDataSharing: boolean };
};

export type IntroOptInEvent =
	| IntroCompletedEvent
	| IntroSkippedEvent
	| IntroBuilderEvent;

export type IntroCompletedEvent = {
	type: 'INTRO_COMPLETED';
	payload: { optInDataSharing: boolean };
}; // can be true or false depending on whether the user opted in or not

export type IntroSkippedEvent = {
	type: 'INTRO_SKIPPED';
	payload: { optInDataSharing: false };
}; // always false for now

export type UserProfileEvent =
	| {
			type: 'USER_PROFILE_COMPLETED';
			payload: {
				userProfile: CoreProfilerStateMachineContext[ 'userProfile' ];
			};
	  }
	| {
			type: 'USER_PROFILE_SKIPPED';
			payload: { userProfile: { skipped: true } };
	  };

export type BusinessInfoEvent =
	| {
			type: 'BUSINESS_INFO_COMPLETED';
			payload: {
				storeName?: string;
				industry?: IndustryChoice;
				storeLocation: CountryStateOption[ 'key' ];
				geolocationOverruled: boolean;
				isOptInMarketing: boolean;
				storeEmailAddress: string;
			};
	  }
	| {
			type: 'RETRY_PRE_BUSINESS_INFO';
	  }
	| {
			type: 'SKIP_BUSINESS_INFO_STEP';
	  };

export type BusinessLocationEvent = {
	type: 'BUSINESS_LOCATION_COMPLETED';
	payload: {
		storeLocation: CountryStateOption[ 'key' ];
	};
};

export type PluginsInstallationRequestedEvent = {
	type: 'PLUGINS_INSTALLATION_REQUESTED';
	payload: {
		pluginsShown: string[];
		pluginsSelected: string[];
		pluginsUnselected: string[];
	};
};

export type PluginsLearnMoreLinkClickedEvent = {
	type: 'PLUGINS_LEARN_MORE_LINK_CLICKED';
	payload: {
		plugin: string;
		learnMoreLink: string;
	};
};

export type PluginsPageSkippedEvent = {
	type: 'PLUGINS_PAGE_SKIPPED';
};

export type PluginInstalledAndActivatedEvent = {
	type: 'PLUGIN_INSTALLED_AND_ACTIVATED';
	payload: {
		progressPercentage: number;
	};
};
export type PluginsInstallationCompletedEvent = {
	type: 'PLUGINS_INSTALLATION_COMPLETED';
	payload: {
		installationCompletedResult: InstallationCompletedResult;
	};
};

export type PluginsInstallationCompletedWithErrorsEvent = {
	type: 'PLUGINS_INSTALLATION_COMPLETED_WITH_ERRORS';
	payload: {
		errors: PluginInstallError[];
	};
};

export type ExternalUrlUpdateEvent = {
	type: 'EXTERNAL_URL_UPDATE';
};

export type RedirectToWooHomeEvent = {
	type: 'REDIRECT_TO_WOO_HOME';
};

export type IntroBuilderEvent = {
	type: 'INTRO_BUILDER';
	payload: { optInDataSharing: false };
}; // always false for now

export type CoreProfilerEvents =
	| InitializationCompleteEvent
	| IntroOptInEvent
	| UserProfileEvent
	| BusinessInfoEvent
	| BusinessLocationEvent
	| PluginsInstallationRequestedEvent
	| PluginsLearnMoreLinkClickedEvent
	| PluginsPageSkippedEvent
	| PluginInstalledAndActivatedEvent
	| PluginsInstallationCompletedEvent
	| PluginsInstallationCompletedWithErrorsEvent
	| ExternalUrlUpdateEvent
	| RedirectToWooHomeEvent
	| IntroBuilderEvent;
