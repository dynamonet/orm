<?php

namespace Dynamo\ORM\Events;

trait EventDispatcherTrait
{
    private $instanceListeners = [];
    private static $superListeners = [];

    /**
     * @param callable|Dynamo\Events\EventListener
     */
    public function listen(string $eventName, $observer)
    {
        if(!isset($this->eventListener[$eventName])){
            $this->eventListener[$eventName] = [];
        }
        $this->eventListener[$eventName][] = $observer;
        
    }

    /**
     * Subscribes to an event on ALL instances
     */
    public static function listenAll(string $eventName, $observer)
    {
        if(!isset(self::$superListeners[$eventName])){
            self::$superListeners[$eventName] = [];
        }
        self::$superListeners[$eventName][] = $observer;
    }

    public function unlisten(string $eventName, mixed $observer)
    {
        //to-do
    }

    public function dispatch(Event $event)
    {
        $eventName = $event->getName();

        //First, we notify instance observers
        if(isset($this->instanceListeners[$eventName])){
            foreach($this->instanceListeners[$eventName] as $observer){
                if(is_callable($observer)){
                    call_user_func($observer, $event);
                } else if($observer instanceof EventListener) {
                    $observer->handleEvent($event);
                }
            }
        }

        //Finally, ww notify the super observers
        if(isset(self::$superListeners[$eventName])){
            foreach(self::$superListeners[$eventName] as $observer){
                if(is_callable($observer)){
                    call_user_func($observer, $event);
                } else if($observer instanceof EventListener) {
                    $observer->handleEvent($event);
                }
            }
        }
    }
}
