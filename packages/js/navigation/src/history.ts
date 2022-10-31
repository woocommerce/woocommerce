/**
 * External dependencies
 */
import { BrowserHistory, createBrowserHistory, Location } from 'history';

// See https://github.com/ReactTraining/react-router/blob/master/FAQ.md#how-do-i-access-the-history-object-outside-of-components
// ^ This is a bit outdated but there's no newer documentation - the replacement for this is to use <unstable_HistoryRouter /> https://reactrouter.com/docs/en/v6/routers/history-router

/**
 * Extension of history.BrowserHistory but also adds { pathname: string } to the location object.
 */
export interface WooBrowserHistory extends BrowserHistory {
	location: Location & {
		pathname: string;
	};
}

let _history: WooBrowserHistory;

/**
 * Recreate `history` to coerce React Router into accepting path arguments found in query
 * parameter `path`, allowing a url hash to be avoided. Since hash portions of the url are
 * not sent server side, full route information can be detected by the server.
 *
 * `<Router />` and `<Switch />` components use `history.location()` to match a url with a route.
 * Since they don't parse query arguments, recreate `get location` to return a `pathname` with the
 * query path argument's value.
 *
 * In react-router v6, { basename } is no longer a parameter in createBrowserHistory(), and the
 * replacement is to use basename in the <Route> component.
 *
 * @return {Object} React-router history object with `get location` modified.
 */
function getHistory(): WooBrowserHistory {
	if ( ! _history ) {
		const browserHistory = createBrowserHistory();
		_history = {
			get action() {
				return browserHistory.action;
			},
			get location() {
				const { location } = browserHistory;

				const query = new URLSearchParams( location.search );
				const pathname = query.has( 'path' )
					? // eslint-disable-next-line @typescript-eslint/no-non-null-assertion -- We already asserted non-null above.
					  query.get( 'path' )!
					: '/';

				return {
					...location,
					pathname,
				};
			},
			createHref: browserHistory.createHref,
			push: browserHistory.push,
			replace: browserHistory.replace,
			go: browserHistory.go,
			back: browserHistory.back,
			forward: browserHistory.forward,
			block: browserHistory.block,
			listen( listener ) {
				return browserHistory.listen( () => {
					listener( {
						action: this.action,
						location: this.location,
					} );
				} );
			},
		};
	}
	return _history;
}

export { getHistory };
