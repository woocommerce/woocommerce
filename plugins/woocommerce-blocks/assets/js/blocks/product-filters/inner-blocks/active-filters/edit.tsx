/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { Inspector } from './components/inspector';
import { InitialDisabled } from '../../components/initial-disabled';
import { EXCLUDED_BLOCKS } from '../../constants';
import { getAllowedBlocks } from '../../utils';

const Edit = ( props: EditProps ) => {
	const { attributes: blockAttributes } = props;

	const { displayStyle } = blockAttributes;
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
			template: [
				[
					'core/group',
					{
						layout: {
							type: 'flex',
							flexWrap: 'nowrap',
						},
						metadata: {
							name: __( 'Header', 'woocommerce' ),
						},
						style: {
							spacing: {
								blockGap: '0',
							},
						},
					},
					[
						[
							'core/heading',
							{
								level: 3,
								content: __( 'Active', 'woocommerce' ),
							},
						],
						[
							'woocommerce/product-filter-clear-button',
							{
								lock: {
									remove: true,
									move: false,
								},
							},
						],
					],
				],
				[
					displayStyle,
					{
						lock: {
							remove: true,
						},
					},
				],
			],
		}
	);

	return (
		<div { ...innerBlocksProps }>
			<Inspector { ...props } />
			<InitialDisabled>{ children }</InitialDisabled>
		</div>
	);
};

export default Edit;
