<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Bruno;

use BlueBeetle\ApiToolkit\OpenApi\EndpointDefinition;
use BlueBeetle\ApiToolkit\OpenApi\SchemaBuilder;
use Illuminate\Support\Str;
use ReflectionMethod;
use Throwable;

final class BrunoCollectionBuilder
{
    private readonly SchemaBuilder $schemaBuilder;

    public function __construct(
        private readonly string $name,
        private readonly string $baseUrl = '{{host}}',
    ) {
        $this->schemaBuilder = new SchemaBuilder();
    }

    public function buildCollectionJson(): string
    {
        return json_encode([
            'version' => '1',
            'name' => $this->name,
            'type' => 'collection',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    public function buildCollectionBru(): string
    {
        return <<<'BRU'
            auth {
              mode: bearer
            }

            auth:bearer {
              token: {{apiToken}}
            }

            BRU;
    }

    /**
     * @param list<string> $secretVars
     */
    public function buildEnvironmentBru(string $name, string $host, string $token = '', array $secretVars = []): string
    {
        $bru = <<<BRU
            vars {
              host: {$host}
              apiToken: {$token}
            }
            BRU;

        if ($secretVars !== []) {
            $bru .= "\n".$this->buildSecretVarsBlock($secretVars);
        }

        return $bru."\n";
    }

    /**
     * @param list<EndpointDefinition> $endpoints
     *
     * @return list<string>
     */
    public function resolveSecretVars(array $endpoints): array
    {
        $vars = [];

        foreach ($endpoints as $endpoint) {
            $resourceName = $this->schemaBuilder->schemaName($endpoint->resourceClass);
            $vars[] = Str::camel($resourceName).'Id';
        }

        $vars = array_unique($vars);
        sort($vars);

        return array_values($vars);
    }

    /**
     * @param list<string> $vars
     */
    public function buildSecretVarsBlock(array $vars): string
    {
        $items = implode(",\n", array_map(fn (string $v): string => "  {$v}", $vars));

        return "vars:secret [\n{$items}\n]\n";
    }

    /**
     * @param list<EndpointDefinition> $endpoints
     *
     * @return array<string, array<string, string>> Keyed by folder name, then filename => content
     */
    public function buildEndpoints(array $endpoints): array
    {
        $folders = [];

        foreach ($endpoints as $endpoint) {
            $folderName = $this->resolveFolderName($endpoint);

            foreach ($endpoint->httpMethods as $httpMethod) {
                $method = mb_strtoupper($httpMethod);
                $fileName = $this->resolveFileName($endpoint, $method);
                $seq = $this->resolveSequence($endpoint, $method);
                $content = $this->buildRequestBru($endpoint, $method, $seq);

                $folders[$folderName][$fileName] = $content;
            }
        }

        return $folders;
    }

    private function buildRequestBru(EndpointDefinition $endpoint, string $method, int $seq): string
    {
        $name = $this->resolveName($endpoint, $method);
        $url = $this->resolveUrl($endpoint, $method);
        $methodLower = mb_strtolower($method);

        $bru = <<<BRU
            meta {
              name: {$name}
              type: http
              seq: {$seq}
            }

            {$methodLower} {
              url: {$url}
              body: none
              auth: inherit
            }
            BRU;

        $preRequest = $this->buildPreRequestScript($endpoint, $method);

        if ($preRequest !== null) {
            $bru .= "\n\n".$preRequest;
        }

        $postResponse = $this->buildPostResponseScript($endpoint, $method);

        if ($postResponse !== null) {
            $bru .= "\n\n".$postResponse;
        }

        return $bru."\n";
    }

    private function buildPreRequestScript(EndpointDefinition $endpoint, string $method): string | null
    {
        if (! in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return null;
        }

        if ($endpoint->formRequestClass === null) {
            return null;
        }

        $fields = $this->extractFieldsFromFormRequest($endpoint->formRequestClass);

        if ($fields === []) {
            return null;
        }

        $body = $this->buildJsObject($fields, 2);

        return <<<BRU
            script:pre-request {
              req.setBody({$body});
            }
            BRU;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFieldsFromFormRequest(string $formRequestClass): array
    {
        if (! method_exists($formRequestClass, 'rules')) {
            return [];
        }

        try {
            $request = new $formRequestClass();
            $rules = $request->rules();
        } catch (Throwable) {
            // If rules() requires request context (e.g. $this->company()),
            // fall back to reading field names via reflection
            $rules = $this->extractRuleKeysViaReflection($formRequestClass);
        }

        if ($rules === []) {
            return [];
        }

        $examples = $this->extractExamplesFromSource($formRequestClass);

        // Separate top-level and nested fields
        $topLevel = [];
        $nested = [];

        foreach ($rules as $field => $fieldRules) {
            // Skip wildcard rules like 'items.*'
            if (str_contains($field, '*')) {
                continue;
            }

            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            if (str_contains($field, '.')) {
                $parts = explode('.', $field);
                $parent = $parts[0];
                $child = implode('.', array_slice($parts, 1));
                $nested[$parent][$child] = isset($examples[$field])
                    ? "'".$examples[$field]."'"
                    : $this->guessPlaceholder($child, $ruleList);
            } else {
                $topLevel[$field] = isset($examples[$field])
                    ? "'".$examples[$field]."'"
                    : $this->guessPlaceholder($field, $ruleList);
            }
        }

        // Replace top-level fields that have nested children with the nested structure
        foreach ($nested as $parent => $children) {
            $topLevel[$parent] = $children;
        }

        return $topLevel;
    }

    /**
     * Extract @example values from docblocks above rule definitions.
     *
     * @return array<string, string>
     */
    private function extractExamplesFromSource(string $formRequestClass): array
    {
        try {
            $reflection = new ReflectionMethod($formRequestClass, 'rules');
            $filename = $reflection->getFileName();

            if ($filename === false || ! file_exists($filename)) {
                return [];
            }

            $lines = file($filename);

            if ($lines === false) {
                return [];
            }

            $methodLines = array_slice(
                $lines,
                $reflection->getStartLine() - 1,
                $reflection->getEndLine() - $reflection->getStartLine() + 1,
            );

            $examples = [];
            $pendingExample = null;

            foreach ($methodLines as $line) {
                $trimmed = mb_trim($line);

                // Capture @example value from docblock
                if (preg_match('/@example\s+(.+)$/', $trimmed, $match)) {
                    $pendingExample = mb_trim($match[1]);

                    continue;
                }

                // If we have a pending example, attach it to the next field
                if ($pendingExample !== null && preg_match("/['\"]([a-zA-Z_][a-zA-Z0-9_.]*)['\"]\\s*=>/", $trimmed, $match)) {
                    $examples[$match[1]] = $pendingExample;
                    $pendingExample = null;

                    continue;
                }

                // Reset pending if we hit a non-comment, non-field line
                if ($pendingExample !== null && $trimmed !== '' && ! str_starts_with($trimmed, '*') && ! str_starts_with($trimmed, '//') && ! str_starts_with($trimmed, '/**')) {
                    $pendingExample = null;
                }
            }

            return $examples;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function extractRuleKeysViaReflection(string $formRequestClass): array
    {
        try {
            $reflection = new ReflectionMethod($formRequestClass, 'rules');
            $filename = $reflection->getFileName();

            if ($filename === false || ! file_exists($filename)) {
                return [];
            }

            $lines = file($filename);

            if ($lines === false) {
                return [];
            }

            $source = implode('', array_slice(
                $lines,
                $reflection->getStartLine() - 1,
                $reflection->getEndLine() - $reflection->getStartLine() + 1,
            ));

            // Extract field names from patterns like 'field_name' => [...]
            preg_match_all("/['\"]([a-zA-Z_][a-zA-Z0-9_.]*)['\"]\\s*=>/", $source, $matches);

            $rules = [];

            foreach ($matches[1] as $field) {
                $rules[$field] = [];
            }

            return $rules;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param list<mixed> $rules
     */
    private function guessPlaceholder(string $field, array $rules): string
    {
        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            if ($rule === 'integer' || $rule === 'numeric') {
                return '0';
            }

            if ($rule === 'boolean') {
                return 'true';
            }

            if ($rule === 'email') {
                return "'email@example.com'";
            }
        }

        if (str_ends_with($field, '_id')) {
            $varName = Str::camel(str_replace('_id', '', $field)).'Id';

            return "bru.getEnvVar('{$varName}')";
        }

        return "''";
    }

    private function buildJsObject(array $fields, int $indent): string
    {
        if ($fields === []) {
            return '{}';
        }

        $pad = str_repeat('  ', $indent);
        $lines = [];

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $nested = $this->buildJsObject($value, $indent + 1);
                $lines[] = "{$pad}{$key}: {$nested},";
            } else {
                $lines[] = "{$pad}{$key}: {$value},";
            }
        }

        $innerPad = str_repeat('  ', $indent - 1);

        return "{\n".implode("\n", $lines)."\n{$innerPad}}";
    }

    private function buildPostResponseScript(EndpointDefinition $endpoint, string $method): string | null
    {
        $varName = $this->resolveVarName($endpoint);

        if ($method === 'GET' && $endpoint->isList) {
            return <<<BRU
                script:post-response {
                  const response = res.getBody();

                  if (response.data?.length > 0) {
                    bru.setEnvVar('{$varName}', response.data[0]?.id);
                  }
                }
                BRU;
        }

        if ($method === 'POST' && ! $this->isActionRoute($endpoint) && ! $this->targetsSpecificResource($endpoint)) {
            return <<<BRU
                script:post-response {
                  const response = res.getBody();

                  if (response.data) {
                    bru.setEnvVar('{$varName}', response.data.id);
                  }
                }
                BRU;
        }

        return null;
    }

    private function resolveFolderName(EndpointDefinition $endpoint): string
    {
        return $this->resolveResourceSegment($endpoint);
    }

    private function resolveFileName(EndpointDefinition $endpoint, string $method): string
    {
        $action = $this->resolveActionSegment($endpoint);

        if ($action !== null) {
            return Str::studly($action);
        }

        $isPostUpdate = $method === 'POST' && $this->targetsSpecificResource($endpoint);

        return match (true) {
            $method === 'GET' && $endpoint->isList => 'List',
            $method === 'GET' => 'View',
            $isPostUpdate => 'Update',
            $method === 'POST' => 'Create',
            $method === 'PUT', $method === 'PATCH' => 'Update',
            $method === 'DELETE' => 'Delete',
            default => $method,
        };
    }

    private function resolveName(EndpointDefinition $endpoint, string $method): string
    {
        $folderName = $this->resolveFolderName($endpoint);
        // Use the last segment for naming (e.g. "Clients/Addresses" -> "Addresses")
        $lastSegment = str_contains($folderName, '/') ? Str::afterLast($folderName, '/') : $folderName;
        $singular = Str::singular($lastSegment);
        $plural = $lastSegment;

        $action = $this->resolveActionSegment($endpoint);

        if ($action !== null) {
            return Str::headline($action).' '.$singular;
        }

        $isPostUpdate = $method === 'POST' && $this->targetsSpecificResource($endpoint);

        return match (true) {
            $method === 'GET' && $endpoint->isList => "List {$plural}",
            $method === 'GET' => "View {$singular}",
            $isPostUpdate => "Update {$singular}",
            $method === 'POST' => "Create {$singular}",
            $method === 'PUT', $method === 'PATCH' => "Update {$singular}",
            $method === 'DELETE' => "Delete {$singular}",
            default => "{$method} {$singular}",
        };
    }

    private function resolveUrl(EndpointDefinition $endpoint, string $method): string
    {
        $path = $endpoint->path;
        $varName = $this->resolveVarName($endpoint);

        // Strip route binding fields (e.g. {brand:public_id} -> {brand})
        $path = preg_replace('/\{(\w+):[^}]+\}/', '{$1}', $path);

        // Replace route params with the resource-derived env variable name
        // e.g. {product} -> {{productId}} (matching the post-response script convention)
        $path = preg_replace('/\{(\w+)\}/', '{{'.$varName.'}}', $path);

        // POST (create) should not have path params, but updates and action routes should keep them
        $isPostCreate = $method === 'POST' && ! $this->targetsSpecificResource($endpoint) && ! $this->isActionRoute($endpoint);

        if ($isPostCreate && preg_match('/\/\{\{[^}]+\}\}$/', $path)) {
            $path = preg_replace('/\/\{\{[^}]+\}\}$/', '', $path);
        }

        return $this->baseUrl.$path;
    }

    private function resolveSequence(EndpointDefinition $endpoint, string $method): int
    {
        if ($this->resolveActionSegment($endpoint) !== null) {
            return 6;
        }

        $isPostUpdate = $method === 'POST' && $this->targetsSpecificResource($endpoint);

        return match (true) {
            $method === 'GET' && $endpoint->isList => 1,
            $method === 'POST' && ! $isPostUpdate => 2,
            $method === 'GET' => 3,
            $isPostUpdate, $method === 'PUT', $method === 'PATCH' => 4,
            $method === 'DELETE' => 5,
            default => 7,
        };
    }

    /**
     * Extract the resource segment(s) from the path.
     *
     * /products -> Products
     * /products/{product} -> Products
     * /products/{product}/restore -> Products
     * /orders/{order}/items -> Orders/Items
     * /api/v1/shipping-methods -> Shipping Methods
     */
    private function resolveResourceSegment(EndpointDefinition $endpoint): string
    {
        $segments = $this->pathSegments($endpoint);
        $skip = ['api', 'api-'];
        $action = $this->resolveActionSegment($endpoint);
        $resourceParts = [];

        foreach ($segments as $segment) {
            if (in_array($segment, $skip, true) || preg_match('/^v\d+$/', $segment)) {
                continue;
            }

            if (str_starts_with($segment, '{')) {
                continue;
            }

            // Skip the action segment (e.g. "restore")
            if ($action !== null && $segment === $action) {
                continue;
            }

            $resourceParts[] = Str::headline(Str::plural(str_replace('-', ' ', $segment)));
        }

        if ($resourceParts !== []) {
            return implode('/', $resourceParts);
        }

        // Fallback to resource class name
        $resourceName = $this->schemaBuilder->schemaName($endpoint->resourceClass);

        return Str::plural(Str::headline($resourceName));
    }

    /**
     * Detect action segments like "restore" at the end of a path.
     *
     * /products/{product}/restore -> restore
     * /products/{product} -> null
     * /products -> null
     */
    private function resolveActionSegment(EndpointDefinition $endpoint): string | null
    {
        // List endpoints never have action segments
        if ($endpoint->isList) {
            return null;
        }

        $segments = $this->pathSegments($endpoint);
        $last = end($segments);

        if ($last === false || str_starts_with($last, '{') || preg_match('/^v\d+$/', $last)) {
            return null;
        }

        // A plural last segment after a param is a nested resource (e.g. /addresses),
        // not an action. Only singular words are actions (e.g. /restore, /archive).
        if (Str::plural($last) === $last) {
            return null;
        }

        $count = count($segments);

        if ($count < 2) {
            return null;
        }

        $beforeLast = $segments[$count - 2];

        if (str_starts_with($beforeLast, '{')) {
            return $last;
        }

        return null;
    }

    /**
     * Check if this is an action route (e.g. /restore, /archive) rather than a CRUD route.
     */
    private function isActionRoute(EndpointDefinition $endpoint): bool
    {
        return $this->resolveActionSegment($endpoint) !== null;
    }

    /**
     * Check if the route targets a specific resource (last meaningful segment is a param).
     *
     * /products/{product} -> true (targets a specific resource)
     * /products -> false (targets the collection)
     * /orders/{order}/items -> false (targets a nested collection)
     * /orders/{order}/items/{item} -> true (targets a specific nested resource)
     */
    private function targetsSpecificResource(EndpointDefinition $endpoint): bool
    {
        $segments = $this->pathSegments($endpoint);
        $last = end($segments);

        if ($last === false) {
            return false;
        }

        // If the last segment is a param, it targets a specific resource
        // If it's an action (like "restore"), check the segment before it
        if ($this->isActionRoute($endpoint)) {
            $count = count($segments);

            return $count >= 2 && str_starts_with($segments[$count - 2], '{');
        }

        return str_starts_with($last, '{');
    }

    /**
     * Resolve the env variable name from the path (uses last resource segment).
     *
     * /products -> productId
     * /shipping-methods -> shippingMethodId
     * /orders/{order}/items -> itemId
     */
    private function resolveVarName(EndpointDefinition $endpoint): string
    {
        $segments = $this->pathSegments($endpoint);
        $skip = ['api', 'api-'];
        $action = $this->resolveActionSegment($endpoint);
        $lastResource = null;

        foreach ($segments as $segment) {
            if (in_array($segment, $skip, true) || preg_match('/^v\d+$/', $segment) || str_starts_with($segment, '{')) {
                continue;
            }

            if ($action !== null && $segment === $action) {
                continue;
            }

            $lastResource = $segment;
        }

        if ($lastResource !== null) {
            return Str::camel(Str::singular($lastResource)).'Id';
        }

        $resourceName = $this->schemaBuilder->schemaName($endpoint->resourceClass);

        return Str::camel($resourceName).'Id';
    }

    /**
     * @return list<string>
     */
    private function pathSegments(EndpointDefinition $endpoint): array
    {
        return array_values(array_filter(explode('/', $endpoint->path)));
    }
}
