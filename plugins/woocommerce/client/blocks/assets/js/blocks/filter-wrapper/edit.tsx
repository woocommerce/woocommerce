/**
 * External dependencies
 */
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { UpgradeNotice } from './upgrade';

const Edit = ( { attributes, clientId }: EditProps ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody>
					<UpgradeNotice clientId={ clientId } />
				</PanelBody>
			</InspectorControls>
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
