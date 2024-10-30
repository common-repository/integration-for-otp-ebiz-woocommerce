<?php
class WCeBizSimpleXMLElement extends SimpleXMLElement
{
    /**
     * Add CDATA text in a node
     * @param string $cdata_text The CDATA value  to add
     */
  private function addCData($cdata_text)
  {
   $node= dom_import_simplexml($this);
   $no = $node->ownerDocument;
   $node->appendChild($no->createCDATASection($cdata_text));
  }

  /**
   * Create a child with CDATA value
   * @param string $name The name of the child element to add.
   * @param string $cdata_text The CDATA value of the child element.
   */
    public function addChildCData($name,$cdata_text)
    {
        $child = $this->addChild($name);
        $child->addCData($cdata_text);
    }

    /**
     * Add SimpleXMLElement code into a SimpleXMLElement
     * @param SimpleXMLElement $append
     */
    public function appendXML($append)
    {
        if ($append) {


            // Create new DOMElements from the two SimpleXMLElements
        		$domdict = dom_import_simplexml($this);
        		$domcat  = dom_import_simplexml($append);

        		// Import the <cat> into the dictionary document
        		$domcat  = $domdict->ownerDocument->importNode($domcat, TRUE);

        		// Append the <cat> to <c> in the dictionary
        		$domdict->appendChild($domcat);


        }
    }
}
