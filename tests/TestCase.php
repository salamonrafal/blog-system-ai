<?php

declare(strict_types=1);

namespace App\Tests;

abstract class TestCase
{
    private int $assertions = 0;

    final protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        ++$this->assertions;

        if ($expected !== $actual) {
            throw new \RuntimeException($message ?: sprintf('Expected %s, got %s.', $this->export($expected), $this->export($actual)));
        }
    }

    final protected function assertTrue(bool $condition, string $message = ''): void
    {
        ++$this->assertions;

        if (!$condition) {
            throw new \RuntimeException($message ?: 'Expected condition to be true.');
        }
    }

    final protected function assertFalse(bool $condition, string $message = ''): void
    {
        ++$this->assertions;

        if ($condition) {
            throw new \RuntimeException($message ?: 'Expected condition to be false.');
        }
    }

    final protected function assertNull(mixed $actual, string $message = ''): void
    {
        ++$this->assertions;

        if (null !== $actual) {
            throw new \RuntimeException($message ?: sprintf('Expected null, got %s.', $this->export($actual)));
        }
    }

    final protected function assertInstanceOf(string $expectedClass, mixed $actual, string $message = ''): void
    {
        ++$this->assertions;

        if (!$actual instanceof $expectedClass) {
            throw new \RuntimeException($message ?: sprintf('Expected instance of %s.', $expectedClass));
        }
    }

    final protected function assertThrows(string $expectedClass, callable $callback, string $expectedMessage = ''): void
    {
        ++$this->assertions;

        try {
            $callback();
        } catch (\Throwable $throwable) {
            if (!$throwable instanceof $expectedClass) {
                throw new \RuntimeException(sprintf('Expected %s, got %s.', $expectedClass, $throwable::class), 0, $throwable);
            }

            if ('' !== $expectedMessage && $expectedMessage !== $throwable->getMessage()) {
                throw new \RuntimeException(sprintf('Expected exception message "%s", got "%s".', $expectedMessage, $throwable->getMessage()), 0, $throwable);
            }

            return;
        }

        throw new \RuntimeException(sprintf('Expected exception %s was not thrown.', $expectedClass));
    }

    final public function assertionCount(): int
    {
        return $this->assertions;
    }

    private function export(mixed $value): string
    {
        return var_export($value, true);
    }
}
