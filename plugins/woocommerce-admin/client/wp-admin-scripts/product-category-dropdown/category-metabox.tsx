/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CATEGORY_TERM_NAME } from './category-handlers';
import { AllCategoryList } from './all-category-list';
import { CategoryTerm, PopularCategoryList } from './popular-category-list';

const initialHash = window.location.hash.substr( 1 );
export const CategoryMetabox: React.FC< {
	initialSelected: CategoryTerm[];
} > = ( { initialSelected } ) => {
	const [ selected, setSelected ] = useState( initialSelected );
	const [ activeTab, setActiveTab ] = useState(
		initialHash === CATEGORY_TERM_NAME + '-pop' ? 'pop' : 'all'
	);
	return (
		<div id={ 'taxonomy-' + CATEGORY_TERM_NAME } className="categorydiv">
			<ul className="category-tabs">
				<li className={ activeTab === 'all' ? 'tabs' : '' }>
					<a
						href={ '#' + CATEGORY_TERM_NAME + '-all' }
						onClick={ () => setActiveTab( 'all' ) }
					>
						All items
					</a>
				</li>
				<li className={ activeTab === 'pop' ? 'tabs' : '' }>
					<a
						href={ '#' + CATEGORY_TERM_NAME + '-pop' }
						onClick={ () => setActiveTab( 'pop' ) }
					>
						Most used
					</a>
				</li>
			</ul>
			<div
				id={ CATEGORY_TERM_NAME + '-pop' }
				className="tabs-panel"
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
				id={ CATEGORY_TERM_NAME + '-all' }
				className="tabs-panel"
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
		</div>
	);
};
