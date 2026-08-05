/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { Tooltip } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import { getAdminLink } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { Badge, Stack } from '@wordpress/ui';
import { Icon, link as linkIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { VariationAttributeRow } from './attribute-rows';

export type AttributeTableColumn =
	| 'values'
	| 'defaultValue'
	| 'isVisible'
	| 'isGlobal';

function getGlobalAttributeTermsLink( attributeSlug: string ): string {
	return getAdminLink(
		addQueryArgs( 'edit-tags.php', {
			taxonomy: attributeSlug,
			post_type: 'product',
		} )
	);
}

function getBooleanLabel( value: boolean ) {
	return value ? __( 'Yes', 'woocommerce' ) : __( 'No', 'woocommerce' );
}

function EmptyDefaultValue() {
	return (
		<span className="woocommerce-variation-attributes__muted">&mdash;</span>
	);
}

function BooleanValue( { value }: { value: boolean } ) {
	const label = getBooleanLabel( value );

	if ( value ) {
		return <>{ label }</>;
	}

	return (
		<span className="woocommerce-variation-attributes__muted">
			{ label }
		</span>
	);
}

function AttributeValuePills( { values }: { values: string[] } ) {
	if ( values.length === 0 ) {
		return <EmptyDefaultValue />;
	}

	return (
		<Stack
			direction="row"
			gap="xs"
			wrap="wrap"
			className="woocommerce-variation-attributes__pill-list"
		>
			{ values.map( ( value ) => (
				<Badge
					key={ value }
					intent="none"
					className="woocommerce-variation-attributes__pill"
				>
					{ value }
				</Badge>
			) ) }
		</Stack>
	);
}

export function getAttributeTableFields(
	nameLabel: string
): Field< VariationAttributeRow >[] {
	return [
		{
			id: 'name',
			label: nameLabel,
			enableHiding: false,
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => item.name,
		},
		{
			id: 'values',
			label: __( 'Values', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => item.values.join( ' ' ),
			render: ( { item } ) => (
				<AttributeValuePills values={ item.values } />
			),
		},
		{
			id: 'defaultValue',
			label: __( 'Default value', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => item.defaultValue,
			render: ( { item } ) =>
				item.defaultValue ? (
					<Badge
						intent="none"
						className="woocommerce-variation-attributes__pill"
					>
						{ item.defaultValue }
					</Badge>
				) : (
					<EmptyDefaultValue />
				),
		},
		{
			id: 'isVisible',
			label: __( 'Visible on product page', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => getBooleanLabel( item.isVisible ),
			render: ( { item } ) => <BooleanValue value={ item.isVisible } />,
		},
		{
			id: 'isGlobal',
			label: __( 'Global', 'woocommerce' ),
			enableSorting: false,
			enableGlobalSearch: false,
			getValue: ( { item } ) => getBooleanLabel( item.isGlobal ),
			render: ( { item } ) =>
				item.isGlobal ? (
					<Tooltip
						text={ __(
							'Available across all products. Customers can filter your catalog by this attribute.',
							'woocommerce'
						) }
					>
						<a
							className="woocommerce-variation-attributes__global-link"
							href={ getGlobalAttributeTermsLink( item.slug ) }
							target="_blank"
							rel="noreferrer"
							onClick={ ( event ) => event.stopPropagation() }
						>
							{ __( 'Yes', 'woocommerce' ) }
							<Icon icon={ linkIcon } size={ 16 } />
						</a>
					</Tooltip>
				) : (
					<BooleanValue value={ item.isGlobal } />
				),
		},
	];
}
