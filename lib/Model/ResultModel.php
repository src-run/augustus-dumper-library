<?php

/*
 * This file is part of the `src-run/augustus-dumper-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Dumper\Model;

class ResultModel implements \Countable, \IteratorAggregate
{
    /**
     * @var string|array
     */
    private $data;

    /**
     * @param string|array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public static function __set_state(array $properties): self
    {
        return new static($properties['data'] ?? []);
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return is_array($this->data);
    }

    /**
     * @return bool
     */
    public function isString(): bool
    {
        return is_string($this->data);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->isArray() ? count($this->data) : mb_strlen($this->data);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->isArray() ? $this->data : str_split($this->data));
    }

    /**
     * @return string|array
     */
    public function getData()
    {
        return $this->data;
    }
}
