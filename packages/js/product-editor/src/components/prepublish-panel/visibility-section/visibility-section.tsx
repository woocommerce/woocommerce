/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';
import { recordEvent } from '@woocommerce/tracks';
import { CheckboxControl, PanelBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VisibilitySectionProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';
import { RequirePassword } from '../../require-password';
import { CatalogVisibility } from '../../catalog-visibility';

export function VisibilitySection( { productType }: VisibilitySectionProps ) {
	const [ catalogVisibility, setCatalogVisibility ] = useEntityProp<
		Product[ 'catalog_visibility' ]
	>( 'postType', productType, 'catalog_visibility' );

	const [ reviewsAllowed, setReviewsAllowed ] = useEntityProp<
		Product[ 'reviews_allowed' ]
	>( 'postType', productType, 'reviews_allowed' );

	const [ postPassword, setPostPassword ] = useEntityProp< string >(
		'postType',
		productType,
		'post_password'
	);

	function getVisibilityLabel() {
		if ( postPassword !== '' ) {
			return __( 'Password protected', 'woocommerce' );
		}
		if ( catalogVisibility === 'hidden' ) {
			return __( 'Hidden', 'woocommerce' );
		}
		return __( 'Public', 'woocommerce' );
	}

	return (
		<PanelBody
			initialOpen={ false }
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore We need to send an Element.
			title={ [
				__( 'Visibility: ', 'woocommerce' ),
				<span className="editor-post-publish-panel__link" key="label">
					{ getVisibilityLabel() }
				</span>,
			] }
		>
			<div className="woocommerce-publish-panel-visibility">
				<fieldset className="woocommerce-publish-panel-visibility__fieldset">
					<legend className="woocommerce-publish-panel-visibility__legend">
						{ __(
							'Control how this product is viewed by customers and other site users.',
							'woocommerce'
						) }
					</legend>
					<CatalogVisibility
						catalogVisibility={ catalogVisibility }
						label={ __( 'Hide in product catalog', 'woocommerce' ) }
						visibility={ 'search' }
						onCheckboxChange={ setCatalogVisibility }
					/>
					<CatalogVisibility
						catalogVisibility={ catalogVisibility }
						label={ __(
							'Hide from search results',
							'woocommerce'
						) }
						visibility={ 'catalog' }
						onCheckboxChange={ setCatalogVisibility }
					/>
					<CheckboxControl
						label={ __( 'Enable product reviews', 'woocommerce' ) }
						checked={ reviewsAllowed }
						onChange={ ( selected: boolean ) => {
							setReviewsAllowed( selected );
							recordEvent( 'product_prepublish_panel', {
								source: TRACKS_SOURCE,
								action: 'enable_product_reviews',
								value: selected,
							} );
						} }
					/>
					<RequirePassword
						label={ __( 'Require a password', 'woocommerce' ) }
						postPassword={ postPassword }
						onInputChange={ setPostPassword }
					/>
				</fieldset>
			</div>
		</PanelBody>
	);
}
