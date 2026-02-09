/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';
import { BlockEditProps } from '@wordpress/blocks';

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

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<UpgradeNotice blockClientId={ props.clientId } />
			</InspectorControls>
			<Disabled>
				<Placeholder />
			</Disabled>
		</div>
	);
};

export default Edit;
