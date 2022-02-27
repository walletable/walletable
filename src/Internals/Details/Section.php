<?php

namespace Walletable\Internals\Details;

use ArrayIterator;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use Traversable;

class Section implements IteratorAggregate
{
    use ForwardsCalls;

    /**
     * Collection of infos
     *
     * @var \Illuminate\Support\Collection
     */
    protected $infos;

    /**
     * Collection of infos
     *
     * @var string
     */
    protected $name;

    public function __construct(string $name, ?Info ...$infos)
    {
        $this->name = $name;
        $this->infos = \collect($infos);
    }

    /**
     * Get Iterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->infos->all());
    }

    /**
     * Static Collecting method
     *
     * @param \Walletable\Internals\Details\Info ...$infos
     */
    public static function create(string $name, Info ...$infos)
    {
        return new static($name, ...$infos);
    }

    /**
     * Returns the name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Add new info
     *
     * @param \Walletable\Internals\Details\Info $info
     * @return self
     */
    public function add(Info $info)
    {
        $this->infos->add($info);
        return $this;
    }

    /**
     * Map method calls to the collection
     *
     * @param string $method
     * @param array $parameters
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->infos, $method, $parameters);
    }
}
