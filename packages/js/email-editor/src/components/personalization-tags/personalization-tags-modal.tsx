/**
 * External dependencies
 */
import { ExternalLink, Modal, SearchControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import { CategoryMenu } from './category-menu';
import { CategorySection } from './category-section';
import { LinkModal } from './link-modal';
import { recordEvent, recordEventOnce } from '../../events';
import { PersonalizationTag, storeName } from '../../store';

const PersonalizationTagsModal = ( {
	onInsert,
	isOpened,
	closeCallback,
	canInsertLink = false,
	openedBy = '',
} ) => {
	const [ activeCategory, setActiveCategory ] = useState( null );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedTag, setSelectedTag ] = useState( null );
	const [ isLinkModalOpened, setIsLinkModalOpened ] = useState( false );

	const list = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsList(),
		[]
	);

	if ( isLinkModalOpened ) {
		return (
			<LinkModal
				onInsert={ ( tag, linkText ) => {
					onInsert( tag, linkText );
					setIsLinkModalOpened( false );
				} }
				isOpened={ isLinkModalOpened }
				closeCallback={ () => setIsLinkModalOpened( false ) }
				tag={ selectedTag }
			/>
		);
	}

	if ( ! isOpened ) {
		return null;
	}

	recordEventOnce( 'personalization_tags_modal_opened', { openedBy } );

	const groupedTags: Record< string, PersonalizationTag[] > = list.reduce(
		( groups, item ) => {
			const { category, name, token } = item;

			if (
				! searchQuery ||
				name.toLowerCase().includes( searchQuery.toLowerCase() ) ||
				token.toLowerCase().includes( searchQuery.toLowerCase() )
			) {
				if ( ! groups[ category ] ) {
					groups[ category ] = [];
				}
				groups[ category ].push( item );
			}
			return groups;
		},
		{} as Record< string, PersonalizationTag[] >
	);

	return (
		<Modal
			size="medium"
			title={ __( 'Personalization Tags', 'woocommerce' ) }
			onRequestClose={ () => {
				closeCallback();
				recordEvent( 'personalization_tags_modal_closed', {
					openedBy,
				} );
			} }
			className="woocommerce-personalization-tags-modal"
		>
			<p>
				{ __(
					'Insert personalization tags to dynamically fill in information and personalize your emails.',
					'woocommerce'
				) }{ ' ' }
				<ExternalLink
					href="https://kb.mailpoet.com/article/435-a-guide-to-personalisation-tags-for-tailored-newsletters#list"
					onClick={ () =>
						recordEvent(
							'personalization_tags_modal_learn_more_link_clicked',
							{ openedBy }
						)
					}
				>
					{ __( 'Learn more', 'woocommerce' ) }
				</ExternalLink>
			</p>
			<SearchControl
				onChange={ ( theSearchQuery ) => {
					setSearchQuery( theSearchQuery );
					recordEventOnce(
						'personalization_tags_modal_search_control_input_updated',
						{ openedBy }
					);
				} }
				value={ searchQuery }
			/>
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ activeCategory }
				onCategorySelect={ ( category ) => {
					setActiveCategory( category );
					recordEvent(
						'personalization_tags_modal_category_menu_clicked',
						{
							category,
							openedBy,
						}
					);
				} }
			/>
			<CategorySection
				groupedTags={ groupedTags }
				activeCategory={ activeCategory }
				onInsert={ ( insertedTag ) => {
					onInsert( insertedTag );
					recordEvent(
						'personalization_tags_modal_tag_insert_button_clicked',
						{
							insertedTag,
							activeCategory,
							openedBy,
						}
					);
				} }
				closeCallback={ closeCallback }
				canInsertLink={ canInsertLink }
				openLinkModal={ ( tag ) => {
					setSelectedTag( tag );
					setIsLinkModalOpened( true );
				} }
			/>
		</Modal>
	);
};

export { PersonalizationTagsModal };
