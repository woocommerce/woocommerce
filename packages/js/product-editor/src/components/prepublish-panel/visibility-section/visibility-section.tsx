/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Product, ProductCatalogVisibility } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
import { recordEvent } from '@woocommerce/tracks';
import {
	BaseControl,
	CheckboxControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
	PanelBody,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VisibilitySectionProps } from './types';
import { store as productEditorUiStore } from '../../../store/product-editor-ui';
import { TRACKS_SOURCE } from '../../../constants';

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

	const { requirePassword } = useDispatch( productEditorUiStore );

	const isPasswordRequired: boolean = useSelect( ( select ) => {
		return select( productEditorUiStore ).isPasswordRequired();
	}, [] );

	const postPasswordId = useInstanceId(
		BaseControl,
		'post_password'
	) as string;

	function handleVisibilityChange(
		selected: boolean,
		visibility: ProductCatalogVisibility
	) {
		if ( selected ) {
			if ( catalogVisibility === 'visible' ) {
				setCatalogVisibility( visibility );
				recordEvent( 'product_prepublish_panel', {
					source: TRACKS_SOURCE,
					action: 'catalog_visibility',
					visibility: catalogVisibility,
				} );
				return;
			}
			setCatalogVisibility( 'hidden' );
		} else {
			if ( catalogVisibility === 'hidden' ) {
				if ( visibility === 'catalog' ) {
					setCatalogVisibility( 'search' );
					recordEvent( 'product_prepublish_panel', {
						source: TRACKS_SOURCE,
						action: 'catalog_visibility',
						visibility: catalogVisibility,
					} );
					return;
				}
				if ( visibility === 'search' ) {
					setCatalogVisibility( 'catalog' );
					recordEvent( 'product_prepublish_panel', {
						source: TRACKS_SOURCE,
						action: 'catalog_visibility',
						visibility: catalogVisibility,
					} );
					return;
				}
				return;
			}
			setCatalogVisibility( 'visible' );
			recordEvent( 'product_prepublish_panel', {
				source: TRACKS_SOURCE,
				action: 'catalog_visibility',
				visibility: catalogVisibility,
			} );
		}
	}

	return (
		<PanelBody
			initialOpen={ false }
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore We need to send an Element.
			title={ [
				__( 'Visibility:', 'woocommerce' ),
				<span className="editor-post-publish-panel__link" key="label">
					{ catalogVisibility === 'visible'
						? __( 'Public', 'woocommerce' )
						: __( 'Hidden', 'woocommerce' ) }
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
					<CheckboxControl
						label={ __( 'Hide in product catalog', 'woocommerce' ) }
						checked={
							catalogVisibility === 'search' ||
							catalogVisibility === 'hidden'
						}
						onChange={ ( selected ) =>
							handleVisibilityChange( selected, 'search' )
						}
					/>
					<CheckboxControl
						label={ __(
							'Hide from search results',
							'woocommerce'
						) }
						checked={
							catalogVisibility === 'catalog' ||
							catalogVisibility === 'hidden'
						}
						onChange={ ( selected ) =>
							handleVisibilityChange( selected, 'catalog' )
						}
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
					<CheckboxControl
						label={ __( 'Require a password', 'woocommerce' ) }
						checked={ isPasswordRequired }
						onChange={ ( selected ) => {
							requirePassword( selected );
							if ( ! selected ) {
								setPostPassword( '' );
							}
							recordEvent( 'product_prepublish_panel', {
								source: TRACKS_SOURCE,
								action: 'require_password',
								value: selected,
							} );
						} }
					/>
					{ isPasswordRequired && (
						<BaseControl
							id={ postPasswordId }
							label={ __( 'Password', 'woocommerce' ) }
						>
							<InputControl
								id={ postPasswordId }
								value={ postPassword }
								onChange={ setPostPassword }
							/>
						</BaseControl>
					) }
				</fieldset>
			</div>
		</PanelBody>
	);
}
