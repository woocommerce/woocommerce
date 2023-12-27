/**
 * External dependencies
 */
import {
	useBlockProps,
	InnerBlocks,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { Inspector } from './components/inspector';
import { RemovableListItem } from './components/removable-list-item';

const Edit = ( props: EditProps ) => {
	const { displayStyle } = props.attributes;

	const blockProps = useBlockProps( {
		className: 'wc-block-active-filters',
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: [
			[
				'core/heading',
				{ content: __( 'Active Filters', 'woocommerce' ), level: 3 },
			],
		],
	} );

	return (
		<div { ...innerBlocksProps }>
			<Inspector { ...props } />
			<InnerBlocks allowedBlocks={ [ 'core/heading' ] } />
			<Disabled>
				<ul
					className={ classNames( 'wc-block-active-filters__list', {
						'wc-block-active-filters__list--chips':
							displayStyle === 'chips',
					} ) }
				>
					<RemovableListItem
						type={ __( 'Size', 'woocommerce' ) }
						name={ __( 'Small', 'woocommerce' ) }
						displayStyle={ displayStyle }
					/>
					<RemovableListItem
						type={ __( 'Color', 'woocommerce' ) }
						name={ __( 'Blue', 'woocommerce' ) }
						displayStyle={ displayStyle }
					/>
				</ul>
			</Disabled>
		</div>
	);
};

export default Edit;
