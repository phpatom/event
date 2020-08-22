<?php


namespace Atom\Event;

use InvalidArgumentException;
use Atom\Contracts\Events\EventContract;
use Atom\Contracts\Events\EventDispatcherContract;
use Atom\Contracts\Events\EventListenerContract;
use Atom\Contracts\Events\EventListenerProviderContract;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;

class EventDispatcher implements EventDispatcherContract
{
    /**
     * @var []<string,EventListenerContract[]>
     */
    private $listeners = [];
    /**
     * @var EventListenerProviderContract[]
     */
    private $listenerProviders = [];

    /**
     * @var EventDispatcher
     */
    private static $instance;

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @return EventDispatcher
     */
    public static function getInstance(): EventDispatcher
    {
        if (is_null(self::$instance)) {
            self::$instance = new EventDispatcher();
        }
        return self::$instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    public static function newInstance()
    {
        self::$instance = new self();
        return self::$instance;
    }

    public function getListeners():array
    {
        return $this->listeners;
    }

    public function getListenerProviders()
    {
        return $this->listenerProviders;
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @inheritDoc
     */
    public function addEventListener(string $event, EventListenerContract $listener):EventListenerContract
    {
        $attachedListeners = $this->listeners[$event] ?? [];
        if (in_array($listener, $attachedListeners)) {
            throw new ListenerAlreadyAttachedToEvent($event, $listener);
        }
        $this->listeners[$event][] = $listener;
        return $listener;
    }

    /**
     * @param array<string,EventListenerContract|EventListenerContract[]>
     * @throws ListenerAlreadyAttachedToEvent
     * @inheritDoc
     */
    public function addEventListeners(array $listeners)
    {
        foreach ($listeners as $event => $listener) {
            if (!is_array($listener)) {
                $this->addEventListener($event, $listener);
                continue;
            }
            foreach ($listener as $l) {
                $this->addEventListener($event, $l);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addListenerProvider(EventListenerProviderContract $listenerProvider)
    {
        $this->listenerProviders[] = $listenerProvider;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function dispatch(object $event)
    {
        if (!$event instanceof EventContract) {
            $eventContractString = EventContract::class;
            $className = get_class($event);
            throw new InvalidArgumentException("Only object of type [$eventContractString] can be dispatched. 
            Object of type [$className] given");
        }
        $this->dispatchEventListenerProviders($event);
        $listeners = $this->listeners[get_class($event)] ?? [];
        $listeners = $this->reorderListeners($listeners);
        foreach ($listeners as $listener) {
            /**
             * @var $listener EventListenerContract
             * @ver $event AbstractEvent
             */
            if ($listener->canBeCalled() && !$event->isPropagationStopped()) {
                $listener->handle($event);
            }
        }
    }

    /**
     * @param object $event
     * @throws ListenerAlreadyAttachedToEvent
     */
    private function dispatchEventListenerProviders(object $event)
    {
        foreach ($this->listenerProviders as $listenerProvider) {
            $listeners = $listenerProvider->getListenersForEvent($event);
            foreach ($listeners as $listener) {
                $this->addEventListener(get_class($event), $listener);
            }
        }
    }

    /**
     * @param $listeners EventListenerContract[]
     * @return EventListenerContract[]
     */
    private function reorderListeners($listeners):array
    {
        uasort($listeners, function ($k, $v) {
            /**
             * @var $v EventListenerContract
             * @var $k EventListenerContract
             */
            return $k->getPriority() < $v->getPriority();
        });
        return $listeners;
    }
}
