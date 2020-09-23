<?php
namespace Atom\Event\Test;

use InvalidArgumentException;
use Atom\Event\Contracts\EventListenerProviderContract;
use Atom\Event\EventDispatcher;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use PHPUnit\Framework\MockObject\MockObject;
use StdClass;
use TypeError;

class EventDispatcherTest extends BaseEventTest
{
    public function testItIsASingleton()
    {
        $dispatcher = new EventDispatcher();
        $this->assertSame($dispatcher, EventDispatcher::getInstance());
        EventDispatcher::clearInstance();
        $newDispatcher = EventDispatcher::getInstance();
        $this->assertNotSame($dispatcher, $newDispatcher);
        $this->assertSame($newDispatcher, EventDispatcher::getInstance());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItAddEventListener()
    {
        $dispatcher = new EventDispatcher();
        $event = "random";
        $event2 = "chimp event";
        $listener = $this->buildListener();
        $listener2 = $this->buildListener();
        $listener3 = $this->buildListener();
        $this->assertEmpty($dispatcher->getListeners());
        $dispatcher->addEventListener($event, $listener);
        $this->assertNotEmpty($dispatcher->getListeners());
        $this->assertArrayHasKey($event, $dispatcher->getListeners());
        $this->assertSame($dispatcher->getListeners()[$event][0], $listener);
        $dispatcher->addEventListener($event2, $listener);
        $this->assertSame($dispatcher->getListeners()[$event2][0], $listener);
        $dispatcher->addEventListener($event, $listener2);
        $dispatcher->addEventListener($event, $listener3);
        $this->assertCount(3, $dispatcher->getListeners()[$event]);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItGenerateANewEmptyInstance()
    {
        $event = "bar event";
        $listener = $this->buildListener();
        $dispatcher = new EventDispatcher();
        $dispatcher->addEventListener($event, $listener);
        $this->assertNotEmpty(EventDispatcher::getInstance()->getListeners());
        $dispatcher = EventDispatcher::newInstance();
        $this->assertEmpty($dispatcher->getListeners());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItThrowAnExceptionIfTheListenerIsAlreadyBoundToAnTheEvent()
    {
        $dispatcher = EventDispatcher::newInstance();
        $event = "foo event";
        $listener = $this->buildListener();
        $dispatcher->addEventListener($event, $listener);
        $this->expectException(ListenerAlreadyAttachedToEvent::class);
        $dispatcher->addEventListener($event, $listener);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItAddEventListeners()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addEventListener("foo", $this->buildListener());
        $dispatcher->addEventListeners([
           "foo" => $this->buildListener(),
            "bar" => $listener = $this->buildListener(),
            "baz" => [
                $this->buildListener(),
                $this->buildListener()
            ]
        ]);

        $this->assertCount(2, $dispatcher->getListeners()["foo"]);
        $this->assertCount(1, $dispatcher->getListeners()["bar"]);
        $this->assertCount(2, $dispatcher->getListeners()["baz"]);
        $this->expectException(ListenerAlreadyAttachedToEvent::class);
        $dispatcher->addEventListeners([
            "bar"=>$listener
        ]);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItCheckTheTypeWhenListenersAreAdded()
    {
        $dispatcher = new EventDispatcher();
        $this->expectException(TypeError::class);
        $dispatcher->addEventListeners(["bar"=>"baz"]);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItCheckTheTypeOfTheEventWhenDispatchIsCalled()
    {
        $this->expectException(InvalidArgumentException::class);
        $dispatcher = new EventDispatcher();
        $dispatcher->dispatch(new StdClass());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItDispatchAnEvent()
    {
        $dispatcher = new EventDispatcher();
        $event = $this->buildEvent();
        $event2 = $this->buildEvent();

        $listener = $this->buildListener();
        $listener2 = $this->buildListener();
        $listener->method("canBeCalled")->willReturn(true);
        $listener2->method("canBeCalled")->willReturn(true);

        $dispatcher->addEventListener(get_class($event), $listener);
        $dispatcher->addEventListener(get_class($event), $listener2);
        $dispatcher->addEventListener(get_class($event2), $listener);

        $listener->expects($this->exactly(2))->method("handle")->withConsecutive(
            [$this->equalTo($event)],
            [$this->equalTo($event2)]
        );
        $listener2->expects($this->once())->method("handle")->with($event);

        $dispatcher->dispatch($event);
        $dispatcher->dispatch($event2);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testAListenerIsNotCalledIfItIsNotActive()
    {
        $listener = $this->buildListener();
        $listener->method("canBeCalled")->willReturn(false);
        $dispatcher = new EventDispatcher();
        $dispatcher->addEventListener(get_class($event = $this->buildEvent()), $listener);
        $listener->expects($this->never())->method("handle");
        $dispatcher->dispatch($event);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testEventListenerProvidersAreDispatched()
    {
        $dispatcher = new EventDispatcher();
        $event = $this->buildEvent();
        $event2 = $this->buildEvent();
        $listener1 = $this->buildListener();
        $listener2 = $this->buildListener();
        $listener1->method("canBeCalled")->willReturn(true);
        $listener2->method("canBeCalled")->willReturn(true);
        $listeners = [$listener1,$listener2];
        /**
         * @var $listenerProvider EventListenerProviderContract|MockObject
         */
        $listenerProvider = $this->getMockBuilder(EventListenerProviderContract::class)->getMock();
        $listenerProvider->method("getListenersForEvent")->will(
            $this->returnValueMap(
                array(
                    array($event, $listeners),
                    array($event2, [])
                )
            )
        );

        $dispatcher->addListenerProvider($listenerProvider);
        $dispatcher->addEventListener(get_class($event2), $listener1);

        $listener1->expects($this->exactly(2))->method("handle")->withConsecutive(
            [$this->equalTo($event)],
            [$this->equalTo($event2)]
        );
        $listener2->expects($this->once())->method("handle")->with($event);
        $dispatcher->dispatch($event);
        $dispatcher->dispatch($event2);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testEventListenersAreExecutedInTheRightOrder()
    {
        $result = [];
        $event = $this->buildEvent();
        $listener1 = $this->buildListener();
        $listener1->method("getPriority")->willReturn(200);
        $listener1->method("canBeCalled")->willReturn(true);
        $listener1->method("handle")->willReturnCallback(function () use (&$result) {
            $result[] = 200;
        });
        $listener2 = $this->buildListener();
        $listener2->method("getPriority")->willReturn(300);
        $listener2->method("canBeCalled")->willReturn(true);
        $listener2->method("handle")->willReturnCallback(function () use (&$result) {
            $result[] = 300;
        });
        $listener3 = $this->buildListener();
        $listener3->method("getPriority")->willReturn(100);
        $listener3->method("canBeCalled")->willReturn(true);
        $listener3->method("handle")->willReturnCallback(function () use (&$result) {
            $result[] = 100;
        });

        $listener4 = $this->buildListener();
        $listener4->method("getPriority")->willReturn(200);
        $listener4->method("canBeCalled")->willReturn(true);
        $listener4->method("handle")->willReturnCallback(function () use (&$result) {
            $result[] = 250;
        });

        $dispatcher = new EventDispatcher();
        $dispatcher->addEventListeners([
            get_class($event) => [$listener1,$listener2,$listener3,$listener4]
        ]);
        $dispatcher->dispatch($event);
        $this->assertSame($result, [300,200,250,100]);
    }
}
