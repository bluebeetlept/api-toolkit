<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Pagination;

use BlueBeetle\ApiToolkit\Parsers\PageParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class OffsetPaginator
{
    public function __construct(
        private PageParser $pageParser = new PageParser(),
    ) {
    }

    public function paginate(Request $request, Builder $query): LengthAwarePaginator
    {
        return $query->paginate(
            perPage: $this->pageParser->getSize($request),
            page: $this->pageParser->getNumber($request),
        );
    }
}
