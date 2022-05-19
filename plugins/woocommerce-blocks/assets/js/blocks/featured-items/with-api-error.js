/**
 * External dependencies
 */
import ErrorPlaceholder from '@woocommerce/editor-components/error-placeholder';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import { getClassPrefixFromName } from './utils';

export const withApiError = ( Component ) => ( props ) => {
	const { error, isLoading, name } = props;

	const className = getClassPrefixFromName( name );
	const onRetry =
		name === BLOCK_NAMES.featuredCategory
			? props.getCategory
			: props.getProduct;

	if ( error ) {
		return (
			<ErrorPlaceholder
				className={ `${ className }-error` }
				error={ error }
				isLoading={ isLoading }
				onRetry={ onRetry }
			/>
		);
	}

	return <Component { ...props } />;
};
