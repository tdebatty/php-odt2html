<?php
namespace webd\odt2html;

class ODT2HTML {
    
    protected $xml;
    protected $root;
    protected $current_tag;
    protected $odt_file = "";
    
    public function __construct($odt_file) {
        $this->odt_file = $odt_file;
        $this->xml = new \XMLReader();
        $this->xml->open('zip://' . $odt_file . '#content.xml');
        
        $this->root = new \webd\html5\DummyTag();
        $this->current_tag = $this->root;
    }
    
    protected function appendTag(\webd\html5\Tag $tag) {
        $this->current_tag->appendChild($tag);
        
        $class = $this->xml->getAttribute("text:style-name");
        if ($class) {
            $tag->addClass($class);
        }
        
        if (! $this->xml->isEmptyElement) {
            $this->current_tag = $tag;
        }
    }
    
    protected function skip() {
        $this->xml->next();
    }

    public function parse() {
        $this->xml->read();
        while (true) {
            
            if ($this->xml->nodeType === \XMLReader::END_ELEMENT) {
                if ($this->current_tag->getParent() != null) {
                    $this->current_tag = $this->current_tag->getParent();
                }
            }
            
            if ($this->xml->nodeType === \XMLReader::TEXT) {
                $this->current_tag->appendChild(new \webd\html5\Text(htmlspecialchars($this->xml->value)));
            }
            
            
            if ($this->xml->nodeType == \XMLReader::ELEMENT) {

                switch ($this->xml->name) {
                    case "text:h"://Title
                        $n = $this->xml->getAttribute("text:outline-level");
                        switch ($n) {
                            case 1 :
                                $this->appendTag(new \webd\html5\H1());
                                break;
                            case 2 :
                                $this->appendTag(new \webd\html5\H2());
                                break;
                            case 3 :
                                $this->appendTag(new \webd\html5\H3());
                                break;
                            case 4 :
                                $this->appendTag(new \webd\html5\H4());
                                break;
                            case 5 :
                                $this->appendTag(new \webd\html5\H5());
                                break;
                            default :
                                $this->appendTag(new \webd\html5\H6());
                        }
                        break;

                    case "text:p":
                        $this->appendTag(new \webd\html5\P());
                        break;
                    
                    case "draw:image":
                        $image_file = 'zip://' . $this->odt_file . '#' . $this->xml->getAttribute("xlink:href");
                        $src = 'data:image;base64,' . base64_encode(file_get_contents($image_file));
                        $this->appendTag(new \webd\html5\Img($src));
                        break;
                
                    case "text:a":
                        $href = $this->xml->getAttribute("xlink:href");
                        $this->appendTag(new \webd\html5\A($href));
                        break;
                    
                    case "text:list":
                        $this->appendTag(new \webd\html5\Ul());
                        break;
                    
                    case "text:list-item":
                        $this->appendTag(new \webd\html5\Li());
                        break;
                    
                    case "table:table":
                        $this->appendTag(new \webd\html5\Table());
                        break;
                    
                    case "table:table-row":
                        $this->appendTag(new \webd\html5\Tr());
                        break;
                    
                    case "table:table-cell":
                        $this->appendTag(new \webd\html5\Td());
                        break;
                    
                    case "text:table-of-content":
                        $this->skip();
                        continue; // without performing extra read
                        
                    case "office:automatic-styles":
                        $this->skip();
                        continue;

                    default : 
                         $this->appendTag(new \webd\html5\DummyTag());
                         

                } // switch   
            } 
            
            if (!$this->xml->read()) {
                break;
            }
            
        } // while
        
        return $this->root->__toString();
    }
}

?>
