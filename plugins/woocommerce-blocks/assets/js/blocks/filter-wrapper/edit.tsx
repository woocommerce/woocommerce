/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import Upgrade from '../product-filter/components/upgrade';

const Edit = ( { attributes, clientId }: EditProps ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			{ isExperimentalBuild() && <Upgrade clientId={ clientId } /> }
			<InnerBlocks
				allowedBlocks={ [ 'core/heading' ] }
				template={ [
					[
						'core/heading',
						{ level: 3, content: attributes.heading || '' },
					],
					[
						`woocommerce/${ attributes.filterType }`,
						{
							heading: '',
							lock: {
								remove: true,
							},
						},
					],
				] }
			/>
		</div>
	);
};

export default Edit;
