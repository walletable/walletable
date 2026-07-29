<?php

namespace Walletable\Internals\Actions;

use Walletable\Models\Posting;

class ActionManager
{
    protected Posting $posting;
    protected ActionInterface $action;

    public function __construct(Posting $posting, ActionInterface $action)
    {
        $this->posting = $posting;
        $this->action = $action;
    }

    public function title()
    {
        return $this->action->title($this->posting);
    }

    public function image()
    {
        return $this->action->image($this->posting);
    }

    public function resource()
    {
        return $this->action->methodResource($this->posting);
    }

    public function getAction(): ActionInterface
    {
        return $this->action;
    }
}
