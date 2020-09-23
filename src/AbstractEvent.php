<?php


namespace Atom\Event;

use Atom\Event\Contracts\EventContract;

abstract class AbstractEvent implements EventContract
{
    protected $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation()
    {
        return $this->propagationStopped = true;
    }
}
