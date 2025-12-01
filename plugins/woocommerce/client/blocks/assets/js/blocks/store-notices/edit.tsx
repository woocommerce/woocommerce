/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import NoticeBanner from '@woocommerce/base-components/notice-banner';

/**
 * Internal dependencies
 */
import './editor.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-store-notices',
	} );

	return (
		<div { ...blockProps }>
			<NoticeBanner status="info" isDismissible={ false }>
				{ __(
					'Notices added by WooCommerce or extensions will show up here.',
					'woocommerce'
				) }
			</NoticeBanner>
		</div>
	);
};

export default Edit;
