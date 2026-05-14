/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import Tag from './tag';
import type { TagItem } from './types';

/**
 * Component props interface
 */
interface TagsProps {
	/** The tags to display */
	tags?: TagItem[];
	/** The method called when a tag is removed */
	onChange?: ( tags: TagItem[] ) => void;
	/** True if the component is disabled */
	disabled?: boolean;
	/** The maximum number of tags to show. 0 or less than 0 evaluates to "Show All". */
	maxVisibleTags?: number;
	/** IDs of tags that cannot be removed */
	nonRemovableIds?: string[];
	/** Callback when a tag is clicked */
	onTagClick?: () => void;
}

/**
 * A list of tags to display selected items.
 */
const Tags = ( {
	tags = [],
	disabled = false,
	maxVisibleTags = 0,
	onChange = () => {},
	nonRemovableIds = [],
	onTagClick,
}: TagsProps ) => {
	const [ showAll, setShowAll ] = useState( false );
	const maxTags = Math.max( 0, maxVisibleTags );
	const shouldShowAll = showAll || ! maxTags;
	const visibleTags = shouldShowAll ? tags : tags.slice( 0, maxTags );

	if ( ! tags.length ) {
		return null;
	}

	/**
	 * Callback to remove a Tag.
	 * The function is defined this way because in the Tag Component the remove logic
	 * is defined as `onClick={ remove(key) }` hence we need to do this to avoid calling remove function
	 * on each render.
	 *
	 * @param key The key for the Tag to be deleted
	 */
	const remove = ( key: string | number | undefined ) => {
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
				const isRemovable = ! nonRemovableIds.includes(
					String( item.id )
				);
				return (
					<Tag
						key={ item.id }
						id={ item.id }
						label={ item.label }
						screenReaderLabel={ screenReaderLabel }
						remove={ remove }
						removable={ isRemovable }
						onClick={ onTagClick }
					/>
				);
			} ) }

			{ maxTags > 0 && tags.length > maxTags && (
				<Button
					variant="minimal"
					size="small"
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
