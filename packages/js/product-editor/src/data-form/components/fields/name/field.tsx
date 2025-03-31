/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DataFormControlProps } from '@wordpress/dataviews';
import { starEmpty, starFilled } from '@wordpress/icons';
import { cleanForSlug } from '@wordpress/url';
import { Product } from '@woocommerce/data';
import classNames from 'classnames';
import {
	Button,
	BaseControl,
	Tooltip,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../../../../components/edit-product-link-modal';
import { Label } from '../../../../components/label/label';
import { useValidation } from '../../../../contexts/validation-context';
import { useProductEdits } from '../../../../hooks/use-product-edits';
import { AUTO_DRAFT_NAME, getPermalinkParts } from '../../../../utils';

// @todo: This is a temporary solution and close to a copy of the block name field.
// We need refactor this to use this Edit in the current block name field.

export function NameBlockEdit( {
	data,
	onChange,
}: DataFormControlProps< Product > ) {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const { editEntityRecord, saveEntityRecord } = useDispatch( 'core' );

	const { hasEdit } = useProductEdits();

	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );

	const productId = data.id;
	const sku = data.sku;
	const name = data.name;
	const featured = data.featured;

	const { prefix: permalinkPrefix, suffix: permalinkSuffix } =
		getPermalinkParts( data );

	const {
		ref: nameRef,
		error: nameValidationError,
		validate: validateName,
	} = useValidation< Product >(
		'name',
		async function nameValidator() {
			if ( ! name || name === AUTO_DRAFT_NAME ) {
				return {
					message: __( 'Product name is required.', 'woocommerce' ),
				};
			}

			if ( name.length > 120 ) {
				return {
					message: __(
						'Please enter a product name shorter than 120 characters.',
						'woocommerce'
					),
				};
			}
		},
		[ name ]
	);

	const setSkuIfEmpty = () => {
		if ( sku || nameValidationError ) {
			return;
		}
		onChange( { sku: cleanForSlug( name ) } );
	};

	const help =
		nameValidationError ??
		( productId &&
			[ 'publish', 'draft' ].includes( data.status ) &&
			permalinkPrefix && (
				<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
					{ __( 'Product link', 'woocommerce' ) }
					:&nbsp;
					<a href={ data.permalink } target="_blank" rel="noreferrer">
						{ permalinkPrefix }
						{ data.slug || cleanForSlug( name ) }
						{ permalinkSuffix }
					</a>
					<Button
						variant="link"
						onClick={ () => setShowProductLinkEditModal( true ) }
					>
						{ __( 'Edit', 'woocommerce' ) }
					</Button>
				</span>
			) );

	const nameControlId = useInstanceId(
		BaseControl,
		'product_name'
	) as string;

	// @todo: This relies on attribute settings from the block information, we need to find a way to get this into the DataForm.
	// useEffect( () => {
	// 	if ( field.attributes.autoFocus ) {
	// 		selectBlock( clientId );
	// 	}
	// }, [] );

	function handleSuffixClick() {
		onChange( { featured: ! featured } );
	}

	function renderFeaturedSuffix() {
		const markedText = __( 'Mark as featured', 'woocommerce' );
		const unmarkedText = __( 'Unmark as featured', 'woocommerce' );
		const tooltipText = featured ? unmarkedText : markedText;

		return (
			<Tooltip text={ tooltipText } placement="top">
				{ featured ? (
					<Button
						icon={ starFilled }
						aria-label={ unmarkedText }
						onClick={ handleSuffixClick }
					/>
				) : (
					<Button
						icon={ starEmpty }
						aria-label={ markedText }
						onClick={ handleSuffixClick }
					/>
				) }
			</Tooltip>
		);
	}

	return (
		<div>
			<BaseControl
				id={ nameControlId }
				label={
					<Label label={ __( 'Name', 'woocommerce' ) } required />
				}
				className={ classNames( {
					'has-error': nameValidationError,
				} ) }
				help={ help }
			>
				<InputControl
					id={ nameControlId }
					ref={ nameRef }
					name="name"
					// eslint-disable-next-line jsx-a11y/no-autofocus
					// autoFocus={ attributes.autoFocus }
					placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
					onChange={ ( nextValue ) => {
						onChange( { name: nextValue ?? '' } );
					} }
					value={ name && name !== AUTO_DRAFT_NAME ? name : '' }
					autoComplete="off"
					data-1p-ignore
					onBlur={ () => {
						if ( hasEdit( 'name' ) ) {
							setSkuIfEmpty();
							validateName();
						}
					} }
					suffix={ renderFeaturedSuffix() }
				/>
			</BaseControl>

			{ showProductLinkEditModal && (
				<EditProductLinkModal
					permalinkPrefix={ permalinkPrefix || '' }
					permalinkSuffix={ permalinkSuffix || '' }
					product={ data }
					onCancel={ () => setShowProductLinkEditModal( false ) }
					onSaved={ () => setShowProductLinkEditModal( false ) }
					saveHandler={ async ( updatedSlug: string ) => {
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore
						const { slug, permalink }: Product =
							await saveEntityRecord( 'postType', 'product', {
								id: data.id,
								slug: updatedSlug,
							} );

						if ( slug && permalink ) {
							editEntityRecord( 'postType', 'product', data.id, {
								slug,
								permalink,
							} );

							return {
								slug,
								permalink,
							};
						}
					} }
				/>
			) }
		</div>
	);
}
