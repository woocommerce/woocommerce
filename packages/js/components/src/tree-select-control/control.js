/**
 * External dependencies
 */
import classnames from 'classnames';
import { noop } from 'lodash';
import { forwardRef, createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tags from './tags';
import { BACKSPACE } from './constants';

/**
 * The Control Component renders a search input and also the Tags.
 * It also triggers the setExpand for expanding the options tree on click.
 *
 * @param {Object}   props                       Component props
 * @param {Array}    props.tags                  Array of tags
 * @param {string}   props.instanceId            Id of the component
 * @param {string}   props.placeholder           Placeholder of the search input
 * @param {boolean}  props.isExpanded            True if the tree is expanded
 * @param {boolean}  props.alwaysShowPlaceholder Will always show placeholder (default: false)
 * @param {boolean}  props.disabled              True if the component is disabled
 * @param {number}   props.maxVisibleTags        The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to "Show All".
 * @param {string}   props.value                 The current input value
 * @param {Function} props.onFocus               On Focus Callback
 * @param {Function} props.onTagsChange          Callback when the Tags change
 * @param {Function} props.onInputChange         Callback when the Input value changes
 * @param {Function} [props.onControlClick]      Callback when clicking on the control.
 * @return {JSX.Element} The rendered component
 */
const Control = forwardRef(
	(
		{
			tags = [],
			instanceId,
			placeholder,
			isExpanded,
			disabled,
			maxVisibleTags,
			value = '',
			onFocus = () => {},
			onTagsChange = () => {},
			onInputChange = () => {},
			onControlClick = noop,
			alwaysShowPlaceholder = false,
		},
		ref
	) => {
		const hasTags = tags.length > 0;
		const showPlaceholder = alwaysShowPlaceholder
			? true
			: ! hasTags && ! isExpanded;

		/**
		 * Handles keydown event
		 *
		 * Keys:
		 * When key down is BACKSPACE. Delete the last tag.
		 *
		 * @param {Event} event Event object
		 */
		const handleKeydown = ( event ) => {
			if ( BACKSPACE === event.key ) {
				if ( value ) return;
				onTagsChange( tags.slice( 0, -1 ) );
				event.preventDefault();
			}
		};

		return (
			/**
			 * ESLint Disable reason
			 * https://github.com/woocommerce/woocommerce-admin/blob/main/packages/components/src/select-control/control.js#L200
			 */
			/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
			<div
				className={ classnames(
					'components-base-control',
					'woocommerce-tree-select-control__control',
					{
						'is-disabled': disabled,
						'has-tags': hasTags,
					}
				) }
				onClick={ ( e ) => {
					ref.current.focus();
					onControlClick( e );
				} }
			>
				{ hasTags && (
					<Tags
						disabled={ disabled }
						tags={ tags }
						maxVisibleTags={ maxVisibleTags }
						onChange={ onTagsChange }
					/>
				) }

				<div className="components-base-control__field">
					<input
						ref={ ref }
						id={ `woocommerce-tree-select-control-${ instanceId }__control-input` }
						type="search"
						placeholder={ showPlaceholder ? placeholder : '' }
						autoComplete="off"
						className="woocommerce-tree-select-control__control-input"
						role="combobox"
						aria-autocomplete="list"
						value={ value }
						aria-expanded={ isExpanded }
						disabled={ disabled }
						onFocus={ onFocus }
						onChange={ onInputChange }
						onKeyDown={ handleKeydown }
					/>
				</div>
			</div>
		);
	}
);

export default Control;
