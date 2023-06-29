/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Notice, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { isSiteEditorPage } from '@woocommerce/utils';
import { select } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './editor.scss';

export function TemplateNotice( { block }: { block: string } ) {
	const [ settingStatus, setStatus ] = useState( 'pristine' );
	const store = select( 'core/edit-site' );

	if ( settingStatus === 'dismissed' || isSiteEditorPage( store ) ) {
		return null;
	}

	const editUrl = `${ getSetting(
		'adminUrl'
	) }site-editor.php?postType=wp_template&postId=woocommerce%2Fwoocommerce%2F%2F${ block }`;

	const noticeContent = sprintf(
		// translators: %s: cart or checkout page name.
		__(
			'The default %s can be customized in the Site Editor',
			'woo-gutenberg-products-block'
		),
		block === 'checkout'
			? __( 'checkout', 'woo-gutenberg-products-block' )
			: __( 'cart', 'woo-gutenberg-products-block' )
	);

	return (
		<Notice
			className="wc-default-template-notice"
			status={ 'warning' }
			onRemove={ () => setStatus( 'dismissed' ) }
			spokenMessage={ noticeContent }
		>
			<>
				<p>{ noticeContent }</p>
				<Button href={ editUrl } variant="secondary" isSmall={ true }>
					{ __( 'Edit template', 'woo-gutenberg-products-block' ) }
				</Button>
			</>
		</Notice>
	);
}
