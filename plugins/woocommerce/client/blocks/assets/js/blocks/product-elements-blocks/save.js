/**
 * External dependencies
 */
import clsx from 'clsx';

const save = ( { attributes } ) => {
	if (
		attributes.isDescendentOfQueryLoop ||
		attributes.isDescendentOfSingleProductBlock ||
		attributes.isDescendentOfSingleProductTemplate
	) {
		return null;
	}

	return <div className={ clsx( 'is-loading', attributes.className ) } />;
};

export default save;
