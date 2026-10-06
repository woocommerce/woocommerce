/**
 * Internal dependencies
 */
import customerNames from './customer-names';
import { AutoCompleter } from './types';

/**
 * Searches customers by name.
 *
 * @deprecated Use `customerNames`, which is named after the field it searches,
 * the way the sibling `usernames` and `emails` completers are. This is an alias
 * of it and behaves identically.
 */
const completer: AutoCompleter = {
	...customerNames,
	name: 'customers',
	className: 'woocommerce-search__customers-result',
};

export default completer;
