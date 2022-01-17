/**
 * External dependencies
 */
import { select, subscribe } from '@wordpress/data';
import { registerBlockType } from '@wordpress/blocks';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { box, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './editor.scss';
import { TEMPLATES } from './constants';
import { getMatchingTemplateData } from './utils';

interface Props {
	attributes: {
		template: string;
		title: string;
		placeholder: string;
	};
}

const Edit = ( { attributes }: Props ) => {
	const blockProps = useBlockProps();
	const { title, placeholder } = attributes;

	return (
		<div { ...blockProps }>
			<Placeholder
				icon={ box }
				label={ title }
				className="wp-block-woocommerce-legacy-template__placeholder"
			>
				<div className="wp-block-woocommerce-legacy-template__placeholder-copy">
					<p className="wp-block-woocommerce-legacy-template__placeholder-warning">
						<strong>
							{ __(
								'Attention: Do not remove this block!',
								'woo-gutenberg-products-block'
							) }
						</strong>{ ' ' }
						{ __(
							'Removal will cause unintended effects on your store.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<p>
						{ sprintf(
							/* translators: %s is the template title */
							__(
								'This is an editor placeholder for the %s. On your store this will be replaced by the template and display with your product image(s), title, price, etc. You can move this placeholder around and add further blocks around it to extend the template.',
								'woo-gutenberg-products-block'
							),
							title
						) }
					</p>
				</div>
				<div className="wp-block-woocommerce-legacy-template__placeholder-wireframe">
					<img
						className="wp-block-woocommerce-legacy-template__placeholder-image"
						src={ `${ WC_BLOCKS_IMAGE_URL }template-placeholders/${ placeholder }.svg` }
						alt={ title }
					/>
				</div>
			</Placeholder>
		</div>
	);
};

let templateId: string | undefined;

const unsubscribe = subscribe( () => {
	const store = select( 'core/edit-site' );

	if ( ! store ) {
		// The store will only exist in the Site Editor so we need to unsubscribe and early return for Posts / Pages.
		unsubscribe();
		return;
	}

	templateId = store?.getEditedPostId();

	if ( templateId ) {
		unsubscribe();
		const currentTemplateSlug = templateId?.split( '//' )[ 1 ];
		const templateData = getMatchingTemplateData(
			TEMPLATES,
			currentTemplateSlug
		);
		// We only want this block to be available for use in specified WooCommerce templates.
		const eligibleForInserter = templateData !== null;
		const title = templateData?.title ?? currentTemplateSlug;
		const placeholder = templateData?.placeholder ?? 'fallback';

		registerBlockType( 'woocommerce/legacy-template', {
			title,
			icon: (
				<Icon
					icon={ box }
					className="wc-block-editor-components-block-icon"
				/>
			),
			category: 'woocommerce',
			apiVersion: 2,
			keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
			description: __(
				'Renders legacy WooCommerce PHP templates.',
				'woo-gutenberg-products-block'
			),
			supports: {
				align: [ 'wide', 'full' ],
				html: false,
				multiple: false,
				reusable: false,
				inserter: eligibleForInserter,
			},
			example: {
				attributes: {
					isPreview: true,
				},
			},
			attributes: {
				/**
				 * Template attribute is used to determine which core PHP template gets rendered.
				 */
				template: {
					type: 'string',
					default: currentTemplateSlug,
				},
				title: {
					type: 'string',
					default: title,
				},
				placeholder: {
					type: 'string',
					default: placeholder,
				},
			},
			edit: Edit,
			save: () => null,
		} );
	}
} );
