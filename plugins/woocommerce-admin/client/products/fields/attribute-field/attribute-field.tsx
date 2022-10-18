/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { ProductAttribute } from '@woocommerce/data';
import { Text } from '@woocommerce/experimental';
import { Sortable, ListItem } from '@woocommerce/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './attribute-field.scss';
import AttributeEmptyStateLogo from './attribute-empty-state-logo.svg';
import { reorderSortableProductAttributePositions } from './utils';

type AttributeFieldProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
};

export const AttributeField: React.FC< AttributeFieldProps > = ( {
	value,
	onChange,
} ) => {
	const onRemove = ( attribute: ProductAttribute ) => {
		// eslint-disable-next-line no-alert
		if ( window.confirm( __( 'Remove this attribute?', 'woocommerce' ) ) ) {
			onChange( value.filter( ( attr ) => attr.id !== attribute.id ) );
		}
	};

	if ( ! value || value.length === 0 ) {
		return (
			<div className="woocommerce-attribute-field">
				<div className="woocommerce-attribute-field__empty-container">
					<img
						src={ AttributeEmptyStateLogo }
						alt="Completed"
						className="woocommerce-attribute-field__empty-logo"
					/>
					<Text
						variant="subtitle.small"
						weight="600"
						size="14"
						lineHeight="20px"
						className="woocommerce-attribute-field__empty-subtitle"
					>
						{ __( 'No attributes yet', 'woocommerce' ) }
					</Text>
					<Button
						variant="secondary"
						className="woocommerce-attribute-field__add-new"
						disabled={ true }
					>
						{ __( 'Add first attribute', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		);
	}

	const sortedAttributes = value.sort( ( a, b ) => a.position - b.position );
	const attributeKeyValues = value.reduce(
		(
			keyValue: Record< number, ProductAttribute >,
			attribute: ProductAttribute
		) => {
			keyValue[ attribute.id ] = attribute;
			return keyValue;
		},
		{} as Record< number, ProductAttribute >
	);
	return (
		<div className="woocommerce-attribute-field">
			<Sortable
				onOrderChange={ ( items ) => {
					onChange(
						reorderSortableProductAttributePositions(
							items,
							attributeKeyValues
						)
					);
				} }
			>
				{ sortedAttributes.map( ( attribute ) => (
					<ListItem key={ attribute.id }>
						<div>{ attribute.name }</div>
						<div className="woocommerce-attribute-field__attribute-options">
							{ attribute.options
								.slice( 0, 2 )
								.map( ( option, index ) => (
									<div
										className="woocommerce-attribute-field__attribute-option-chip"
										key={ index }
									>
										{ option }
									</div>
								) ) }
							{ attribute.options.length > 2 && (
								<div className="woocommerce-attribute-field__attribute-option-chip">
									{ sprintf(
										__( '+ %i more', 'woocommerce' ),
										attribute.options.length - 2
									) }
								</div>
							) }
						</div>
						<div className="woocommerce-attribute-field__attribute-actions">
							<Button variant="tertiary" disabled>
								{ __( 'edit', 'woocommerce' ) }
							</Button>
							<Button
								icon={ closeSmall }
								label={ __(
									'Remove attribute',
									'woocommerce'
								) }
								onClick={ () => onRemove( attribute ) }
							></Button>
						</div>
					</ListItem>
				) ) }
			</Sortable>
			<ListItem>
				<Button
					variant="secondary"
					className="woocommerce-attribute-field__add-attribute"
					disabled={ true }
				>
					{ __( 'Add attribute', 'woocommerce' ) }
				</Button>
			</ListItem>
		</div>
	);
};
