interface FilterTitlePlaceholderProps {
	children?: React.ReactChildren;
}

const FilterTitlePlaceholder = ( {
	children,
}: FilterTitlePlaceholderProps ): JSX.Element => {
	return (
		<div className="wc-block-filter-title-placeholder">{ children }</div>
	);
};

export default FilterTitlePlaceholder;
