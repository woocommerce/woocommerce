const assert = require('node:assert/strict');
const test = require('node:test');

const {
    classifyCheckRuns,
    computeOverallState,
} = require('./pr-readiness-checklist');

function checkRun(name, overrides = {}) {
    return {
        name,
        status: 'completed',
        conclusion: 'success',
        ...overrides,
    };
}

test('classifyCheckRuns: task is omitted when no check-run matches it', () => {
    const tasks = classifyCheckRuns([checkRun('Some Unrelated Check')]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: task passes when its only matching run succeeded', () => {
    const tasks = classifyCheckRuns([checkRun('Validate changelog')]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Changelog entry');
    assert.equal(tasks[0].status, 'pass');
});

test('classifyCheckRuns: task fails when any matching run failed', () => {
    const tasks = classifyCheckRuns([
        checkRun('Lint - @woocommerce/plugin-woocommerce'),
        checkRun('Lint - @woocommerce/blocks', { conclusion: 'failure' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Lint');
    assert.equal(tasks[0].status, 'fail');
});

test('classifyCheckRuns: skipped runs are treated as not applicable, not failing', () => {
    const tasks = classifyCheckRuns([
        checkRun('Validate markdown', { conclusion: 'skipped' }),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: still-running matching runs omit the task for this update', () => {
    const tasks = classifyCheckRuns([
        checkRun('PHPStan Analysis', { status: 'in_progress', conclusion: null }),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: matches dynamic test job names by bracketed testType', () => {
    const tasks = classifyCheckRuns([
        checkRun('Unit Tests - @woocommerce/plugin-woocommerce [unit:php]'),
        checkRun('E2E Tests - @woocommerce/plugin-woocommerce [e2e]', { conclusion: 'failure' }),
        checkRun('API Tests - @woocommerce/plugin-woocommerce [api]'),
    ]);
    const labels = tasks.map((task) => task.label).sort();
    assert.deepEqual(labels, ['API tests', 'E2E tests', 'Unit tests (PHP)']);
    assert.equal(
        tasks.find((task) => task.label === 'E2E tests').status,
        'fail'
    );
});

test('classifyCheckRuns: performance tests are not tracked', () => {
    const tasks = classifyCheckRuns([
        checkRun('Performance Tests - @woocommerce/plugin-woocommerce [performance]'),
    ]);
    assert.deepEqual(tasks, []);
});

test('computeOverallState: clear when there are no applicable tasks', () => {
    assert.equal(computeOverallState([]), 'clear');
});

test('computeOverallState: clear when every applicable task passed', () => {
    assert.equal(computeOverallState([{ label: 'Lint', status: 'pass' }]), 'clear');
});

test('computeOverallState: failing when any applicable task failed', () => {
    assert.equal(
        computeOverallState([
            { label: 'Lint', status: 'pass' },
            { label: 'PHPStan', status: 'fail' },
        ]),
        'failing'
    );
});
