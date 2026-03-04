/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

interface EditProps {
	attributes: {
		label: string;
		url: string;
		pageId: number;
		status: string;
		editUrl: string;
	};
}

export default function Edit( { attributes }: EditProps ) {
	const { label, url, status, editUrl } = attributes;
	const isDraft = status && status !== 'publish';
	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-privacy-links__item',
	} );

	const linkClass = clsx( 'wp-block-woocommerce-privacy-links__link', {
		'wp-block-woocommerce-privacy-links__link--is-draft': isDraft,
	} );

	return (
		<li { ...blockProps }>
			<a className={ linkClass } href={ url }>
				{ label }
			</a>
			{ isDraft && (
				<Tooltip
					text={ __(
						"This page is a draft and won't appear on the frontend. Edit and publish it to make it visible.",
						'woocommerce'
					) }
				>
					<a
						href={ editUrl }
						className="wp-block-woocommerce-privacy-links__draft-badge"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Edit draft', 'woocommerce' ) }
					</a>
				</Tooltip>
			) }
		</li>
	);
}
