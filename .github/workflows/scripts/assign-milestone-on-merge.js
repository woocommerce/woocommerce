/**
 * Assigns a milestone to a merged PR based on the checkbox selection.
 *
 * @param {Object} params - The parameters object
 * @param {Object} params.github - GitHub API client
 * @param {Object} params.context - GitHub Actions context
 * @param {Object} params.core - GitHub Actions core utilities
 */
module.exports = async ({ github, context, core }) => {
    const prNumber = context.payload.pull_request.number;
    const body = context.payload.pull_request.body || '';
    const owner = context.payload.repository.owner.login;
    const repo = context.payload.repository.name;
    const baseRef = context.payload.pull_request.base.ref;

    core.info(`Repository: ${owner}/${repo}, Base ref: ${baseRef}`);

    const existingMilestone = context.payload.pull_request.milestone;
    if (existingMilestone) {
        core.info(`Milestone "${existingMilestone.title}" is already set. Skipping automatic assignment.`);
        return;
    }

    const nextVersionMatch = body.match(/- \[([ xX])\].*\*\*.*next WooCommerce version.*\*\*/);

    if (!nextVersionMatch) {
        core.setFailed('Milestone selection checkbox not found in PR description. Cannot assign milestone.');
        return;
    }

    const nextVersionChecked = nextVersionMatch[1].toLowerCase() === 'x';

    if (!nextVersionChecked) {
        core.setFailed('Auto-assign checkbox not selected. Cannot assign milestone.');
        return;
    }

    core.info(`Fetching woocommerce.php from ${owner}/${repo}@${baseRef}`);
    const { data: fileData } = await github.rest.repos.getContent({
        owner: owner,
        repo: repo,
        path: 'plugins/woocommerce/woocommerce.php',
        ref: baseRef
    });

    const wcFileContent = Buffer.from(fileData.content, 'base64').toString('utf8');
    const versionMatch = wcFileContent.match(/^\s*\*\s*Version:\s*(.+)$/m);
    if (!versionMatch) {
        core.warning(`Could not parse WooCommerce version from woocommerce.php plugin header`);
        return;
    }

    let version = versionMatch[1].trim().replace(/-dev$/, '');
    const versionParts = version.split('.').map(Number);
    const major = versionParts[0];
    const minor = versionParts[1];

    core.info(`Parsed version: ${versionMatch[1].trim()} -> ${major}.${minor}`);

    const targetMilestone = `${major}.${minor}.0`;

    core.info(`PR #${prNumber} targets next main release`);
    core.info(`Looking for milestone: ${targetMilestone}`);

    const { data: milestones } = await github.rest.issues.listMilestones({
        owner: owner,
        repo: repo,
        state: 'open'
    });

    const milestone = milestones.find(m => m.title === targetMilestone);
    if (!milestone) {
        const warningMessage = `Could not find milestone "${targetMilestone}". Please assign a milestone manually.\n\nAvailable open milestones: ${milestones.map(m => m.title).join(', ')}`;

        await github.rest.issues.createComment({
            owner: owner,
            repo: repo,
            issue_number: prNumber,
            body: `⚠️ ${warningMessage}`
        });

        core.warning(warningMessage);
        return;
    }

    await github.rest.issues.update({
        owner: owner,
        repo: repo,
        issue_number: prNumber,
        milestone: milestone.number
    });

    core.info(`Successfully assigned milestone "${targetMilestone}" to PR #${prNumber}`);
};
