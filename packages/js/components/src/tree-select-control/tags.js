/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Tag from '../tag';

/**
 * A list of tags to display selected items.
 *
 * @param {Object}   props                    The component props
 * @param {Object[]} [props.tags=[]]          The tags
 * @param {Function} props.onChange           The method called when a tag is removed
 * @param {boolean}  props.disabled           True if the plugin is disabled
 * @param {number}   [props.maxVisibleTags=0] The maximum number of tags to show. 0 or less than 0 evaluates to "Show All".
 */
const Tags = ( {
	tags = [],
	disabled,
	maxVisibleTags = 0,
	onChange = () => {},
} ) => {
	const [ showAll, setShowAll ] = useState( false );
	const maxTags = Math.max( 0, maxVisibleTags );
	const shouldShowAll = showAll || ! maxTags;
	const visibleTags = shouldShowAll ? tags : tags.slice( 0, maxTags );

	if ( ! tags.length ) {
		return null;
	}

	/**
	 * Callback to remove a Tag.
	 * The function is defined this way because in the WooCommerce Tag Component the remove logic
	 * is defined as `onClick={ remove(key) }` hence we need to do this to avoid calling remove function
	 * on each render.
	 *
	 * @param {string} key The key for the Tag to be deleted
	 */
	const remove = ( key ) => {
		return () => {
			if ( disabled ) {
				return;
			}
			onChange( tags.filter( ( tag ) => tag.id !== key ) );
		};
	};

	return (
		<div className="woocommerce-tree-select-control__tags">
			{ visibleTags.map( ( item, i ) => {
				if ( ! item.label ) {
					return null;
				}
				const screenReaderLabel = sprintf(
					// translators: 1: Tag Label, 2: Current Tag index, 3: Total amount of tags.
					__( '%1$s (%2$d of %3$d)', 'woocommerce' ),
					item.label,
					i + 1,
					tags.length
				);
				return (
					<Tag
						key={ item.id }
						id={ item.id }
						label={ item.label }
						screenReaderLabel={ screenReaderLabel }
						remove={ remove }
					/>
				);
			} ) }

			{ maxTags > 0 && tags.length > maxTags && (
				<Button
					isTertiary
					className="woocommerce-tree-select-control__show-more"
					onClick={ () => {
						setShowAll( ! showAll );
					} }
				>
					{ showAll
						? __( 'Show less', 'woocommerce' )
						: sprintf(
								// translators: %d: The number of extra tags to show
								__( '+ %d more', 'woocommerce' ),
								tags.length - maxTags
						  ) }
				</Button>
			) }
		</div>
	);
};

export default Tags;
