/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';

// NOTE: This uses core/notices2, if this file is copied back upstream
// to Gutenberg this needs to be changed back to core/notices.
export default registerStore( 'core/notices2', {
	reducer,
	actions,
	selectors,
} );
