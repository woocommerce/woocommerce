/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { starEmpty, starFilled } from '@wordpress/icons';
import { cleanForSlug } from '@wordpress/url';
import {
	PRODUCTS_STORE_NAME,
	WCDataSelector,
	Product,
} from '@woocommerce/data';
import { useWooBlockProps } from '@woocommerce/block-templates';
import classNames from 'classnames';
import {
	Button,
	BaseControl,
	Tooltip,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../../../components/edit-product-link-modal';
import { Label } from '../../../components/label/label';
import { useValidation } from '../../../contexts/validation-context';
import { useProductEdits } from '../../../hooks/use-product-edits';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductEditorBlockEditProps } from '../../../types';
import { AUTO_DRAFT_NAME } from '../../../utils';
import { NameBlockAttributes } from './types';

export function Edit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< NameBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	// @ts-expect-error There are no types for this.
	const { editEntityRecord, saveEntityRecord } = useDispatch( 'core' );

	const { hasEdit } = useProductEdits();

	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );

	const productId = useEntityId( 'postType', 'product' );
	const product: Product = useSelect( ( select ) =>
		// @ts-expect-error There are no types for this.
		select( 'core' ).getEditedEntityRecord(
			'postType',
			'product',
			productId
		)
	);

	const [ sku, setSku ] = useEntityProp( 'postType', 'product', 'sku' );
	const [ name, setName ] = useEntityProp< string >(
		'postType',
		'product',
		'name'
	);

	const { permalinkPrefix, permalinkSuffix } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getPermalinkParts } = select( PRODUCTS_STORE_NAME );
			if ( productId ) {
				const parts = getPermalinkParts( productId );
				return {
					permalinkPrefix: parts?.prefix,
					permalinkSuffix: parts?.suffix,
				};
			}
			return {};
		}
	);

	const {
		ref: nameRef,
		error: nameValidationError,
		validate: validateName,
	} = useValidation< Product >(
		'name',
		async function nameValidator() {
			if ( ! name || name === AUTO_DRAFT_NAME ) {
				return __( 'Name field is required.', 'woocommerce' );
			}

			if ( name.length > 120 ) {
				return __(
					'Please enter a product name shorter than 120 characters.',
					'woocommerce'
				);
			}
		},
		[ name ]
	);

	const setSkuIfEmpty = () => {
		if ( sku || nameValidationError ) {
			return;
		}
		setSku( cleanForSlug( name ) );
	};

	const help =
		nameValidationError ??
		( productId &&
			[ 'publish', 'draft' ].includes( product.status ) &&
			permalinkPrefix && (
				<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
					{ __( 'Product link', 'woocommerce' ) }
					:&nbsp;
					<a
						href={ product.permalink }
						target="_blank"
						rel="noreferrer"
					>
						{ permalinkPrefix }
						{ product.slug || cleanForSlug( name ) }
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

	// Select the block initially if it is set to autofocus.
	// (this does not get done automatically by focusing the input)
	const { selectBlock } = useDispatch( 'core/block-editor' );
	useEffect( () => {
		if ( attributes.autoFocus ) {
			selectBlock( clientId );
		}
	}, [] );

	const [ featured, setFeatured ] =
		useProductEntityProp< boolean >( 'featured' );

	function handleSuffixClick() {
		setFeatured( ! featured );
	}

	function renderFeaturedSuffix() {
		const markedText = __( 'Mark as featured', 'woocommerce' );
		const unmarkedText = __( 'Unmark as featured', 'woocommerce' );
		const tooltipText = featured ? unmarkedText : markedText;

		return (
			<Tooltip text={ tooltipText } position="top center">
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
		<>
			<div { ...blockProps }>
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
						autoFocus={ attributes.autoFocus }
						placeholder={ __(
							'e.g. 12 oz Coffee Mug',
							'woocommerce'
						) }
						onChange={ setName }
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
						product={ product }
						onCancel={ () => setShowProductLinkEditModal( false ) }
						onSaved={ () => setShowProductLinkEditModal( false ) }
						saveHandler={ async ( updatedSlug ) => {
							// @ts-expect-error There are no types for this.
							const { slug, permalink }: Product =
								await saveEntityRecord( 'postType', 'product', {
									id: product.id,
									slug: updatedSlug,
								} );

							if ( slug && permalink ) {
								editEntityRecord(
									'postType',
									'product',
									product.id,
									{
										slug,
										permalink,
									}
								);

								return {
									slug,
									permalink,
								};
							}
						} }
					/>
				) }
			</div>
		</>
	);
}
