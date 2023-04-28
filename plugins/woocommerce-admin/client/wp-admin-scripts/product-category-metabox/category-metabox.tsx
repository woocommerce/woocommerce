/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';

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

const CATEGORY_POPULAR_TAB_ID = 'pop';
const CATEGORY_ALL_TAB_ID = 'all';

const CategoryMetabox: React.FC< {
	initialSelected: CategoryTerm[];
} > = ( { initialSelected } ) => {
	const [ selected, setSelected ] = useState( initialSelected );
	const allCategoryListRef = useRef< { resetInitialValues: () => void } >(
		null
	);
	const [ activeTab, setActiveTab ] = useState(
		initialTab === CATEGORY_POPULAR_TAB_ID
			? initialTab
			: CATEGORY_ALL_TAB_ID
	);
	return (
		<div
			id={ 'taxonomy-' + CATEGORY_TERM_NAME }
			className="categorydiv category-async-metabox"
		>
			<ul className="category-tabs">
				<li
					className={
						activeTab === CATEGORY_ALL_TAB_ID ? 'tabs' : ''
					}
				>
					<a
						href={
							'#' + CATEGORY_TERM_NAME + '-' + CATEGORY_ALL_TAB_ID
						}
						onClick={ ( event ) => {
							event.preventDefault();
							setActiveTab( CATEGORY_ALL_TAB_ID );
							if ( window.deleteUserSetting ) {
								window.deleteUserSetting(
									CATEGORY_TERM_NAME + '_tab'
								);
							}
						} }
					>
						{ __( 'All items', 'woocommerce' ) }
					</a>
				</li>
				<li
					className={
						activeTab === CATEGORY_POPULAR_TAB_ID ? 'tabs' : ''
					}
				>
					<a
						href={
							'#' +
							CATEGORY_TERM_NAME +
							'-' +
							CATEGORY_POPULAR_TAB_ID
						}
						onClick={ ( event ) => {
							event.preventDefault();
							setActiveTab( CATEGORY_POPULAR_TAB_ID );
							if ( window.setUserSetting ) {
								window.setUserSetting(
									CATEGORY_TERM_NAME + '_tab',
									CATEGORY_POPULAR_TAB_ID
								);
							}
						} }
					>
						{ __( 'Most used', 'woocommerce' ) }
					</a>
				</li>
			</ul>
			<div
				className="tabs-panel"
				id={ CATEGORY_TERM_NAME + '-' + CATEGORY_POPULAR_TAB_ID }
				style={
					activeTab !== CATEGORY_POPULAR_TAB_ID
						? { display: 'none' }
						: {}
				}
			>
				<ul
					id={
						CATEGORY_TERM_NAME +
						'checklist-' +
						CATEGORY_POPULAR_TAB_ID
					}
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
				id={ CATEGORY_TERM_NAME + '-' + CATEGORY_ALL_TAB_ID }
				style={
					activeTab !== CATEGORY_ALL_TAB_ID ? { display: 'none' } : {}
				}
			>
				<AllCategoryList
					selectedCategoryTerms={ selected }
					onChange={ setSelected }
					ref={ allCategoryListRef }
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
			<CategoryAddNew
				selectedCategoryTerms={ selected }
				onChange={ ( sel ) => {
					setSelected( sel );
					if ( allCategoryListRef.current ) {
						allCategoryListRef.current.resetInitialValues();
					}
				} }
			/>
		</div>
	);
};

export default CategoryMetabox;
