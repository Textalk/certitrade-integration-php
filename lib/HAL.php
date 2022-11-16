<?php

namespace CertiTrade;

abstract class HAL
{
    protected $atomicData;
    protected $links;
    protected $embedded;

    public function __construct($atomics, $links, $embedded)
    {
        $this->atomicData = $atomics;
        $this->links = $links;
        $this->embedded = $embedded;
    }
    
    
    public function getLink($link)
    {
        if (!isset($this->links->$link)) {
            return null;
        }
    
        return $this->links->$link->href;
    }
    
    public function __get($name)
    {
        if (!isset($this->atomicData->$name)) {
            return null;
        }
        
        return $this->atomicData->$name;
    }
};