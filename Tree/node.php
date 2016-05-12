<?php

class Node {
    public $children = [];
    public $parent;
    public $value;

    public function __construct($value, $children) {
        $this->value = $value;
        if (!empty($children)) {
            $this->children=$children;
        }
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }


    public function addChild($child) {
        $child->setParent($this);
        array_push($this->children, $child);
    }

    public function getChildren() {
        return $this->children;
    }

    public function removeChild($child) {
        foreach ($this->children as $key => $myChild) {
            if ($child == $myChild) {
                unset($this->children[$key]);
            }
        }
        $child->setParent(null);
    }

    public function setParent($parent) {
        $this->parent = $parent;
    }

    public function getParent(){
        return $this->parent;
    }
}

?>