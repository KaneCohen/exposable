<?php

/** Run from the repository with PHP 8.4: php tools/test-matrix.php [row ...]. */
$root = dirname(__DIR__);
$rows = [
    'laravel10-carbon2' => ['10.50.3', '^8.39.0', '2.73.0', true],
    'laravel11-carbon2' => ['^11.57.0', '^9.18.0', '2.73.0', true],
    'laravel11-carbon3-min' => ['^11.57.0', '^9.18.0', '3.8.4', true],
    'laravel11-carbon3' => ['^11.57.0', '^9.18.0', '^3.8.4', true],
    'laravel12-carbon3' => ['^12.69.3', '^10.12.0', '^3.8.4', false],
    'laravel13-carbon3' => ['^13.34.0', '^11.3.0', '^3.8.4', false],
];
$requested = array_slice($argv, 1) ?: array_keys($rows);
$base = getenv('EXPOSABLE_MATRIX_DIR') ?: sys_get_temp_dir().'/exposable-matrix-'.date('Ymd-His');
if (!is_dir($base)) {
    mkdir($base, 0777, true);
}
echo "Artifacts: $base\n";
$php = [PHP_BINARY];
if (php_ini_loaded_file()) {
    array_push($php, '-c', php_ini_loaded_file());
}
array_push($php, '-d', 'error_reporting=32767');
$composer = getenv('EXPOSABLE_COMPOSER') ?: 'composer';
// Resolve the Composer executable without relying on its PHP shebang.
if (!str_contains($composer, '/')) {
    foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $path) {
        if (is_file($path.'/'.$composer)) {
            $composer = $path.'/'.$composer;
            break;
        }
    }
}
function copyTree(string $source, string $target): void
{
    mkdir($target, 0777, true);
    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) {
            continue;
        }
        if ($entry->isDir()) {
            copyTree($entry->getPathname(), $target.'/'.$entry->getFilename());
        } else {
            copy($entry->getPathname(), $target.'/'.$entry->getFilename());
        }
    }
}
function run(array $command, string $cwd, string $log): int
{
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]], $pipes, $cwd);
    return proc_close($process);
}
$failed = false;
foreach ($requested as $name) {
    if (!isset($rows[$name])) {
        throw new InvalidArgumentException('Unknown row: '.$name);
    }
    [$laravel, $testbench, $carbon, $checkpoint] = $rows[$name];
    $dir = $base.'/'.$name;
    if (file_exists($dir)) {
        throw new RuntimeException('Refusing to overwrite '.$dir);
    }
    mkdir($dir, 0777, true);
    foreach (['src', 'tests', 'config'] as $tree) {
        copyTree($root.'/'.$tree, $dir.'/'.$tree);
    }
    copy($root.'/phpunit.xml.dist', $dir.'/phpunit.xml.dist');
    copy($root.'/phpunit10.xml.dist', $dir.'/phpunit10.xml.dist');
    mkdir($dir.'/tools');
    copy($root.'/tools/test.php', $dir.'/tools/test.php');
    $json = json_decode(file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $json['require']['laravel/framework'] = $laravel;
    $json['require']['nesbot/carbon'] = $carbon;
    $json['require-dev']['orchestra/testbench'] = $testbench;
    file_put_contents($dir.'/composer.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    $command = [...$php, $composer, 'update', '--no-interaction', '--no-progress'];
    if ($checkpoint) {
        // Explicit historical test installation, never a production recommendation.
        $command[] = '--no-security-blocking';
    }
    echo "$name: resolving tagged dependencies\n";
    if (run($command, $dir, $dir.'/composer.log') !== 0) {
        echo "$name: dependency resolution FAILED (see composer.log)\n";
        $failed = true;
        continue;
    }
    $lock = json_decode(file_get_contents($dir.'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
    $versions = [];
    foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
        if (in_array($package['name'], ['laravel/framework', 'orchestra/testbench', 'nesbot/carbon', 'league/uri', 'league/uri-components', 'phpunit/phpunit', 'mockery/mockery'], true)) {
            $versions[$package['name']] = $package['version'];
        }
    }
    if (str_starts_with($versions['phpunit/phpunit'], '10.')) {
        $config = file_get_contents($dir.'/phpunit10.xml.dist');
        file_put_contents($dir.'/phpunit.xml.dist', $config);
    }
    $display = ['--display-deprecations', '--display-errors', '--display-notices', '--display-warnings'];
    $status = run([...$php, 'vendor/bin/phpunit', ...$display, '--log-junit', 'junit.xml'], $dir, $dir.'/tests.log');
    // Retain a separate, unfiltered E_ALL report for framework/dependency issues.
    $config = str_replace([' ignoreIndirectDeprecations="true"', ' restrictDeprecations="true"'], '', file_get_contents($dir.'/phpunit.xml.dist'));
    file_put_contents($dir.'/phpunit.upstream.xml', $config);
    $upstreamStatus = run([...$php, 'vendor/bin/phpunit', '-c', 'phpunit.upstream.xml', ...$display, '--do-not-fail-on-deprecation'], $dir, $dir.'/upstream.log');
    file_put_contents($dir.'/result.json', json_encode(['php' => PHP_VERSION, 'versions' => $versions, 'package_suite_exit' => $status, 'upstream_suite_exit' => $upstreamStatus], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo "$name: package suite ".($status === 0 ? 'PASS' : 'FAIL')."; upstream report exit $upstreamStatus\n";
    echo json_encode($versions, JSON_UNESCAPED_SLASHES)."\n";
    $failed = $failed || $status !== 0 || $upstreamStatus !== 0;
}
exit($failed ? 1 : 0);
