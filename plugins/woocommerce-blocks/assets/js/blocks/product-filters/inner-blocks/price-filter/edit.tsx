/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { PriceSlider } from './components/price-slider';
import { Inspector } from './components/inspector';

const Edit = ( props: EditProps ) => {
	const { showInputFields, inlineInput } = props.attributes;

	const blockProps = useBlockProps( {
		className: classNames( {
			'inline-input': inlineInput && showInputFields,
		} ),
	} );

	return (
		<div { ...blockProps }>
			<Inspector { ...props } />
			<PriceSlider { ...props } />
		</div>
	);
};

export default Edit;
