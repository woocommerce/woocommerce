<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

use WooCommerce\RestApiDocs\Model\EndpointDescriptor;
use WooCommerce\RestApiDocs\Parser\DescriptorParser;

/**
 * Command to delete ignored endpoint descriptor files.
 */
final class PurgeIgnoredCommand implements CommandInterface
{
    public function __construct(
        private readonly DescriptorParser $descriptorParser,
        private readonly string $descriptorsDir,
    ) {
    }

    public function getName(): string
    {
        return 'purge-ignored';
    }

    public function getDescription(): string
    {
        return 'Delete ignored endpoint descriptor files';
    }

    public function execute(array $options): int
    {
        $verbose = $options['verbose'] ?? false;
        $quiet = $options['quiet'] ?? false;
        $dryRun = $options['dry-run'] ?? false;
        $all = $options['all'] ?? false;
        $filter = $options['filter'] ?? null;

        try {
            // Load all descriptors
            $allDescriptors = $this->descriptorParser->loadAll();

            // Filter to only ignored descriptors
            $ignoredDescriptors = array_filter($allDescriptors, fn($d) => $d->ignore);

            // Apply route filter if specified
            if ($filter !== null) {
                $ignoredDescriptors = array_filter(
                    $ignoredDescriptors,
                    fn($d) => $this->matchesFilter($d->route, $filter)
                );
            }

            // Unless --all is specified, only delete those with TODO: prefix in name and description
            if (!$all) {
                $ignoredDescriptors = array_filter(
                    $ignoredDescriptors,
                    fn($d) => $this->isTodoDescriptor($d)
                );
            }

            $count = count($ignoredDescriptors);

            if ($count === 0) {
                if (!$quiet) {
                    $this->output("No ignored descriptors found to delete.");
                    if (!$all) {
                        $this->output("(Use --all to delete all ignored descriptors, not just TODO: ones)");
                    }
                }
                return 0;
            }

            if (!$quiet) {
                $mode = $dryRun ? "[DRY RUN] " : "";
                $scope = $all ? "all ignored" : "TODO: ignored";
                $this->output("{$mode}Found {$count} {$scope} descriptors to delete:");
                if ($filter !== null) {
                    $this->output("Filter: {$filter}");
                }
                $this->output("");
            }

            // Track directories that might become empty
            $affectedDirs = [];

            // Delete files
            foreach ($ignoredDescriptors as $descriptor) {
                $filePath = $descriptor->filePath;
                $dir = dirname($filePath);
                $affectedDirs[$dir] = true;

                if (!$quiet) {
                    $relativePath = $this->getRelativePath($filePath);
                    $this->output("  Deleting: {$relativePath}");
                }

                if (!$dryRun) {
                    if (!unlink($filePath)) {
                        $this->error("Failed to delete: {$filePath}");
                    }
                }
            }

            // Clean up empty directories
            $emptyDirsRemoved = 0;
            if (!$dryRun) {
                $emptyDirsRemoved = $this->removeEmptyDirectories(array_keys($affectedDirs));
            } else {
                // In dry-run mode, count potential empty directories
                $emptyDirsRemoved = $this->countPotentialEmptyDirectories(array_keys($affectedDirs), $ignoredDescriptors);
            }

            if (!$quiet) {
                $this->output("");
                $mode = $dryRun ? "Would delete" : "Deleted";
                $this->output("{$mode} {$count} descriptor file(s).");
                if ($emptyDirsRemoved > 0) {
                    $dirMode = $dryRun ? "Would remove" : "Removed";
                    $this->output("{$dirMode} {$emptyDirsRemoved} empty directory(ies).");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if a descriptor has TODO: prefix in both name and description.
     */
    private function isTodoDescriptor(EndpointDescriptor $descriptor): bool
    {
        return str_starts_with($descriptor->name, 'TODO:')
            && str_starts_with($descriptor->description, 'TODO:');
    }

    /**
     * Check if a route matches the filter pattern.
     *
     * @param string $route The route to check
     * @param string|null $filter The filter pattern (regex or simple string)
     * @return bool True if the route matches or no filter is set
     */
    private function matchesFilter(string $route, ?string $filter): bool
    {
        if ($filter === null) {
            return true;
        }

        // Try as regex first
        if (@preg_match('#' . $filter . '#', $route) === 1) {
            return true;
        }

        // Fall back to simple string contains
        return str_contains($route, $filter);
    }

    /**
     * Get relative path from descriptors directory.
     */
    private function getRelativePath(string $filePath): string
    {
        if (str_starts_with($filePath, $this->descriptorsDir)) {
            return ltrim(substr($filePath, strlen($this->descriptorsDir)), '/');
        }
        return $filePath;
    }

    /**
     * Remove empty directories recursively up to the descriptors root.
     *
     * @param array<string> $directories Directories to check
     * @return int Number of directories removed
     */
    private function removeEmptyDirectories(array $directories): int
    {
        $removed = 0;

        // Collect all directories to check, including parents
        $allDirs = [];
        foreach ($directories as $dir) {
            $current = $dir;
            while ($current !== $this->descriptorsDir && str_starts_with($current, $this->descriptorsDir)) {
                $allDirs[$current] = true;
                $current = dirname($current);
            }
        }

        // Sort by depth (deepest first) to handle nested empty dirs
        $allDirs = array_keys($allDirs);
        usort($allDirs, fn($a, $b) => substr_count($b, '/') - substr_count($a, '/'));

        // Remove empty directories from deepest to shallowest
        foreach ($allDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            // Check if directory is empty
            $files = scandir($dir);
            $isEmpty = count($files) === 2; // Only . and ..

            if ($isEmpty) {
                if (rmdir($dir)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /**
     * Count directories that would become empty after deletion (for dry-run).
     *
     * @param array<string> $directories Directories to check
     * @param array<EndpointDescriptor> $descriptorsToDelete Descriptors being deleted
     * @return int Number of directories that would be removed
     */
    private function countPotentialEmptyDirectories(array $directories, array $descriptorsToDelete): int
    {
        // Build a map of files to be deleted
        $filesToDelete = [];
        foreach ($descriptorsToDelete as $d) {
            $filesToDelete[$d->filePath] = true;
        }

        $wouldRemove = 0;
        $checked = [];

        // Sort by depth (deepest first)
        usort($directories, fn($a, $b) => substr_count($b, '/') - substr_count($a, '/'));

        foreach ($directories as $dir) {
            $current = $dir;

            while ($current !== $this->descriptorsDir && str_starts_with($current, $this->descriptorsDir)) {
                if (isset($checked[$current])) {
                    break;
                }
                $checked[$current] = true;

                if (!is_dir($current)) {
                    break;
                }

                // Count files that would remain
                $files = scandir($current);
                $remainingCount = 0;
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $fullPath = $current . '/' . $file;
                    if (!isset($filesToDelete[$fullPath]) && !isset($checked[$fullPath])) {
                        $remainingCount++;
                    }
                }

                if ($remainingCount === 0) {
                    $wouldRemove++;
                    $current = dirname($current);
                } else {
                    break;
                }
            }
        }

        return $wouldRemove;
    }

    /**
     * Output a message.
     */
    private function output(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Output an error message.
     */
    private function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }
}
