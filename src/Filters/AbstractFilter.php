<?php
/**
 * JSONPath implementation for PHP.
 *
 * @copyright Copyright (c) 2018 Flow Communications
 * @license   MIT <https://github.com/SoftCreatR/JSONPath/blob/main/LICENSE>
 */
declare(strict_types=1);

namespace Flow\JSONPath\Filters;

use Flow\JSONPath\JSONPath;
use Flow\JSONPath\JSONPathToken;

abstract class AbstractFilter
{
    /**
     * @var JSONPathToken
     */
    protected $token;

    /**
     * @var  bool|int
     */
    protected $magicIsAllowed;

    /**
     * AbstractFilter constructor.
     *
     * @param JSONPathToken $token
     */
    public function __construct(JSONPathToken $token)
    {
        $this->token = $token;
        $this->magicIsAllowed = JSONPath::ALLOW_MAGIC;
    }

    /**
     * @param array|object $collection
     * @return array
     */
    abstract public function filter($collection): array;
}
