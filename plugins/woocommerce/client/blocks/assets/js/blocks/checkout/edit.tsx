/**
 * External dependencies
 */
import clsx from 'clsx';
import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import { CheckoutProvider, EditorProvider } from '@woocommerce/base-context';
import {
	previewCart,
	previewSavedPaymentMethods,
} from '@woocommerce/resource-previews';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import type { TemplateArray } from '@wordpress/blocks';
import { useEffect, useRef } from '@wordpress/element';
import { getQueryArg } from '@wordpress/url';
import { dispatch, select as selectData, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { defaultFields as defaultFieldsSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './inner-blocks';
import './styles/editor.scss';
import {
	addClassToBody,
	BlockSettings,
	useBlockPropsWithLocking,
} from '../cart-checkout-shared';
import '../cart-checkout-shared/sidebar-notices';
import { CheckoutBlockContext } from './context';
import type { Attributes } from './types';

// This is adds a class to body to signal if the selected block is locked
addClassToBody();

// Array of allowed block names.
const ALLOWED_BLOCKS: string[] = [
	'woocommerce/checkout-fields-block',
	'woocommerce/checkout-totals-block',
];

export const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: {
	clientId: string;
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element => {
	const {
		showOrderNotes,
		showPolicyLinks,
		showReturnToCart,
		showRateAfterTaxName,
		cartPageId,
		isPreview = false,
		showFormStepNumbers = false,
		hasDarkControls = false,
	} = attributes;

	const fieldSettings = useSelect( ( select ) => {
		return select(
			coreStore as unknown as string
			// @ts-expect-error getEditedEntityRecord is not typed in @wordpress/core-data yet.
		).getEditedEntityRecord( 'root', 'site' ) as Record< string, string >;
	}, [] );

	const fieldsWithDefaults = {
		phone: 'optional',
		company: 'hidden',
		address_2: 'optional',
	} as const;

	const defaultFields = {
		...defaultFieldsSetting,
		...Object.fromEntries(
			Object.entries( fieldsWithDefaults ).map(
				( [ field, defaultValue ] ) => {
					const value =
						fieldSettings[
							`woocommerce_checkout_${ field }_field`
						] || defaultValue;
					return [
						field,
						{
							...defaultFieldsSetting[
								field as keyof typeof defaultFieldsSetting
							],
							required: value === 'required',
							hidden: value === 'hidden',
						},
					];
				}
			)
		),
	};

	// This focuses on the block when a certain query param is found. This is used on the link from the task list.
	const focus = useRef( getQueryArg( window.location.href, 'focus' ) );

	useEffect( () => {
		if (
			focus.current === 'checkout' &&
			! selectData( 'core/block-editor' ).hasSelectedBlock()
		) {
			dispatch( 'core/block-editor' ).selectBlock( clientId );
			dispatch( 'core/interface' ).enableComplementaryArea(
				'core/edit-site',
				'edit-site/block-inspector'
			);
		}
	}, [ clientId ] );

	const defaultTemplate = [
		[ 'woocommerce/checkout-totals-block', {}, [] ],
		[ 'woocommerce/checkout-fields-block', {}, [] ],
	] as TemplateArray;

	const blockProps = useBlockPropsWithLocking();
	return (
		<div { ...blockProps }>
			<InspectorControls>
				<BlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<EditorProvider
				isPreview={ !! isPreview }
				previewData={ {
					previewCart,
					previewSavedPaymentMethods,
					defaultFields,
				} }
			>
				<SlotFillProvider>
					<CheckoutProvider>
						<SidebarLayout
							className={ clsx( 'wc-block-checkout', {
								'has-dark-controls': hasDarkControls,
							} ) }
						>
							<CheckoutBlockContext.Provider
								value={ {
									showOrderNotes,
									showPolicyLinks,
									showReturnToCart,
									cartPageId,
									showRateAfterTaxName,
									showFormStepNumbers,
									defaultFields,
								} }
							>
								<InnerBlocks
									allowedBlocks={ ALLOWED_BLOCKS }
									template={ defaultTemplate }
									templateLock="insert"
								/>
							</CheckoutBlockContext.Provider>
						</SidebarLayout>
					</CheckoutProvider>
				</SlotFillProvider>
			</EditorProvider>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div
			{ ...useBlockProps.save( {
				className: 'wc-block-checkout is-loading',
			} ) }
		>
			<InnerBlocks.Content />
		</div>
	);
};
