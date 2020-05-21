/**
 * External dependencies
 */
import { isNil } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { DEFAULT_REQUIREMENT } from '../constants';
import { getResourceName } from '../utils';

const getOptions = ( getResource, requireResource ) => (
	optionNames,
	requirement = DEFAULT_REQUIREMENT
) => {
	const resourceName = getResourceName( 'options', optionNames );
	const options = {};

	const names =
		requireResource( requirement, resourceName ).data || optionNames;

	names.forEach( ( name ) => {
		const data = getSetting(
			'preloadOptions',
			{},
			( po ) =>
				getResource( getResourceName( 'options', name ) ).data ||
				po[ name ]
		);
		if ( data ) {
			options[ name ] = data;
		}
	} );
	return options;
};

const getOptionsError = ( getResource ) => ( optionNames ) => {
	return getResource( getResourceName( 'options', optionNames ) ).error;
};

const getUpdateOptionsError = ( getResource ) => ( optionNames ) => {
	return getResource( getResourceName( 'options-update', optionNames ) )
		.error;
};

const isGetOptionsRequesting = ( getResource ) => ( optionNames ) => {
	const { lastReceived, lastRequested } = getResource(
		getResourceName( 'options', optionNames )
	);

	if ( isNil( lastRequested ) || isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

const isUpdateOptionsRequesting = ( getResource ) => ( optionNames ) => {
	const { lastReceived, lastRequested } = getResource(
		getResourceName( 'options-update', optionNames )
	);

	if ( ! isNil( lastRequested ) && isNil( lastReceived ) ) {
		return true;
	}

	return lastRequested > lastReceived;
};

export default {
	getOptions,
	getOptionsError,
	getUpdateOptionsError,
	isGetOptionsRequesting,
	isUpdateOptionsRequesting,
};
