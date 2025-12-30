<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HookService
{
    /**
     * @var Collection<int, array{hook: string|array<string>, callback: callable, priority: int}>
     */
    protected Collection $items;

    public function __construct()
    {
        $this->items = collect();
    }

    public function __call(string $method, array $arguments)
    {
        return $this->items->{$method}(...$arguments);
    }

    public function register(array|string $hook, callable $callback, int $priority = 10): void
    {
        $this->items->push(compact('hook', 'callback', 'priority'));
    }

    public function apply(string $hook, ...$arguments): mixed
    {
        return $this->items->filter(function ($filter) use ($hook) {
            return (bool) array_filter((array) $filter['hook'], function ($item) use ($hook) {
                return Str::is($item, $hook);
            });
        })->sortBy('priority')->reduce(function ($value, $filter) use ($arguments) {
            return call_user_func_array($filter['callback'], [$value] + $arguments);
        }, $arguments[0] ?? null);
    }
}
