<?php

namespace CertiTrade;

require_once('HAL.php');

class Resource extends HAL
{
    public function __toString()
    {
        return print_r($this->atomicData, true) . print_r($this->links, true);
    }
};