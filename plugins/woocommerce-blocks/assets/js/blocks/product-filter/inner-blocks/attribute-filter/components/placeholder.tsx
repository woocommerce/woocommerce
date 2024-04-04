/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, category, external } from '@wordpress/icons';
import { getAdminLink } from '@woocommerce/settings';
import { Placeholder, Button } from '@wordpress/components';

export const AttributesPlaceholder = ( {
	children,
}: {
	children: React.ReactNode;
} ) => (
	<Placeholder
		className="wc-block-attribute-filter"
		icon={ <Icon icon={ category } /> }
		label={ __( 'Filter by Attribute', 'woocommerce' ) }
		instructions={ __(
			'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
			'woocommerce'
		) }
	>
		{ children }
	</Placeholder>
);

export const NoAttributesPlaceholder = () => (
	<AttributesPlaceholder>
		<p>
			{ __(
				"Attributes are needed for filtering your products. You haven't created any attributes yet.",
				'woocommerce'
			) }
		</p>
		<Button
			className="wc-block-attribute-filter__add-attribute-button"
			variant="secondary"
			href={ getAdminLink(
				'edit.php?post_type=product&page=product_attributes'
			) }
			target="_top"
		>
			{ __( 'Add new attribute', 'woocommerce' ) + ' ' }
			<Icon icon={ external } />
		</Button>
		<Button
			className="wc-block-attribute-filter__read_more_button"
			variant="tertiary"
			href="https://woocommerce.com/document/managing-product-taxonomies/"
			target="_blank"
		>
			{ __( 'Learn more', 'woocommerce' ) }
		</Button>
	</AttributesPlaceholder>
);
