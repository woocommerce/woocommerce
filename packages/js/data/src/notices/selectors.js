export const getNotices = ( state ) => {
	return state.notices || {};
};

export const getNoticesError = ( state ) => {
	return state.errors.getNotices || false;
};

export const getDismissNoticeError = ( state, selector ) => {
	return state.errors[ selector ] || false;
};
