<?php


namespace Atom\Event;

use Atom\Contracts\Events\EventListenerProviderContract;

class EventListenerProvider implements EventListenerProviderContract
{

    /**
     * @var string
     */
    private $event;
    /**
     * @var iterable
     */
    private $listeners;

    public function __construct(string $event, iterable $listeners)
    {
        $this->event = $event;
        $this->listeners = $listeners;
    }

    public function getListenersForEvent(object $event): iterable
    {
        if (get_class($event) == $this->event) {
            return $this->listeners;
        }
        return [];
    }
}
