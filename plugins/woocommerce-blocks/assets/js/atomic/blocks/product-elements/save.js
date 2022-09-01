/**
 * External dependencies
 */
import classnames from 'classnames';

const save = ( { attributes } ) => {
	if ( attributes.isDescendentOfQueryLoop ) {
		return null;
	}

	return (
		<div className={ classnames( 'is-loading', attributes.className ) } />
	);
};

export default save;
