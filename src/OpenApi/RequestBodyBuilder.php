<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\OpenApi;

use BackedEnum;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionClass;
use ReflectionProperty;
use Stringable;
use Throwable;

/**
 * Describes a form request's body from its validation rules: dotted keys become nested objects, `*` keys become
 * arrays, and a comment above a rule supplies its description and `@example`.
 *
 * @phpstan-type RuleNode array{rules: list<mixed>, children: array<array-key, mixed>}
 */
final class RequestBodyBuilder
{
    /**
     * Beyond this many values, such as every country subdivision, an enum buries the field it describes.
     */
    private const int ENUM_LIMIT = 100;

    /**
     * @var (Closure(FormRequest): void)|null
     */
    private static Closure | null $preparer = null;

    /**
     * Rules often reach for context only a real request carries, such as the authenticated user or the tenant it
     * belongs to. Give requests a stand-in for it here, before their rules are read.
     *
     * @param (Closure(FormRequest): void)|null $callback
     */
    public static function prepareRequestsUsing(Closure | null $callback): void
    {
        self::$preparer = $callback;
    }

    /**
     * @param class-string $formRequestClass
     *
     * @return array<string, mixed>|null
     */
    public function build(string $formRequestClass, string $httpMethod): array | null
    {
        $rules = $this->rules($formRequestClass, $httpMethod);

        if ($rules === null || $rules === []) {
            return null;
        }

        $comments = $this->ruleComments($formRequestClass);

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => $this->schemaFor($this->tree($rules), '', $comments),
                ],
            ],
        ];
    }

    /**
     * @param class-string $formRequestClass
     *
     * @return array<array-key, mixed>|null
     */
    private function rules(string $formRequestClass, string $httpMethod): array | null
    {
        if (! is_subclass_of($formRequestClass, FormRequest::class) || ! method_exists($formRequestClass, 'rules')) {
            return null;
        }

        try {
            $container = Container::getInstance();

            $request = $formRequestClass::createFrom(Request::create('/', $httpMethod));
            $request->setContainer($container);

            if (self::$preparer !== null) {
                (self::$preparer)($request);
            }

            $rules = $container->call([$request, 'rules']);
        } catch (Throwable) {
            return null;
        }

        return is_array($rules) ? $rules : null;
    }

    /**
     * @param array<array-key, mixed> $rules
     *
     * @return RuleNode
     */
    private function tree(array $rules): array
    {
        $root = ['rules' => [], 'children' => []];

        foreach ($rules as $field => $fieldRules) {
            $node = &$root;

            foreach (explode('.', (string) $field) as $segment) {
                $node['children'][$segment] ??= ['rules' => [], 'children' => []];
                $node = &$node['children'][$segment];
            }

            $node['rules'] = $this->normalize($fieldRules);

            unset($node);
        }

        return $root;
    }

    /**
     * @return list<mixed>
     */
    private function normalize(mixed $fieldRules): array
    {
        if (is_string($fieldRules)) {
            return explode('|', $fieldRules);
        }

        if (is_array($fieldRules)) {
            return array_values($fieldRules);
        }

        return [$fieldRules];
    }

    /**
     * @param RuleNode                                                     $node
     * @param array<string, array{description?: string, example?: string}> $comments
     *
     * @return array<string, mixed>
     */
    private function schemaFor(array $node, string $path, array $comments): array
    {
        $rules = $this->ruleStrings($node['rules']);
        $children = $node['children'];

        if (isset($children['*'])) {
            $schema = ['type' => 'array', 'items' => $this->openWhenEmpty($this->schemaFor($children['*'], $this->childPath($path, '*'), $comments))];
        } elseif ($children !== []) {
            $schema = ['type' => 'object', 'properties' => []];
            $required = [];

            foreach ($children as $key => $child) {
                $childPath = $this->childPath($path, (string) $key);
                $schema['properties'][$key] = $this->openWhenEmpty($this->schemaFor($child, $childPath, $comments));

                if (in_array('required', $this->ruleStrings($child['rules']), true)) {
                    $required[] = (string) $key;
                }
            }

            if ($required !== []) {
                $schema['required'] = $required;
            }
        } else {
            $schema = $this->scalarSchema($rules);
        }

        $schema = $this->applyEnum($schema, $node['rules']);
        $schema = $this->applyConstraints($schema, $rules);
        $schema = $this->applyComment($schema, $comments[$path] ?? [], $this->conditionsFor($rules));

        if (in_array('nullable', $rules, true) && isset($schema['type']) && is_string($schema['type'])) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        return $schema;
    }

    /**
     * A field with nothing known about it is described as `{}`, which an empty PHP array would encode as `[]`.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>|object
     */
    private function openWhenEmpty(array $schema): array | object
    {
        return $schema === [] ? (object) [] : $schema;
    }

    private function childPath(string $path, string $key): string
    {
        return $path === '' ? $key : "{$path}.{$key}";
    }

    /**
     * The rules as strings: `max:255` and the like, with rule objects that describe themselves as one.
     *
     * @param list<mixed> $rules
     *
     * @return list<string>
     */
    private function ruleStrings(array $rules): array
    {
        $strings = [];

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $strings[] = $rule;

                continue;
            }

            // Whether it applies depends on the request, so it is described rather than required
            if ($rule instanceof RequiredIf || $rule instanceof Enum || $rule instanceof In) {
                continue;
            }

            if ($rule instanceof Stringable) {
                try {
                    $strings[] = (string) $rule;
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $strings;
    }

    /**
     * @param list<string> $rules
     *
     * @return array<string, mixed>
     */
    private function scalarSchema(array $rules): array
    {
        foreach ($rules as $rule) {
            [$name, $parameters] = $this->parse($rule);

            $schema = match ($name) {
                'integer' => ['type' => 'integer'],
                'numeric', 'decimal' => ['type' => 'number'],
                'boolean', 'accepted', 'declined' => ['type' => 'boolean'],
                'array' => $parameters === [] ? ['type' => 'array'] : ['type' => 'object', 'properties' => array_fill_keys($parameters, (object) [])],
                'string', 'json', 'alpha', 'alpha_num', 'alpha_dash', 'regex' => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'url', 'active_url' => ['type' => 'string', 'format' => 'uri'],
                'uuid' => ['type' => 'string', 'format' => 'uuid'],
                'ip' => ['type' => 'string', 'format' => 'ip'],
                'date', 'before', 'after', 'before_or_equal', 'after_or_equal' => ['type' => 'string', 'format' => 'date'],
                'date_format' => ['type' => 'string', 'format' => Str::contains($parameters[0] ?? '', ['H', 'i', 's']) ? 'date-time' : 'date'],
                'file', 'image', 'mimes', 'mimetypes' => ['type' => 'string', 'format' => 'binary'],
                default => null,
            };

            if ($schema !== null) {
                return $schema;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $schema
     * @param list<mixed>          $rules
     *
     * @return array<string, mixed>
     */
    private function applyEnum(array $schema, array $rules): array
    {
        foreach ($rules as $rule) {
            $values = match (true) {
                $rule instanceof Enum => $this->enumRuleValues($rule),
                $rule instanceof In => $this->parse((string) $rule)[1],
                is_string($rule) && str_starts_with($rule, 'in:') => $this->parse($rule)[1],
                default => null,
            };

            if ($values === null || $values === []) {
                continue;
            }

            $schema['type'] ??= is_int($values[0]) ? 'integer' : 'string';

            if (count($values) <= self::ENUM_LIMIT) {
                $schema['enum'] = $values;
            }
        }

        return $schema;
    }

    /**
     * @return list<int|string>
     */
    private function enumRuleValues(Enum $rule): array
    {
        $type = $this->property($rule, 'type');

        if (! is_string($type) || ! is_subclass_of($type, BackedEnum::class)) {
            return [];
        }

        $only = $this->property($rule, 'only');
        $except = $this->property($rule, 'except');

        $cases = array_filter($type::cases(), static function (BackedEnum $case) use ($only, $except): bool {
            if (is_array($only) && $only !== [] && ! in_array($case, $only, true)) {
                return false;
            }

            return ! (is_array($except) && in_array($case, $except, true));
        });

        return array_values(array_map(static fn (BackedEnum $case): int | string => $case->value, $cases));
    }

    private function property(object $object, string $name): mixed
    {
        $reflection = new ReflectionClass($object);

        if (! $reflection->hasProperty($name)) {
            return null;
        }

        return (new ReflectionProperty($object, $name))->getValue($object);
    }

    /**
     * @param array<string, mixed> $schema
     * @param list<string>         $rules
     *
     * @return array<string, mixed>
     */
    private function applyConstraints(array $schema, array $rules): array
    {
        $type = $schema['type'] ?? null;

        [$minimumKey, $maximumKey] = match ($type) {
            'integer', 'number' => ['minimum', 'maximum'],
            'array' => ['minItems', 'maxItems'],
            'string' => ['minLength', 'maxLength'],
            default => [null, null],
        };

        if ($minimumKey === null || $maximumKey === null || isset($schema['enum'])) {
            return $schema;
        }

        foreach ($rules as $rule) {
            [$name, $parameters] = $this->parse($rule);

            if (! isset($parameters[0]) || ! is_numeric($parameters[0])) {
                continue;
            }

            $first = $this->number($parameters[0], $type);

            match ($name) {
                'min', 'gte' => $schema[$minimumKey] = $first,
                'max', 'lte' => $schema[$maximumKey] = $first,
                'size' => $schema[$minimumKey] = $schema[$maximumKey] = $first,
                'between' => [$schema[$minimumKey], $schema[$maximumKey]] = [$first, $this->number($parameters[1] ?? $parameters[0], $type)],
                default => null,
            };
        }

        return $schema;
    }

    private function number(string $value, mixed $type): int | float
    {
        return $type === 'number' ? (float) $value : (int) $value;
    }

    /**
     * When a field is only sometimes required, say so.
     *
     * @param list<string> $rules
     *
     * @return list<string>
     */
    private function conditionsFor(array $rules): array
    {
        $quoted = static fn (array $values): string => implode(', ', array_map(static fn (string $value): string => "`{$value}`", $values));

        $conditions = [];

        foreach ($rules as $rule) {
            [$name, $parameters] = $this->parse($rule);

            $field = $quoted(array_slice($parameters, 0, 1));
            $values = str_replace(', ', ' or ', $quoted(array_slice($parameters, 1)));

            $conditions[] = match ($name) {
                'required_if' => "Required when {$field} is {$values}.",
                'required_unless' => "Required unless {$field} is {$values}.",
                'required_with', 'required_with_all' => "Required with {$quoted($parameters)}.",
                'required_without', 'required_without_all' => "Required without {$quoted($parameters)}.",
                default => null,
            };
        }

        return array_values(array_filter($conditions));
    }

    /**
     * @param array<string, mixed>                          $schema
     * @param array{description?: string, example?: string} $comment
     * @param list<string>                                  $conditions
     *
     * @return array<string, mixed>
     */
    private function applyComment(array $schema, array $comment, array $conditions): array
    {
        $description = mb_trim(implode(' ', array_filter([$comment['description'] ?? null, ...$conditions])));

        if ($description !== '') {
            $schema['description'] = $description;
        }

        if (isset($comment['example'])) {
            $schema['examples'] = [$this->typedExample($comment['example'], $schema['type'] ?? null)];
        }

        return $schema;
    }

    private function typedExample(string $example, mixed $type): mixed
    {
        $type = is_array($type) ? $type[0] : $type;

        return match (true) {
            $type === 'integer' && is_numeric($example) => (int) $example,
            $type === 'number' && is_numeric($example) => (float) $example,
            $type === 'boolean' && in_array($example, ['true', 'false'], true) => $example === 'true',
            default => $example,
        };
    }

    /**
     * Comments written above the rules, for the request and the requests it extends:
     *     /*
     *      * The customer's own reference for the order.
     *      *
     *      * @example PO-2026-001
     *      *\/
     *     'reference' => ['nullable', 'string'],
     *
     * @param class-string $formRequestClass
     *
     * @return array<string, array{description?: string, example?: string}>
     */
    private function ruleComments(string $formRequestClass): array
    {
        $comments = [];
        $class = new ReflectionClass($formRequestClass);

        do {
            if ($class->hasMethod('rules') && $class->getMethod('rules')->getDeclaringClass()->getName() === $class->getName()) {
                $comments = [...$this->commentsIn(MethodSource::of($class->getMethod('rules')) ?? ''), ...$comments];
            }

            $class = $class->getParentClass();
        } while ($class !== false);

        return $comments;
    }

    /**
     * @return array<string, array{description?: string, example?: string}>
     */
    private function commentsIn(string $source): array
    {
        preg_match_all('#/\*\*?(.*?)\*/\s*[\'"]([^\'"]+)[\'"]\s*=>#s', $source, $matches, PREG_SET_ORDER);

        $comments = [];

        foreach ($matches as [, $body, $field]) {
            $description = [];
            $comment = [];

            $lines = preg_split('/\R/', $body);

            foreach ($lines === false ? [] : $lines as $line) {
                $line = mb_trim(mb_ltrim(mb_trim($line), '*'));

                if (str_starts_with($line, '@example')) {
                    $comment['example'] = mb_trim(mb_substr($line, mb_strlen('@example')));
                } elseif ($line !== '' && ! str_starts_with($line, '@')) {
                    $description[] = $line;
                }
            }

            if ($description !== []) {
                $comment['description'] = implode(' ', $description);
            }

            $comments[$field] = $comment;
        }

        return $comments;
    }

    /**
     * A rule's name in snake case, as written (`min`, `required_if`), and its parameters.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function parse(string $rule): array
    {
        [$name, $parameters] = ValidationRuleParser::parse($rule);

        return [Str::snake((string) $name), array_values(array_map(strval(...), $parameters))];
    }
}
