<?php
namespace FoxORM\Std;
use FoxORM\Std\Cast;
use ArrayAccess;
use Iterator;
use JsonSerializable;
use Countable;
use stdClass;
class ArrayIterator implements ArrayAccess,Iterator,JsonSerializable,Countable{
	protected $data = [];
	function __construct($data=[]){
		$this->data = $data;
	}
	function __set($k,$v){
		$this->data[$k] = $v;
	}
	function &__get($k){
		return $this->data[$k];
	}
	function __isset($k){
		return isset($this->data[$k]);
	}
	function __unset($k){
		unset($this->data[$k]);
	}
	
	function rewind(){
		reset($this->data);
	}
	function current(){
		return current($this->data);
	}
	function key(){
		return key($this->data);
	}
	function next(){
		return next($this->data);
	}
	function valid(){
		return key($this->data)!==null;
	}
	function count(){
		return count($this->data);
	}
	
	function offsetSet($k,$v){
		$this->__set($k,$v);
	}
	function &offsetGet($k){
		return $this->data[$k];
	}
	function offsetExists($k){
		return isset($this->data[$k]);
	}
	function offsetUnset($k){
		unset($this->data[$k]);
	}
	
	function setArray(array $data){
		$this->data = $data;
	}
	function getArrayTree(){
		if(func_num_args()){
			$o = func_get_arg(0);
		}
		else{
			$o = $this->data;
		}
		$a = [];
		foreach($o as $k=>$v){
			if(Cast::isScalar($v)){
				$a[$k] = Cast::scalar($v);
			}
			else{
				$a[$k] = $this->getArrayTree($v);
			}
		}
		return $a;
	}
	function getArray(){
		return $this->data;
	}
	
	function jsonSerialize(){
		$o = new stdClass();
		foreach($this->data as $k=>$v){
			$o->$k = $v;
		}
		return $o;
	}
	
	function __clone(){
		foreach($this->data as $k=>$o){
			$this->data[$k] = clone $o;
		}
	}
}