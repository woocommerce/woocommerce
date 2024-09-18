/**
 * External dependencies
 */
import { DataForm, isItemValid } from '@wordpress/dataviews';
import type { Form } from '@wordpress/dataviews';
import { createElement, useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import classNames from 'classnames';
import {
	// @ts-expect-error missing types.
	__experimentalHeading as Heading,
	// @ts-expect-error missing types.
	__experimentalText as Text,
	// @ts-expect-error missing types.
	__experimentalHStack as HStack,
	// @ts-expect-error missing types.
	__experimentalVStack as VStack,
	FlexItem,
	Button,
} from '@wordpress/components';
// @ts-expect-error missing types.
// eslint-disable-next-line @woocommerce/dependency-group
import { privateApis as editorPrivateApis } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';
import { productFields } from '../product-list/fields';

const { NavigableRegion } = unlock( editorPrivateApis );

const form: Form = {
	type: 'panel',
	fields: [ 'name', 'status' ],
};

type ProductEditProps = {
	subTitle?: string;
	className?: string;
	hideTitleFromUI?: boolean;
	actions?: React.JSX.Element;
	postType: string;
	postId: string;
};

export default function ProductEdit( {
	subTitle,
	actions,
	className,
	hideTitleFromUI = true,
	postType,
	postId = '',
}: ProductEditProps ) {
	const classes = classNames( 'edit-product-page', className, {
		'is-empty': ! postId,
	} );
	const ids = useMemo( () => postId.split( ',' ), [ postId ] );
	const { initialEdits } = useSelect(
		( select ) => {
			return {
				initialEdits:
					ids.length === 1
						? select( 'wc/admin/products' ).getProduct( ids[ 0 ] )
						: null,
			};
		},
		[ postType, ids ]
	);
	const [ edits, setEdits ] = useState( {} );
	const itemWithEdits = useMemo( () => {
		return {
			...initialEdits,
			...edits,
		};
	}, [ initialEdits, edits ] );
	const isUpdateDisabled = ! isItemValid(
		itemWithEdits,
		productFields,
		form
	);

	const onSubmit = async ( event: Event ) => {
		event.preventDefault();

		if ( ! isItemValid( itemWithEdits, productFields, form ) ) {
			return;
		}
		// Empty save.

		setEdits( {} );
	};

	return (
		<NavigableRegion
			className={ classes }
			ariaLabel={ __( 'Product Edit', 'woocommerce' ) }
		>
			<div className="edit-product-content">
				{ ! hideTitleFromUI && (
					<VStack
						className="edit-site-page-header"
						as="header"
						spacing={ 0 }
					>
						<HStack className="edit-site-page-header__page-title">
							<Heading
								as="h2"
								level={ 3 }
								weight={ 500 }
								className="edit-site-page-header__title"
								truncate
							>
								{ __( 'Product Edit', 'woocommerce' ) }
							</Heading>
							<FlexItem className="edit-site-page-header__actions">
								{ actions }
							</FlexItem>
						</HStack>
						{ subTitle && (
							<Text
								variant="muted"
								as="p"
								className="edit-site-page-header__sub-title"
							>
								{ subTitle }
							</Text>
						) }
					</VStack>
				) }
				{ ! postId && (
					<p>{ __( 'Select a product to edit', 'woocommerce' ) }</p>
				) }
				{ postId && (
					<VStack spacing={ 4 } as="form" onSubmit={ onSubmit }>
						<DataForm
							data={ itemWithEdits }
							fields={ productFields }
							form={ form }
							onChange={ setEdits }
						/>
						<FlexItem>
							<Button
								variant="primary"
								type="submit"
								// @ts-expect-error missing type.
								accessibleWhenDisabled
								disabled={ isUpdateDisabled }
								__next40pxDefaultSize
							>
								{ __( 'Update', 'woocommerce' ) }
							</Button>
						</FlexItem>
					</VStack>
				) }
			</div>
		</NavigableRegion>
	);
}
