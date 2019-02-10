/** @format */
/**
 * External dependencies
 */
import { createHashHistory } from 'history';

// See https://github.com/ReactTraining/react-router/blob/master/FAQ.md#how-do-i-access-the-history-object-outside-of-components

let _history;

function getHistory() {
	if ( ! _history ) {
		_history = createHashHistory();
	}
	return _history;
}

export { getHistory };
