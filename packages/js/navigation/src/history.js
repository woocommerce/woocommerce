/**
 * External dependencies
 */
import { createBrowserHistory } from 'history';
import { parse } from 'qs';

// See https://github.com/ReactTraining/react-router/blob/master/FAQ.md#how-do-i-access-the-history-object-outside-of-components

let _history;

/**
 * Recreate `history` to coerce React Router into accepting path arguments found in query
 * parameter `path`, allowing a url hash to be avoided. Since hash portions of the url are
 * not sent server side, full route information can be detected by the server.
 *
 * `<Router />` and `<Switch />` components use `history.location()` to match a url with a route.
 * Since they don't parse query arguments, recreate `get location` to return a `pathname` with the
 * query path argument's value.
 *
 * @return {Object} React-router history object with `get location` modified.
 */
function getHistory() {
	if ( ! _history ) {
		const path = document.location.pathname;
		const browserHistory = createBrowserHistory( {
			basename: path.substring( 0, path.lastIndexOf( '/' ) ),
		} );
		_history = {
			get length() {
				return browserHistory.length;
			},
			get action() {
				return browserHistory.action;
			},
			get location() {
				const { location } = browserHistory;
				const query = parse( location.search.substring( 1 ) );
				const pathname = query.path || '/';

				return {
					...location,
					pathname,
				};
			},
			createHref: ( ...args ) =>
				browserHistory.createHref.apply( browserHistory, args ),
			push: ( ...args ) =>
				browserHistory.push.apply( browserHistory, args ),
			replace: ( ...args ) =>
				browserHistory.replace.apply( browserHistory, args ),
			go: ( ...args ) => browserHistory.go.apply( browserHistory, args ),
			goBack: ( ...args ) =>
				browserHistory.goBack.apply( browserHistory, args ),
			goForward: ( ...args ) =>
				browserHistory.goForward.apply( browserHistory, args ),
			block: ( ...args ) =>
				browserHistory.block.apply( browserHistory, args ),
			listen( listener ) {
				return browserHistory.listen( () => {
					listener( this.location, this.action );
				} );
			},
		};
	}
	return _history;
}

export { getHistory };
