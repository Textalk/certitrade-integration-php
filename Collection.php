<?

namespace CertiTrade;

require_once 'HAL.php';

class Collection extends HAL implements \IteratorAggregate
{
    protected $collectionName;

    public function __construct($atomics, $links, $embedded, $collectionName)
    {
        $this->collectionName = $collectionName;
        
        parent::__construct($atomics, $links, $embedded);
    }

    public function getIterator()
    {
        $collectionName = $this->collectionName;
    
        return $this->embedded->$collectionName;
    }
    
    
    public function __toString()
    {
        return print_r($this->embedded, true) . print_r($this->links, true);
    }
};