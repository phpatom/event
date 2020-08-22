<?php


namespace Atom\Event\Exceptions;

use Exception;
use Atom\Contracts\Events\EventListenerContract;

class ListenerAlreadyAttachedToEvent extends Exception
{
    public function __construct(string $event, EventListenerContract $listener)
    {
        $listenerClass = get_class($listener);
        parent::__construct("Listener [$listenerClass] has already been bound to the event [$event]");
    }
}
