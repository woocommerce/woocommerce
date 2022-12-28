/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link, useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './options-section.scss';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { Options } from '../fields/options';

export const OptionsSection: React.FC = () => {
	const {
		getInputProps,
		values: { id: productId },
	} = useFormContext< Product >();

	return (
		<ProductSectionLayout
			title={ __( 'Options', 'woocommerce' ) }
			className="woocommerce-product-options-section"
			description={
				<>
					<span>
						{ __(
							'Add and manage options, such as size and color, for customers to choose on the product page.',
							'woocommerce'
						) }
					</span>
					<Link
						className="woocommerce-form-section__header-link"
						href="https://woocommerce.com/document/managing-product-taxonomies/#product-attributes"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'learn_more_about_options_help' );
						} }
					>
						{ __( 'Learn more about options', 'woocommerce' ) }
					</Link>
				</>
			}
		>
			<Options
				{ ...getInputProps( 'attributes', {
					productId,
				} ) }
			/>
		</ProductSectionLayout>
	);
};
