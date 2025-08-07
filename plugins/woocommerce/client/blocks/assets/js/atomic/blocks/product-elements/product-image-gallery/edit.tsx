/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { PLACEHOLDER_IMG_SRC } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
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

const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Disabled>
				<Placeholder />
			</Disabled>
		</div>
	);
};

export default Edit;
