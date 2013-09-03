<?php

namespace webd\odt2html;

abstract class HTMLTag {
    /**
     *
     * @var HTMLTag[] 
     */
    protected $children = array();
    
    /**
     *
     * @var HTMLTag 
     */
    protected $parent = null;
    
    
    protected $classes = array();
    
    protected $tag = "";
    
    public $id = "";
    
    
    public function appendChild(HTMLTag $child) {
        $this->children[] = $child;
        $child->parent = $this;
    }
    
    public function getParent() {
        return $this->parent;
    }
    
    
    public function value() {
        return "";
    }
    
    public function __toString() {

        $return = "<";
        $return .= $this->tag();
        $return .= $this->id();
        $return .= $this->classes();
        $return .= $this->attributes();
        $return .= ">";
        
        $return .= $this->value();
        $return .= $this->children();
        
        $return .= "</" . $this->tag() . ">\n";

        return $return;
    }
    
    public function tag() {
        return $this->tag;
    }
    
    public function id() {
        
        if ($this->id != "") {
            return " id='{$this->id}'";
        }
        return "";
    }
    
    public function classes() {
        $return = "";
        if (count($this->classes)) {
            $return .= " class='" . implode(" ", $this->classes) . "'";
        }
        return $return;
    }
    
    public function attributes() {
        $return = "";
        $reflection = new \ReflectionObject($this);
        foreach ($reflection->getProperties() as $property) {
            /* @var $property \ReflectionProperty */
            if ($property->isPublic() AND $property->name != "id") {
                $value = $property->getValue($this);
                $return .= " {$property->name}='$value'";
            }
        }
        return $return;
    }
    
    public function children() {
        return trim(implode(" ", $this->children));
    }
    
    public function addClass($class) {
        $this->classes[] = $class;
    }
}

class TagDummy extends HTMLTag
{
    public function __toString() {
        return $this->children();
    }  
}

class TagH1 extends HTMLTag
{
    protected $tag = "h1";

}

class TagH2 extends HTMLTag
{
    protected $tag = "h2";  
}

class TagH3 extends HTMLTag
{
    protected $tag = "h3"; 
}

class TagH4 extends HTMLTag
{
    protected $tag = "h4";  
}

class TagH5 extends HTMLTag
{
    protected $tag = "h5"; 
}

class TagH6 extends HTMLTag
{
    protected $tag = "h6";  
}

class TagImg extends HTMLTag
{
    public $src = "";
    
    protected $tag = "img";
    
    public function __construct($src) {
        $this->src = $src;
    }
}

class Text extends HTMLTag
{
    public $value = "";
    
    public function __toString() {
        return trim($this->value);
    }
}

class TagP  extends HTMLTag
{
    protected $tag = "p"; 
}

class TagA extends HTMLTag
{
    public $href = "";
    
    public function __construct($href) {
        $this->href = $href;
    }
    
    protected $tag = "a";
}

class Ul extends HTMLTag
{
    protected $tag = "ul"; 
}

class Li extends HTMLTag
{
    protected $tag = "li";
}

class Ol extends HTMLTag
{
    protected $tag = "ol";
}

class ODT2HTML {
    
    protected $xml;
    protected $root;
    protected $current_tag;
    protected $odt_file = "";
    
    public function __construct($odt_file) {
        $this->odt_file = $odt_file;
        $this->xml = new \XMLReader();
        $this->xml->open('zip://' . $odt_file . '#content.xml');
        
        $this->root = new TagDummy();
        $this->current_tag = $this->root;
    }
    
    protected function appendTag(HTMLTag $tag) {
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
                $new = new Text();
                $new->value = htmlspecialchars($this->xml->value);
                $this->current_tag->appendChild($new);
            }
            
            
            if ($this->xml->nodeType == \XMLReader::ELEMENT) {

                switch ($this->xml->name) {
                    case "text:h"://Title
                        $n = $this->xml->getAttribute("text:outline-level");
                        switch ($n) {
                            case 1 :
                                $this->appendTag(new TagH1());
                                break;
                            case 2 :
                                $this->appendTag(new TagH2());
                                break;
                            case 3 :
                                $this->appendTag(new TagH3());
                                break;
                            case 4 :
                                $this->appendTag(new TagH4());
                                break;
                            case 5 :
                                $this->appendTag(new TagH5());
                                break;
                            default :
                                $this->appendTag(new TagH6());
                        }
                        break;

                    case "text:p":
                        $this->appendTag(new TagP());
                        break;
                    
                    case "draw:image":
                        $image_file = 'zip://' . $this->odt_file . '#' . $this->xml->getAttribute("xlink:href");
                        $src = 'data:image;base64,' . base64_encode(file_get_contents($image_file));
                        $this->appendTag(new TagImg($src));
                        break;
                
                    case "text:a":
                        $href = $this->xml->getAttribute("xlink:href");
                        $this->appendTag(new TagA($href));
                        break;
                    
                    case "text:list":
                        $this->appendTag(new Ul());
                        break;
                    
                    case "text:list-item":
                        $this->appendTag(new Li());
                        break;
                    
                    case "text:table-of-content":
                        $this->skip();
                        continue; // without performing extra read
                        
                    case "office:automatic-styles":
                        $this->skip();
                        continue;

                    default : 
                         $this->appendTag(new TagDummy());
                         

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
