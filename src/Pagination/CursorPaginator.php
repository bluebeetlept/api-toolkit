<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Pagination;

use Eufaturo\ApiToolkit\Parsers\PageParser;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;

final readonly class CursorPaginator
{
    public function __construct(
        private PageParser $pageParser = new PageParser(),
    ) {
    }

    public function paginate(Request $request, Builder $query): CursorPaginatorContract
    {
        $cursorValue = $this->pageParser->getCursor($request);

        $cursor = $cursorValue !== null
            ? Cursor::fromEncoded($cursorValue)
            : null;

        return $query->cursorPaginate(
            perPage: $this->pageParser->getSize($request),
            cursor: $cursor,
        );
    }
}
