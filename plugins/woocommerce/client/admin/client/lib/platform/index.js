export const ANDROID_PLATFORM = 'android';
export const IOS_PLATFORM = 'ios';
export const UNKNOWN_PLATFORM = 'unknown';

/**
 * Provide basic detection of platform based on user agent. This is not
 * a robust check for browser features or the like. You should only use
 * this for non-critical display logic.
 */
export const platform = () => {
	if ( /iPhone|iPad|iPod/i.test( window.navigator.userAgent ) ) {
		return IOS_PLATFORM;
	} else if ( /Android/i.test( window.navigator.userAgent ) ) {
		return ANDROID_PLATFORM;
	}

	return UNKNOWN_PLATFORM;
};
