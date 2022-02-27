<?php

namespace Walletable\Contracts;

interface Walletable
{
    /**
     * Get the name of wallet owner
     *
     * @return string
     */
    public function getOwnerName();

    /**
     * Get the email of wallet owner
     *
     * @return string
     */
    public function getOwnerEmail();

    /**
     * Get the ID of owner
     *
     * @return string
     */
    public function getOwnerID();

    /**
     * Get the morph name of owner
     *
     * @return string
     */
    public function getOwnerMorphName();
}
