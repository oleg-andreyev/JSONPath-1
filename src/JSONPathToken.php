<?php
/**
 * JSONPath implementation for PHP.
 *
 * @copyright Copyright (c) 2018 Flow Communications
 * @license   MIT <https://github.com/SoftCreatR/JSONPath/blob/main/LICENSE>
 */
declare(strict_types=1);

namespace Flow\JSONPath;


use function class_exists;
use function in_array;
use function ucfirst;

class JSONPathToken
{
    /*
     * Tokens
     */
    public const T_INDEX = 'index';
    public const T_RECURSIVE = 'recursive';
    public const T_QUERY_RESULT = 'queryResult';
    public const T_QUERY_MATCH = 'queryMatch';
    public const T_SLICE = 'slice';
    public const T_INDEXES = 'indexes';

    /**
     * @var string
     */
    public $type;

    /**
     * @var string|array|null
     */
    public $value;

    /**
     * JSONPathToken constructor.
     *
     * @param string $type
     * @param string|array|null $value
     * @throws JSONPathException
     */
    public function __construct(string $type, $value)
    {
        $this->validateType($type);

        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @param string $type
     * @throws JSONPathException
     */
    public function validateType(string $type): void
    {
        if (!in_array($type, static::getTypes(), true)) {
            throw new JSONPathException('Invalid token: ' . $type);
        }
    }

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            static::T_INDEX,
            static::T_RECURSIVE,
            static::T_QUERY_RESULT,
            static::T_QUERY_MATCH,
            static::T_SLICE,
            static::T_INDEXES,
        ];
    }

    /**
     * @return mixed
     * @throws JSONPathException
     */
    public function buildFilter()
    {
        $filterClass = 'Flow\\JSONPath\\Filters\\' . ucfirst($this->type) . 'Filter';

        if (!class_exists($filterClass)) {
            throw new JSONPathException("No filter class exists for token [{$this->type}]");
        }

        return new $filterClass($this);
    }
}
