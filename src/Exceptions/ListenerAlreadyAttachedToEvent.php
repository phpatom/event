<?php


namespace Atom\Event\Exceptions;

use Atom\Event\Contracts\EventListenerContract;
use Exception;

class ListenerAlreadyAttachedToEvent extends Exception
{
    public function __construct(string $event, EventListenerContract $listener)
    {
        $listenerClass = get_class($listener);
        parent::__construct("Listener [$listenerClass] has already been bound to the event [$event]");
    }
}
