<?php
namespace FoxORM\Entity;
interface Observer{
	function beforeRecursive();
	function beforePut();
	function beforeCreate();
	function beforeRead();
	function beforeUpdate();
	function beforeDelete();
	function afterPut();
	function afterCreate();
	function afterRead();
	function afterUpdate();
	function afterDelete();
	function afterRecursive();
	
	function on($event,$call=null,$index=0,$prepend=false);
	function off($event,$call=null,$index=0);
	function trigger($event, $recursive=false, $flow=null);
}