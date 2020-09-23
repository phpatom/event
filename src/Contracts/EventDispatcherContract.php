<?php


namespace Atom\Event\Contracts;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventDispatcherContract extends EventDispatcherInterface
{

    /**
     * Allow a listener to subscribe to a specific event
     * @param string $event
     * @param EventListenerContract $listener
     * @return mixed
     */
    public function addEventListener(string $event, EventListenerContract $listener);

    /**
     * Allow to add many listeners
     * @param array<string,EventListenerContract> $listeners
     * @return mixed
     */
    public function addEventListeners(array $listeners);

    /**
     * add a listener provider
     * @param EventListenerProviderContract $listenerProvider
     * @return mixed
     */
    public function addListenerProvider(EventListenerProviderContract $listenerProvider);
}
