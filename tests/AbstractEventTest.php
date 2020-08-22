<?php


namespace Atom\Event\Test;

use Atom\Event\AbstractEvent;
use Atom\Event\AbstractEventListener;
use Atom\Event\EventDispatcher;

class AbstractEventTest extends BaseEventTest
{

    public function testThePropagationIsStopped()
    {
        $event = $this->getMockForAbstractClass(AbstractEvent::class);
        $listener1 = $this->buildListener();
        $listener1->method("handle")->willReturnCallback(function () use ($event) {
            $event->stopPropagation();
        });
        $listener1->method("canBeCalled")->willReturn(true);
        $listener2 = $this->buildListener();
        $listener2->method("canBeCalled")->willReturn(true);
        $dispatcher = new EventDispatcher();

        $dispatcher->addEventListeners([
           get_class($event) => [
               $listener1,
               $listener2
           ]
        ]);
        $listener1->expects($this->once())->method("handle");
        $listener2->expects($this->never())->method("handle");

        $dispatcher->dispatch($event);
    }
}
