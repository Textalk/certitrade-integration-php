<?php

namespace CertiTrade;

class APIProblem
{
    protected $httpStatus;
    protected $title;
    protected $detail;
    protected $describedBy;

    public function __construct($httpStatus, $title, $detail, $describedBy)
    {
        $this->httpStatus = $httpStatus;
        $this->title = $title;
        $this->detail = $detail;
        $this->describedBy = $describedBy;
    }
    
    
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
    
    public function getTitle()
    {
        return $this->title;
    }
    
    public function getDetail()
    {
        return $this->detail;
    }
    
    public function getDescribedBy()
    {
        return $this->describedBy;
    }
    
    
    public function __toString()
    {
        $ret = "ERROR: $this->title";
        if (!empty($this->detail)) {
            $ret .= ", $this->detail";
        }
        $ret .= "\n";
    
        return $ret;
    }
};