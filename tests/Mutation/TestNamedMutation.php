<?php

namespace Walletable\Tests\Mutation;

use Walletable\Internals\Mutation\AbstractNamedMutation;

class TestNamedMutation extends AbstractNamedMutation
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
    public function __construct(string $name, $value, array $extras = [])
    {
        parent::__construct($name);

        $this->value = $value;
        $this->extras = $extras;
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
