/*
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './editor.scss';

export default function Edit() {
	return (
		<p { ...useBlockProps() }>
			{ __( 'Example Static - hello from the editor!', 'woocommerce' ) }
		</p>
	);
}
