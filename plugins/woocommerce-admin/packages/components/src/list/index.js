/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import LegacyListItem from './list-item';

/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list
 */
function List( props ) {
	const { className, items, children } = props;
	const listClassName = classnames( 'woocommerce-list', className );

	deprecated( 'List with items prop is deprecated', {
		hint:
			'See ExperimentalList / ExperimentalListItem for the new API that will replace this component in future versions.',
	} );

	return (
		<TransitionGroup component="ul" className={ listClassName } role="menu">
			{ items.map( ( item, index ) => {
				const { className: itemClasses, href, key, onClick } = item;
				const hasAction = typeof onClick === 'function' || href;
				const itemClassName = classnames(
					'woocommerce-list__item',
					itemClasses,
					{
						'has-action': hasAction,
					}
				);

				return (
					<CSSTransition
						key={ key || index }
						timeout={ 500 }
						classNames="woocommerce-list__item"
					>
						<li className={ itemClassName }>
							{ children ? (
								children( item, index )
							) : (
								<LegacyListItem item={ item } />
							) }
						</li>
					</CSSTransition>
				);
			} ) }
		</TransitionGroup>
	);
}

List.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * An array of list items.
	 */
	items: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Content displayed after the list item text.
			 */
			after: PropTypes.node,
			/**
			 * Content displayed before the list item text.
			 */
			before: PropTypes.node,
			/**
			 * Additional class name to style the list item.
			 */
			className: PropTypes.string,
			/**
			 * Content displayed beneath the list item title.
			 */
			content: PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.node,
			] ),
			/**
			 * Href attribute used in a Link wrapped around the item.
			 */
			href: PropTypes.string,
			/**
			 * Called when the list item is clicked.
			 */
			onClick: PropTypes.func,
			/**
			 * Target attribute used for Link wrapper.
			 */
			target: PropTypes.string,
			/**
			 * Title displayed for the list item.
			 */
			title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
			/**
			 * Unique key for list item.
			 */
			key: PropTypes.string,
		} )
	),
};

export default List;

export { ExperimentalListItem } from './experimental-list-item';
export { ExperimentalList } from './experimental-list';
