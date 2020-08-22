<?php

namespace Atom\Event\Test;

use Atom\Event\AbstractEventListener;
use Atom\Event\EventDispatcher;
use Atom\Event\EventListenerProvider;

class AbstractEventListenerTest extends BaseEventTest
{
    private $stack = [];
    protected function buildListener()
    {
        return $this->getMockForAbstractClass(AbstractEventListener::class);
    }

    public function testTheListenerIsCalledOnce()
    {
        $dispatcher = new EventDispatcher();
        $listener = $this->buildListener();
        $event1 = $this->buildEvent();
        $event2 = $this->buildEvent();

        $dispatcher->addEventListener(get_class($event1), $listener->once());
        $dispatcher->addEventListener(get_class($event2), $listener);

        $dispatcher->dispatch($event2);
        $dispatcher->dispatch($event1);

        $this->assertEquals(1, $listener->getCalls());
    }

    public function testTheListenerIsCalledASpecificNumberOfTime()
    {
        $dispatcher = new EventDispatcher();
        $listener = $this->buildListener();
        $event1 = $this->buildEvent();
        $event2 = $this->buildEvent();
        $event3 = $this->buildEvent();

        $dispatcher->addEventListener(get_class($event1), $listener);
        $dispatcher->addEventListener(get_class($event2), $listener->exactly(2));
        $dispatcher->addEventListener(get_class($event3), $listener);

        $dispatcher->dispatch($event2);
        $dispatcher->dispatch($event3);
        $dispatcher->dispatch($event1);

        $this->assertEquals(2, $listener->getCalls());
    }

    public function testTheListenerIsNeverCalled()
    {
        $dispatcher = new EventDispatcher();
        $listener = $this->buildListener();
        $event1 = $this->buildEvent();
        $event2 = $this->buildEvent();
        $event3 = $this->buildEvent();

        $dispatcher->addEventListener(get_class($event1), $listener->never());
        $dispatcher->addEventListener(get_class($event2), $listener);
        $dispatcher->addEventListener(get_class($event3), $listener);

        $dispatcher->dispatch($event2);
        $dispatcher->dispatch($event3);
        $dispatcher->dispatch($event1);

        $this->assertEquals(0, $listener->getCalls());
    }

    public function testListenersAreOrdered()
    {
        $result = [];
        $h = $this->buildListener()->withHighPriority();
        $h->method("on")->willReturnCallback(function () use (&$result, $h) {
            $result[] = $h->getPriority();
        });
        $s = $this->buildListener()->withSeverePriority();
        $s->method("on")->willReturnCallback(function () use (&$result, $s) {
            $result[] = $s->getPriority();
        });
        $m = $this->buildListener()->withMediumPriority();
        $m->method("on")->willReturnCallback(function () use (&$result, $m) {
            $result[] = $m->getPriority();
        });
        $n = $this->buildListener()->withNormalPriority();
        $n->method("on")->willReturnCallback(function () use (&$result, $n) {
            $result[] = $n->getPriority();
        });
        $c = $this->buildListener()->withPriority(250);
        $c->method("on")->willReturnCallback(function () use (&$result, $c) {
            $result[] = $c->getPriority();
        });

        $event = $this->buildEvent();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListenerProvider(new EventListenerProvider(get_class($event), [
            $c,$s,$m,$h,$n
        ]));
        $dispatcher->dispatch($event);
        $this->assertSame([
            AbstractEventListener::PRIORITY_SEVERE,250,
            AbstractEventListener::PRIORITY_HIGH,
            AbstractEventListener::PRIORITY_MEDIUM,
            AbstractEventListener::PRIORITY_NORMAL], $result);
    }
}
