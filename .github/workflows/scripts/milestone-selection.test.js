const assert = require('node:assert/strict');
const test = require('node:test');

const { getAutoAssignMilestoneSelection } = require('./milestone-selection');

const checkboxLine =
    '- [x] Automatically assign milestone for the **[next WooCommerce version](../blob/trunk/plugins/woocommerce/woocommerce.php#L6)**';

test('detects checked auto-assign checkbox with marker comments', () => {
    const body = [
        '### Milestone',
        '',
        '<!-- milestone-target-selection -->',
        checkboxLine,
        '<!-- /milestone-target-selection -->',
    ].join('\n');

    assert.deepEqual(getAutoAssignMilestoneSelection(body), {
        found: true,
        checked: true,
    });
});

test('detects checked auto-assign checkbox without marker comments', () => {
    const body = [
        '### Milestone',
        '',
        checkboxLine,
        '',
        '> **Note:** Check the box above to have the milestone automatically assigned when merged.',
    ].join('\n');

    assert.deepEqual(getAutoAssignMilestoneSelection(body), {
        found: true,
        checked: true,
    });
});

test('detects unchecked auto-assign checkbox', () => {
    const body = checkboxLine.replace('[x]', '[ ]');

    assert.deepEqual(getAutoAssignMilestoneSelection(body), {
        found: true,
        checked: false,
    });
});

test('does not detect unrelated checklist items', () => {
    const body = '- [x] I have tested the next WooCommerce version locally.';

    assert.deepEqual(getAutoAssignMilestoneSelection(body), {
        found: false,
        checked: false,
    });
});

test('detects checked auto-assign checkbox with "*" bullet and uppercase marker', () => {
    const body = checkboxLine.replace('- [x]', '* [X]');

    assert.deepEqual(getAutoAssignMilestoneSelection(body), {
        found: true,
        checked: true,
    });
});

test('detects indented auto-assign checkbox with CRLF line endings', () => {
    const body = ['### Milestone', '', `    ${checkboxLine}`, ''].join('\r\n');

    assert.deepEqual(getAutoAssignMilestoneSelection(body), {
        found: true,
        checked: true,
    });
});

test('returns not found for null or undefined body', () => {
    const expected = { found: false, checked: false };

    assert.deepEqual(getAutoAssignMilestoneSelection(null), expected);
    assert.deepEqual(getAutoAssignMilestoneSelection(undefined), expected);
});
