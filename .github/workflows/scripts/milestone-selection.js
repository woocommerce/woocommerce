const AUTO_ASSIGN_MILESTONE_CHECKBOX_PATTERN = /(?:^|\r?\n)\s*[-*]\s*\[\s*([xX]?)\s*\][^\r\n]*Automatically assign milestone[^\r\n]*next WooCommerce version[^\r\n]*/;

/**
 * Finds the PR template checkbox used to request automatic milestone assignment.
 *
 * The pattern is non-global, so the first matching checkbox in the body wins.
 * The PR template only ever renders this checkbox once, so first-match is the
 * intended contract; duplicate occurrences (if any) are ignored.
 *
 * @param {string} body PR body.
 * @return {{found: boolean, checked: boolean}} Checkbox state.
 */
const getAutoAssignMilestoneSelection = (body) => {
    const match = (body || '').match(AUTO_ASSIGN_MILESTONE_CHECKBOX_PATTERN);

    if (!match) {
        return {
            found: false,
            checked: false,
        };
    }

    return {
        found: true,
        checked: match[1].toLowerCase() === 'x',
    };
};

module.exports = {
    getAutoAssignMilestoneSelection,
};
