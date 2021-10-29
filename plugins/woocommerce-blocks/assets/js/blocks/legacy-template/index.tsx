/**
 * External dependencies
 */
import { registerExperimentalBlockType } from '@woocommerce/block-settings';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

interface Props {
	attributes: {
		template: string;
	};
}

const Edit = ( { attributes }: Props ) => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Placeholder
				label={ sprintf(
					/* translators: %s is the template name */
					__(
						'Wireframe template for %s will be rendered here.',
						'woo-gutenberg-products-block'
					),
					attributes.template
				) }
			/>
		</div>
	);
};

registerExperimentalBlockType( 'woocommerce/legacy-template', {
	title: __( 'WooCommerce Legacy Template', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	apiVersion: 2,
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Renders legacy WooCommerce PHP templates.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
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
			default: 'any',
		},
		lock: {
			type: 'object',
			default: {
				remove: true,
			},
		},
	},
	edit: Edit,
	save: () => null,
} );
