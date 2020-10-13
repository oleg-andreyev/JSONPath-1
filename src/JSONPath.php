<?php
/**
 * JSONPath implementation for PHP.
 *
 * @copyright Copyright (c) 2018 Flow Communications
 * @license   MIT <https://github.com/SoftCreatR/JSONPath/blob/main/LICENSE>
 */
declare(strict_types=1);

namespace Flow\JSONPath;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;
use JsonSerializable;
use function array_merge;
use function count;
use function current;
use function end;
use function key;
use function md5;
use function next;
use function reset;

class JSONPath implements ArrayAccess, Iterator, JsonSerializable, Countable
{
    /**
     * @var array
     */
    protected static $tokenCache = [];

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var int
     */
    protected $options;

    /**
     * @var bool|int
     */
    public const ALLOW_MAGIC = 1;

    /**
     * @param array|object|null $data
     * @param int $options
     */
    final public function __construct($data = null, $options = 0)
    {
        $this->data = $data;
        $this->options = $options;
    }

    /**
     * Evaluate an expression
     *
     * @param string $expression
     * @return JSONPath
     * @throws Exception
     */
    public function find(string $expression): self
    {
        $tokens = $this->parseTokens($expression);

        $collectionData = [$this->data];

        foreach ($tokens as $token) {
            $filter = $token->buildFilter($this->options);

            $filteredData = [];

            foreach ($collectionData as $value) {
                if (AccessHelper::isCollectionType($value)) {
                    $filteredValue = $filter->filter($value);
                    $filteredData = array_merge($filteredData, $filteredValue);
                }
            }

            $collectionData = $filteredData;
        }


        return new static($collectionData, $this->options);
    }

    /**
     * @return mixed
     */
    public function first()
    {
        $keys = AccessHelper::collectionKeys($this->data);

        if (empty($keys)) {
            return null;
        }

        $value = $this->data[$keys[0]] ?? null;

        return AccessHelper::isCollectionType($value) ? new static($value, $this->options) : $value;
    }

    /**
     * Evaluate an expression and return the last result
     * @return mixed
     */
    public function last()
    {
        $keys = AccessHelper::collectionKeys($this->data);

        if (empty($keys)) {
            return null;
        }

        $value = $this->data[end($keys)] ?: null;

        return AccessHelper::isCollectionType($value) ? new static($value, $this->options) : $value;
    }

    /**
     * Evaluate an expression and return the first key
     * @return mixed
     */
    public function firstKey()
    {
        $keys = AccessHelper::collectionKeys($this->data);

        if (empty($keys)) {
            return null;
        }

        return $keys[0];
    }

    /**
     * Evaluate an expression and return the last key
     * @return mixed
     */
    public function lastKey()
    {
        $keys = AccessHelper::collectionKeys($this->data);

        if (empty($keys) || end($keys) === false) {
            return null;
        }

        return end($keys);
    }

    /**
     * @param string $expression
     * @return array
     * @throws Exception
     */
    public function parseTokens(string $expression): array
    {
        $cacheKey = md5($expression);

        if (isset(static::$tokenCache[$cacheKey])) {
            return static::$tokenCache[$cacheKey];
        }

        $lexer = new JSONPathLexer($expression);
        $tokens = $lexer->parseExpression();

        static::$tokenCache[$cacheKey] = $tokens;

        return $tokens;
    }

    /**
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @deprecated Please use getData()
     * @return array|null
     */
    public function data(): ?array
    {
        return $this->getData();
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return AccessHelper::keyExists($this->data, $offset);
    }

    /**
     * @param string|int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $value = AccessHelper::getValue($this->data, $offset);

        return AccessHelper::isCollectionType($value)
            ? new static($value, $this->options)
            : $value;
    }

    /**
     * @param string|int|null $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            AccessHelper::setValue($this->data, $offset, $value);
        }
    }

    /**
     * @param string|int $offset
     */
    public function offsetUnset($offset): void
    {
        AccessHelper::unsetValue($this->data, $offset);
    }

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?array
    {
        return $this->data;
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        $value = current($this->data);

        return AccessHelper::isCollectionType($value) ? new static($value, $this->options) : $value;
    }

    /**
     * Move forward to next element
     */
    public function next(): void
    {
        next($this->data);
    }

    /**
     * Return the key of the current element
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * @param string|int $key
     * @return mixed
     * @noinspection MagicMethodsValidityInspection
     */
    public function __get($key)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : null;
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }
}
