<?php

declare(strict_types=1);

use App\Tests\TestCase;

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/TestCase.php';

$testFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/Unit'));
foreach ($testFiles as $testFile) {
    if ($testFile->isFile() && 'php' === $testFile->getExtension()) {
        require $testFile->getPathname();
    }
}

$failures = [];
$tests = 0;
$assertions = 0;

foreach (get_declared_classes() as $className) {
    if (!is_subclass_of($className, TestCase::class)) {
        continue;
    }

    $reflectionClass = new ReflectionClass($className);
    foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (!str_starts_with($method->getName(), 'test')) {
            continue;
        }

        ++$tests;
        $testCase = $reflectionClass->newInstance();

        try {
            $method->invoke($testCase);
            $assertions += $testCase->assertionCount();
            echo sprintf("[OK] %s::%s\n", $className, $method->getName());
        } catch (Throwable $throwable) {
            $failures[] = sprintf(
                "[FAIL] %s::%s\n%s: %s",
                $className,
                $method->getName(),
                $throwable::class,
                $throwable->getMessage()
            );
        }
    }
}

if ([] !== $failures) {
    echo PHP_EOL.implode(PHP_EOL.PHP_EOL, $failures).PHP_EOL;
    echo PHP_EOL.sprintf('Failures: %d, Tests: %d, Assertions: %d', count($failures), $tests, $assertions).PHP_EOL;

    exit(1);
}

echo PHP_EOL.sprintf('OK (%d tests, %d assertions)', $tests, $assertions).PHP_EOL;
