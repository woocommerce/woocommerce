/**
 * Internal dependencies
 */
import { Analytics } from './analytics';
import SessionManager from './session-manager';

/**
 * Initialize analytics
 */
function initAnalytics() {
	const sessionManager = new SessionManager();
	const analytics = new Analytics( sessionManager, {
		eventQueue: window.wcAnalytics.eventQueue,
		commonProps: window.wcAnalytics.commonProps,
		features: window.wcAnalytics.features,
		pages: window.wcAnalytics.pages,
	} );
	analytics.init();
}

initAnalytics();
