/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-shipping-address',
	} );

	return (
		<div { ...blockProps }>
			<address>
				Test address 1<br />
				Test address 2<br />
				San Francisco, CA 94110
				<br />
				United States
			</address>
		</div>
	);
};

export default Edit;
