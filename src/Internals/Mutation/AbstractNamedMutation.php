<?php

namespace Walletable\Internals\Mutation;

abstract class AbstractNamedMutation extends AbstractMutation
{
    /**
     * The mutation name
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new named mustation
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the mutation name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
