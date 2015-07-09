<?php

include('LI_ConfigFileReaderTrait.php');

abstract class LI_ConfigReaderInterface extends LI_ConfigFileReaderTrait
{
    /**
     * @param string $lookupString
     * @return array
     */
    public function lookup($lookupString)
    {
    	
    }
}
