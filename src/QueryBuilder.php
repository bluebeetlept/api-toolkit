<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit;

use Eufaturo\ApiToolkit\Http\SuccessResponse;
use Eufaturo\ApiToolkit\Pagination\CursorPaginator;
use Eufaturo\ApiToolkit\Pagination\OffsetPaginator;
use Eufaturo\ApiToolkit\Parsers\FilterParser;
use Eufaturo\ApiToolkit\Parsers\Filters\Filter;
use Eufaturo\ApiToolkit\Parsers\IncludeParser;
use Eufaturo\ApiToolkit\Parsers\PageParser;
use Eufaturo\ApiToolkit\Parsers\SortParser;
use Eufaturo\ApiToolkit\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class QueryBuilder
{
    /** @var class-string<resource>|null */
    private string | null $resourceClass = null;

    private Resource | null $resourceInstance = null;

    /** @var array<string, class-string<Filter>|Filter>|null */
    private array | null $filters = null;

    /** @var list<string>|null */
    private array | null $sorts = null;

    private string | null $defaultSort = null;

    /** @var list<string>|null */
    private array | null $includes = null;

    private bool $defaultSortOverridden = false;

    private function __construct(private readonly Builder $query, private readonly Request $request)
    {
    }

    /**
     * @param Builder|class-string<Model> $subject
     */
    public static function for(string | Builder $subject, Request $request): self
    {
        $query = $subject instanceof Builder ? $subject : $subject::query();

        return new self($query, $request);
    }

    /**
     * @param class-string<resource> $resourceClass
     */
    public function fromResource(string $resourceClass): self
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    /**
     * @param array<string, class-string<Filter>|Filter> $filters
     */
    public function allowedFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param list<string> $sorts
     */
    public function allowedSorts(array $sorts): self
    {
        $this->sorts = $sorts;

        return $this;
    }

    public function defaultSort(string $sort): self
    {
        $this->defaultSort = $sort;
        $this->defaultSortOverridden = true;

        return $this;
    }

    /**
     * @param list<string> $includes
     */
    public function allowedIncludes(array $includes): self
    {
        $this->includes = $includes;

        return $this;
    }

    public function paginate(): SuccessResponse
    {
        $this->applyAll();

        $pageParser = app(PageParser::class);
        $data = (new OffsetPaginator($pageParser))->paginate($this->request, $this->query);

        return $this->toSuccessResponse($data);
    }

    public function cursorPaginate(): SuccessResponse
    {
        $this->applyAll();

        $pageParser = app(PageParser::class);
        $data = (new CursorPaginator($pageParser))->paginate($this->request, $this->query);

        return $this->toSuccessResponse($data);
    }

    public function get(): SuccessResponse
    {
        $this->applyAll();

        return $this->toSuccessResponse($this->query->get());
    }

    public function apply(): static
    {
        $this->applyAll();

        return $this;
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }

    private function toSuccessResponse(mixed $data): SuccessResponse
    {
        $response = new SuccessResponse(
            data: $data,
            resource: $this->resourceClass,
        );

        $response->withRequest($this->request);

        return $response;
    }

    private function applyAll(): void
    {
        $this->applyFilters();
        $this->applySorts();
        $this->applyIncludes();
    }

    private function applyFilters(): void
    {
        $filters = $this->resolveFilters();

        if ($filters === []) {
            return;
        }

        (new FilterParser($filters))->apply($this->request, $this->query);
    }

    private function applySorts(): void
    {
        $sorts = $this->resolveSorts();
        $defaultSort = $this->resolveDefaultSort();

        (new SortParser(
            allowed: $sorts,
            default: $defaultSort,
        ))->apply($this->request, $this->query);
    }

    private function applyIncludes(): void
    {
        $includes = $this->resolveIncludes();

        if ($includes === []) {
            return;
        }

        (new IncludeParser($includes))->apply($this->request, $this->query);
    }

    private function resolveResource(): Resource | null
    {
        if ($this->resourceInstance !== null) {
            return $this->resourceInstance;
        }

        if ($this->resourceClass !== null) {
            $this->resourceInstance = app($this->resourceClass)->withRequest($this->request);

            return $this->resourceInstance;
        }

        return null;
    }

    /**
     * @return array<string, class-string<Filter>|Filter>
     */
    private function resolveFilters(): array
    {
        if ($this->filters !== null) {
            return $this->filters;
        }

        return $this->resolveResource()?->allowedFilters() ?? [];
    }

    /**
     * @return list<string>
     */
    private function resolveSorts(): array
    {
        if ($this->sorts !== null) {
            return $this->sorts;
        }

        return $this->resolveResource()?->allowedSorts() ?? [];
    }

    private function resolveDefaultSort(): string | null
    {
        if ($this->defaultSortOverridden) {
            return $this->defaultSort;
        }

        return $this->resolveResource()?->defaultSort();
    }

    /**
     * @return list<string>
     */
    private function resolveIncludes(): array
    {
        if ($this->includes !== null) {
            return $this->includes;
        }

        return $this->resolveResource()?->allowedIncludes() ?? [];
    }
}
