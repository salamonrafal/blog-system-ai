#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Bumps the semantic version based on existing Git tags, creates a new annotated tag
 * and can optionally push the current branch together with the new tag.
 *
 * Supported tags:
 * - 1.2.3
 * - v1.2.3
 * - 1.2.3-beta-1
 * - v1.2.3-rc.1
 */

$releaseType = $argv[1] ?? null;
$shouldPush = in_array('--push', array_slice($argv, 2), true);

if (!in_array($releaseType, ['patch', 'minor', 'major'], true)) {
    fwrite(STDERR, "Usage: php bin/release-version.php [patch|minor|major] [--push]\n");
    exit(1);
}

$rootDir = dirname(__DIR__);
chdir($rootDir);

if (!is_dir($rootDir . '/.git')) {
    fwrite(STDERR, "Git repository was not found in project root.\n");
    exit(1);
}

assertCleanWorktree();

$latestTag = findLatestSemanticVersionTag();
$tagPrefix = extractTagPrefix($latestTag);
$currentVersion = parseSemanticVersion($latestTag);
$nextVersion = bumpSemanticVersion($currentVersion, $releaseType);
$nextTag = $tagPrefix . implode('.', $nextVersion);

if (gitCommand(sprintf('rev-parse -q --verify refs/tags/%s', escapeshellarg($nextTag)))) {
    fwrite(STDERR, sprintf("Tag %s already exists.\n", $nextTag));
    exit(1);
}

$tagMessage = sprintf('Release %s', $nextTag);

gitCommandOrFail(sprintf(
    'tag -a %s -m %s',
    escapeshellarg($nextTag),
    escapeshellarg($tagMessage)
));

$currentBranch = getCurrentBranchName();

if ($shouldPush) {
    if ($currentBranch === null) {
        fwrite(STDERR, "Current Git branch could not be determined. Detached HEAD is not supported for release publishing.\n");
        exit(1);
    }

    gitCommandOrFail(sprintf(
        'push origin %s %s',
        escapeshellarg($currentBranch),
        escapeshellarg($nextTag)
    ));

    fwrite(STDOUT, sprintf(
        "Created tag %s (from %s, %s bump) and pushed branch %s with the tag to origin.\n",
        $nextTag,
        $latestTag ?? '0.0.0',
        $releaseType,
        $currentBranch
    ));

    exit(0);
}

if ($currentBranch !== null) {
    fwrite(STDOUT, sprintf(
        "Created tag %s (from %s, %s bump).\nPush branch and tag with: git push origin %s %s\n",
        $nextTag,
        $latestTag ?? '0.0.0',
        $releaseType,
        escapeshellarg($currentBranch),
        escapeshellarg($nextTag)
    ));

    exit(0);
}

fwrite(STDOUT, sprintf(
    "Created tag %s (from %s, %s bump) on detached HEAD.\nPush the tag with: git push origin %s\n",
    $nextTag,
    $latestTag ?? '0.0.0',
    $releaseType,
    escapeshellarg($nextTag)
));

/**
 * @return array{0:int,1:int,2:int}
 */
function parseSemanticVersion(?string $tag): array
{
    if ($tag === null) {
        return [0, 0, 0];
    }

    if (!preg_match('/^v?(\d+)\.(\d+)\.(\d+)(?:-[0-9A-Za-z.-]+)?$/', $tag, $matches)) {
        fwrite(STDERR, sprintf("Unsupported semantic tag format: %s\n", $tag));
        exit(1);
    }

    return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
}

function extractTagPrefix(?string $tag): string
{
    if ($tag === null) {
        return 'v';
    }

    return str_starts_with($tag, 'v') ? 'v' : '';
}

/**
 * @param array{0:int,1:int,2:int} $version
 *
 * @return array{0:int,1:int,2:int}
 */
function bumpSemanticVersion(array $version, string $releaseType): array
{
    [$major, $minor, $patch] = $version;

    return match ($releaseType) {
        'major' => [$major + 1, 0, 0],
        'minor' => [$major, $minor + 1, 0],
        'patch' => [$major, $minor, $patch + 1],
    };
}

function findLatestSemanticVersionTag(): ?string
{
    $output = gitCommandOrFail('tag --list --sort=-version:refname');
    $tags = array_filter(array_map('trim', explode("\n", $output)));

    foreach ($tags as $tag) {
        if (preg_match('/^v?\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $tag) === 1) {
            return $tag;
        }
    }

    return null;
}

function assertCleanWorktree(): void
{
    $status = gitCommandOrFail('status --short');

    if (trim($status) !== '') {
        fwrite(STDERR, "Working tree is not clean. Commit or stash changes before creating a release tag.\n");
        exit(1);
    }
}

function getCurrentBranchName(): ?string
{
    $branch = trim(gitCommandOrFail('branch --show-current'));

    if ($branch === '') {
        return null;
    }

    return $branch;
}

function gitCommand(string $arguments): ?string
{
    $output = [];
    $exitCode = 0;

    exec(sprintf('git %s 2>&1', $arguments), $output, $exitCode);

    if ($exitCode !== 0) {
        return null;
    }

    return implode("\n", $output);
}

function gitCommandOrFail(string $arguments): string
{
    $output = [];
    $exitCode = 0;

    exec(sprintf('git %s 2>&1', $arguments), $output, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, implode("\n", $output) . "\n");
        exit(1);
    }

    return implode("\n", $output);
}
