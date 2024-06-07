/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { PriceSlider } from './components/price-slider';
import { Inspector } from './components/inspector';

const Edit = ( props: EditProps ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Inspector { ...props } />
			<Disabled>
				<PriceSlider { ...props } />
			</Disabled>
		</div>
	);
};

export default Edit;
