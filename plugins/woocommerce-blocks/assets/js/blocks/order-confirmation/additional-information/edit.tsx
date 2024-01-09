/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Skeleton } from '@woocommerce/base-components/skeleton';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-additional-information',
	} );

	return (
		<div { ...blockProps }>
			<Skeleton numberOfLines={ 3 } />
		</div>
	);
};

export default Edit;
