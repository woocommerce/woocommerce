/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';
import { BlockEditProps } from '@wordpress/blocks';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { UpgradeNotice } from './upgrade-notice';
import './editor.scss';

const Placeholder = () => {
	return (
		<div className="wc-block-editor-product-gallery">
			<img src={ PLACEHOLDER_IMG_SRC } alt="Placeholder" />
			<div className="wc-block-editor-product-gallery__other-images">
				{ [ ...Array( 4 ).keys() ].map( ( index ) => {
					return (
						<img
							key={ index }
							src={ PLACEHOLDER_IMG_SRC }
							alt="Placeholder"
						/>
					);
				} ) }
			</div>
		</div>
	);
};

const Edit = ( props: BlockEditProps< Record< string, never > > ) => {
	const blockProps = useBlockProps();
	const hasAddToCartWithOptionsBlock = useSelect( ( select ) => {
		const blocks = select( 'core/block-editor' ).getBlocks();

		return !! findBlock( {
			blocks,
			findCondition: ( block ) =>
				block.name === 'woocommerce/add-to-cart-with-options',
		} );
	}, [] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<UpgradeNotice
					blockClientId={ props.clientId }
					showAddToCartWithOptionsCompatibilityNotice={
						hasAddToCartWithOptionsBlock
					}
				/>
			</InspectorControls>
			<Disabled>
				<Placeholder />
			</Disabled>
		</div>
	);
};

export default Edit;
