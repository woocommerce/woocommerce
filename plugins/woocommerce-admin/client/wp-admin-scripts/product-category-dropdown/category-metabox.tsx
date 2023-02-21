/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CATEGORY_TERM_NAME } from './category-handlers';
import { AllCategoryList } from './all-category-list';
import { CategoryTerm, PopularCategoryList } from './popular-category-list';
import { CategoryAddNew } from './category-add-new';

let initialTab = '';
if ( window.getUserSetting ) {
	initialTab = window.getUserSetting( CATEGORY_TERM_NAME + '_tab' ) || '';
}

export const CategoryMetabox: React.FC< {
	initialSelected: CategoryTerm[];
} > = ( { initialSelected } ) => {
	const [ selected, setSelected ] = useState( initialSelected );
	const [ activeTab, setActiveTab ] = useState(
		initialTab === 'pop' ? 'pop' : 'all'
	);
	return (
		<div id={ 'taxonomy-' + CATEGORY_TERM_NAME } className="categorydiv">
			<ul className="category-tabs">
				<li className={ activeTab === 'all' ? 'tabs' : '' }>
					<a
						href={ '#' + CATEGORY_TERM_NAME + '-all' }
						onClick={ ( event ) => {
							event.preventDefault();
							setActiveTab( 'all' );
							if ( window.deleteUserSetting ) {
								window.deleteUserSetting(
									CATEGORY_TERM_NAME + '_tab'
								);
							}
						} }
					>
						All items
					</a>
				</li>
				<li className={ activeTab === 'pop' ? 'tabs' : '' }>
					<a
						href={ '#' + CATEGORY_TERM_NAME + '-pop' }
						onClick={ ( event ) => {
							event.preventDefault();
							setActiveTab( 'pop' );
							if ( window.setUserSetting ) {
								window.setUserSetting(
									CATEGORY_TERM_NAME + '_tab',
									'pop'
								);
							}
						} }
					>
						Most used
					</a>
				</li>
			</ul>
			<div
				className="tabs-panel"
				id={ CATEGORY_TERM_NAME + '-pop' }
				style={ activeTab !== 'pop' ? { display: 'none' } : {} }
			>
				<ul
					id={ CATEGORY_TERM_NAME + 'checklist-pop' }
					className="categorychecklist form-no-clear"
				>
					<PopularCategoryList
						selected={ selected }
						onChange={ setSelected }
					/>
				</ul>
			</div>
			<div
				className="tabs-panel"
				id={ CATEGORY_TERM_NAME + '-all' }
				style={ activeTab !== 'all' ? { display: 'none' } : {} }
			>
				<AllCategoryList
					selected={ selected }
					onChange={ setSelected }
				/>
			</div>
			{ ( selected || [] ).map( ( sel ) => (
				<input
					key={ sel.term_id }
					type="hidden"
					value={ sel.term_id }
					name={ 'tax_input[' + CATEGORY_TERM_NAME + '][]' }
				/>
			) ) }
			{ selected.length === 0 && (
				<input
					type="hidden"
					value=""
					name={ 'tax_input[' + CATEGORY_TERM_NAME + '][]' }
				/>
			) }
			<CategoryAddNew selected={ selected } onChange={ setSelected } />
		</div>
	);
};
