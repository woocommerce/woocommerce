/**
 * External dependencies
 */
import { createElement, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Product, ProductCatalogVisibility } from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
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

	const [ requiredPasswordChecked, setRequiredPasswordChecked ] = useState(
		Boolean( postPassword )
	);

	useEffect( () => {
		if ( postPassword !== '' ) {
			setRequiredPasswordChecked( true );
		}
	}, [ postPassword ] );

	const postPasswordId = useInstanceId(
		BaseControl,
		'post_password'
	) as string;

	console.log( 'requiredPasswordChecked:', requiredPasswordChecked );
	console.log( 'postPassword:', postPassword );
	console.log( 'reviewsAllowed:', reviewsAllowed );

	function handleVisibilityChange(
		selected: boolean,
		visibility: ProductCatalogVisibility
	) {
		if ( selected ) {
			if ( catalogVisibility === 'visible' ) {
				setCatalogVisibility( visibility );
				return;
			}
			setCatalogVisibility( 'hidden' );
		} else {
			if ( catalogVisibility === 'hidden' ) {
				if ( visibility === 'catalog' ) {
					setCatalogVisibility( 'search' );
					return;
				}
				if ( visibility === 'search' ) {
					setCatalogVisibility( 'catalog' );
					return;
				}
				return;
			}
			setCatalogVisibility( 'visible' );
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
						onChange={ setReviewsAllowed }
					/>
					<CheckboxControl
						label={ __( 'Require a password', 'woocommerce' ) }
						checked={ requiredPasswordChecked }
						onChange={ setRequiredPasswordChecked }
					/>
					{ requiredPasswordChecked && (
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
