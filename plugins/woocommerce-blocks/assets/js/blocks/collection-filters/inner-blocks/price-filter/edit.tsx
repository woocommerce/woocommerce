/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';

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

	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template: [
			[
				'core/heading',
				{ content: __( 'Filter by Price', 'woocommerce' ), level: 3 },
			],
		],
	} );

	return (
		<div { ...innerBlockProps }>
			<Inspector { ...props } />
			<InnerBlocks allowedBlocks={ [ 'core/heading' ] } />
			<PriceSlider { ...props } />
		</div>
	);
};

export default Edit;
