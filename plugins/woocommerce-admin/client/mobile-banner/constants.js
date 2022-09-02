// The Play Store link is defined as an exported constant mainly for the sake of testing the Mobile App Banner.
// It is nearly impossible to fake navigation in JSDOM 16, so exposing this link for mocking allows us to
// avoid triggering a navigation.
export const PLAY_STORE_LINK =
	'https://play.google.com/store/apps/details?id=com.woocommerce.android';

export const TRACKING_EVENT_NAME = 'wcadmin_mobile_android_banner_click';
