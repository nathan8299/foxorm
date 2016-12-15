<?php
#FoxORM
#https://foxorm.com

#Std/ScalarInterface.php

namespace FoxORM\Std {
interface ScalarInterface{
	function __toString();
}
}
#Std/Cast.php

namespace FoxORM\Std {
use DateTime;
use FoxORM\Std\ScalarInterface;
abstract class Cast{
	static function isInt($value){
		return is_scalar($value)&&(strval($value)===strval(intval($value)));
	}
	
	static function isScalar($value, $special=true){
		if(is_scalar($value)||is_null($value)){
			return true;
		}
		if($special){
			if($value instanceof DateTime){
				return true;
			}
			if($value instanceof ScalarInterface){
				return true;
			}
		}
		return false;
	}
	
	static function scalar($value){
		if($value instanceof DateTime){
			$value = $value->format('Y-m-d H:i:s');
		}
		if($value instanceof ScalarInterface){
			$value = $value->__toString();
		}
		return $value;
	}
}
}
#Std/CaseConvert.php

namespace FoxORM\Std {
abstract class CaseConvert{
	static function snake($str){
        return str_replace(' ', '_', strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $str)));
	}
	static function camel($str){
		return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
	}
	static function pascal($str){
		return ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
	}
	static function ucw($str){
		return ucfirst(str_replace(' ', '_', ucwords(str_replace('_', ' ', $str))));
	}
	static function lcw($str){
		return lcfirst(str_replace(' ', '_', preg_replace_callback('~\b\w~', ['self', '_lcwordsCallback'],str_replace('_', ' ', $str))));
	}
	private static function _lcwordsCallback($matches){
		return strtolower($matches[0]);
	}
}
}
#Std/ArrayIterator.php

namespace FoxORM\Std {
use FoxORM\Std\Cast;
use ArrayAccess;
use Iterator;
use JsonSerializable;
use Countable;
use stdClass;
use BadMethodCallException;
class ArrayIterator implements ArrayAccess,Iterator,JsonSerializable,Countable{
	private $__readingState;
	private $__modified = false;
	
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
	
	protected function iteratorSkipNull(){
		while($this->current()===null&&$this->valid()){
			if($this->data instanceof Iterator){
				$this->data->next();
			}
			else{
				next($this->data);
			}
		}
	}
	
	function rewind(){
		if($this->data instanceof Iterator){
			$this->data->rewind();
		}
		else{
			reset($this->data);
		}
		$this->iteratorSkipNull();
	}
	function current(){
		if($this->data instanceof Iterator){
			return $this->data->current();
		}
		else{
			return current($this->data);
		}
	}
	function key(){
		if($this->data instanceof Iterator){
			return $this->data->key();
		}
		else{
			return key($this->data);
		}
	}
	function next(){
		if($this->data instanceof Iterator){
			$this->data->next();
		}
		else{
			next($this->data);
		}
		$this->iteratorSkipNull();
	}
	function valid(){
		if($this->data instanceof Iterator){
			return $this->data->valid();
		}
		else{
			return key($this->data)!==null;
		}
	}
	function count(){
		return count($this->data);
	}
	
	function offsetSet($k,$v){
		if(!$this->__readingState) $this->__modified = true;
		$this->__set($k,$v);
	}
	function &offsetGet($k){
		return $this->data[$k];
	}
	function offsetExists($k){
		return isset($this->data[$k]);
	}
	function offsetUnset($k){
		if(!$this->__readingState) $this->__modified = true;
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
		foreach($this as $k=>$v){
			$o->$k = $v;
		}
		return $o;
	}
	
	function __clone(){
		foreach($this->data as $k=>$o){
			$this->data[$k] = clone $o;
		}
	}
	
	function __modified(){
		return $this->__modified;
	}
	function __readingState($b){
		$this->__readingState = (bool)$b;
	}
	
	function __call($f,$args){
		if(is_object($this->data)){
			return call_user_func_array([$this->data,$f],$args);
		}
		throw new BadMethodCallException('Call to undefined method '.get_class($this).'->'.$f);
	}
}
}
#Exception/Exception.php

namespace FoxORM\Exception {
use FoxORM\DataSource;
class Exception extends \Exception {
	protected $db;
	function setDB(DataSource $db){
		$this->db = $db;
	}
	function getDB(){
		return $this->db;
	}
}
}
#Exception/QueryException.php

namespace FoxORM\Exception {
use FoxORM\Exception\Exception;
class QueryException extends Exception {
	protected $query;
	protected $params;
	function setQuery($q){
		$this->query = $q;
	}
	function setParams($p){
		$this->params = $p;
	}
	function getQuery(){
		return $this->query;
	}
	function getParams(){
		return $this->params;
	}
}
}
#Exception/SchemaException.php

namespace FoxORM\Exception {
use FoxORM\Exception\Exception;
class SchemaException extends Exception {
	
}
}
#Exception/ValidationException.php

namespace FoxORM\Exception {
use FoxORM\Exception\Exception;
class ValidationException extends Exception {
	protected $entity;
	function setEntity($row){
		$this->entity = $row;
	}
	function getEntity(){
		return $this->entity;
	}
}
}
#Bases.php

namespace FoxORM {
use FoxORM\Validate\Validate;
use InvalidArgumentException;
class Bases implements \ArrayAccess{
	private $map;
	private $mapObjects= [];
	private $modelClassPrefix;
	private $entityClassDefault;
	private $entityFactory;
	private $tableWrapperFactory;
	private $primaryKeyDefault;
	private $uniqTextKeyDefault;
	private $primaryKeys;
	private $uniqTextKeys;
	private $many2manyPrefix;
	private $tableWrapperClassDefault;
	private $debug;
	private $validateService;
	function __construct(array $map = [],$modelClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKeyDefault='id',$uniqTextKeyDefault='uniq',array $primaryKeys=[],array $uniqTextKeys=[],$many2manyPrefix='',$tableWrapperClassDefault=false,$debug=DataSource::DEBUG_DEFAULT){
		$this->map = $map;
		$this->modelClassPrefix = (array)$modelClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKeyDefault = $primaryKeyDefault;
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
		$this->primaryKeys = $primaryKeys;
		$this->uniqTextKeys = $uniqTextKeys;
		$this->many2manyPrefix = $many2manyPrefix;
		$this->tableWrapperClassDefault = $tableWrapperClassDefault;
		$this->debug = $debug;
		
		if(class_exists(Validate::class)){
			$this->validateService = new Validate();
		}
		
	}
	function debug($level=DataSource::DEBUG_ON){
		$this->debug = $level;
		foreach($this->mapObjects as $o)
			$o->debug($level);
	}
	function setEntityFactory($factory){
		$this->entityFactory = $factory;
	}
	function setTableWapperFactory($factory){
		$this->tableWrapperFactory = $factory;
	}
	function setModelClassPrefix($modelClassPrefix='Model\\'){
		$this->modelClassPrefix = (array)$modelClassPrefix;
	}
	function appendModelClassPrefix($modelClassPrefix){
		$this->modelClassPrefix[] = $modelClassPrefix;
	}
	function prependModelClassPrefix($modelClassPrefix){
		array_unshift($this->modelClassPrefix,$modelClassPrefix);
	}
	function setEntityClassDefault($entityClassDefault='stdClass'){
		$this->entityClassDefault = $entityClassDefault;
	}
	function setPrimaryKeyDefault($primaryKeyDefault='id'){
		$this->primaryKeyDefault = $primaryKeyDefault;
	}
	function setUniqTextKeyDefault($uniqTextKeyDefault='uniq'){
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
	}
	function offsetGet($k){
		if(!isset($this->map[$k]))
			throw new InvalidArgumentException('Try to access undefined DataSource layer "'.$k.'"');
		if(!isset($this->mapObjects[$k])){
			$this->mapObjects[$k] = $this->loadDataSource($this->map[$k]);
			if($this->debug){
				$this->mapObjects[$k]->debug($this->debug);
			}
		}
		return $this->mapObjects[$k];
	}
	function offsetSet($k,$v){
		$this->map[$k] = (array)$v;
		$this->mapObjects[$k] = null;
	}
	function offsetExists($k){
		return isset($this->map[$k]);
	}
	function offsetUnset($k){
		if(isset($this->map[$k]))
			unset($this->map[$k]);
		if(isset($this->mapObjects[$k]))
			unset($this->mapObjects[$k]);
	}
	function selectDatabase($key,$dsn,$user=null,$password=null,$config=[]){
		$this[$key] = [
			'dsn'=>$dsn,
			'user'=>$user,
			'password'=>$password,
		]+$config;
		return $this[$key];
	}
	private function loadDataSource(array $config){
		$modelClassPrefix = $this->modelClassPrefix;
		$entityClassDefault = $this->entityClassDefault;
		$primaryKey = $this->primaryKeyDefault;
		$uniqTextKey = $this->uniqTextKeyDefault;
		$primaryKeys = $this->primaryKeys;
		$uniqTextKeys = $this->uniqTextKeys;
		$many2manyPrefix = $this->many2manyPrefix;
		$tableWrapperClassDefault = $this->tableWrapperClassDefault;
		$debug = $this->debug;
		
		if(isset($config['type'])){
			$type = $config['type'];
		}
		elseif((isset($config[0])&&($dsn=$config[0]))||(isset($config['dsn'])&&($dsn=$config['dsn']))){
			$type = strtolower(substr($dsn,0,strpos($dsn,':')));
			$config['type'] = $type;
		}
		else{
			throw new InvalidArgumentException('Undefined type of DataSource, please use atleast key type, dsn or offset 0');
		}
		
		if(isset($config['modelClassPrefix'])){
			$modelClassPrefix = $config['modelClassPrefix'];
			unset($config['modelClassPrefix']);
		}
		if(isset($config['entityClassDefault'])){
			$entityClassDefault = $config['entityClassDefault'];
			unset($config['entityClassDefault']);
		}
		if(isset($config['tableWrapperClassDefault'])){
			$tableWrapperClassDefault = $config['tableWrapperClassDefault'];
			unset($config['tableWrapperClassDefault']);
		}
		if(isset($config['primaryKey'])){
			$primaryKey = $config['primaryKey'];
			unset($config['primaryKey']);
		}
		if(isset($config['uniqTextKey'])){
			$uniqTextKey = $config['uniqTextKey'];
			unset($config['uniqTextKey']);
		}
		if(isset($config['primaryKeys'])){
			$primaryKeys = $config['primaryKeys'];
			unset($config['primaryKeys']);
		}
		if(isset($config['uniqTextKeys'])){
			$uniqTextKeys = $config['uniqTextKeys'];
			unset($config['uniqTextKeys']);
		}
		if(isset($config['many2manyPrefix'])){
			$many2manyPrefix = $config['many2manyPrefix'];
			unset($config['many2manyPrefix']);
		}
		if(isset($config['debug'])){
			$debug = $config['debug'];
			unset($config['debug']);
		}
		
		$class = __NAMESPACE__.'\\DataSource\\'.ucfirst($type);
		$dataSource = new $class($this,$type,$modelClassPrefix,$entityClassDefault,$primaryKey,$uniqTextKey,$primaryKeys,$uniqTextKeys,$many2manyPrefix,$tableWrapperClassDefault,$debug,$config);
		if($this->entityFactory){
			$dataSource->setEntityFactory($this->entityFactory);
		}
		if($this->tableWrapperFactory){
			$dataSource->setTableWapperFactory($this->tableWrapperFactory);
		}
		return $dataSource;
	}
	function getValidateService(){
		return $this->validateService;
	}
}
}
#DataSource.php

namespace FoxORM {
use FoxORM\Std\Cast;
use FoxORM\Std\ArrayIterator;
use FoxORM\Std\CaseConvert;
use FoxORM\Std\ScalarInterface;
use FoxORM\Entity\StateFollower;
use FoxORM\Entity\Box;
use FoxORM\Entity\Observer;
use FoxORM\Entity\RulableInterface;
abstract class DataSource implements \ArrayAccess,\Iterator,\JsonSerializable{
	const DEBUG_OFF = 0;
	const DEBUG_ERROR = 1;
	const DEBUG_QUERY = 2;
	const DEBUG_RESULT = 4;
	const DEBUG_SPEED = 8;
	const DEBUG_EXPLAIN = 16;
	const DEBUG_SYSTEM = 32;
	const DEBUG_DEFAULT = 1;
	const DEBUG_ON = 31;
	protected $bases;
	protected $type;
	protected $modelClassSuffix = '_Row';
	protected $modelClassPrefix;
	protected $entityClassDefault;
	protected $tableWrapperClassDefault;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $primaryKeys;
	protected $uniqTextKeys;
	protected $many2manyPrefix;
	protected $tableMap = [];
	protected $entityFactory;
	protected $tableWrapperFactory;
	protected $recursiveStorageOpen = [];
	protected $recursiveStorageClose = [];
	protected $tablesList = [];
	protected $debugLevel;
	protected $performingSystemQuery = false;
	protected $performingOptionalQuery = false;
	function __construct(Bases $bases,$type,$modelClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKey='id',$uniqTextKey='uniq',array $primaryKeys=[],array $uniqTextKeys=[],$many2manyPrefix='',$tableWrapperClassDefault=false,$debugLevel=self::DEBUG_DEFAULT,array $config=[]){
		$this->bases = $bases;
		$this->type = $type;
		$this->modelClassPrefix = (array)$modelClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->tableWrapperClassDefault = $tableWrapperClassDefault;
		$this->primaryKey = $primaryKey;
		$this->uniqTextKey = $uniqTextKey;
		$this->primaryKeys = $primaryKeys;
		$this->uniqTextKeys = $uniqTextKeys;
		$this->many2manyPrefix = $many2manyPrefix;
		$this->debugLevel = $debugLevel;
		$this->construct($config);
	}
	function getType(){
		return $this->type;
	}
	
	function getUniqTextKey(){
		return $this->uniqTextKey;
	}
	function getPrimaryKey(){
		return $this->primaryKey;
	}
	function setUniqTextKey($uniqTextKey='uniq'){
		$this->uniqTextKey = $uniqTextKey;
	}
	function setPrimaryKey($primaryKey='id'){
		$this->primaryKey = $primaryKey;
	}
	
	function setTableUniqTextKey($table,$uniqTextKey='uniq'){
		$this->uniqTextKeys[$table] = $uniqTextKey;
	}
	function setTablePrimaryKey($table,$primaryKey='id'){
		$this->primaryKeys[$table] = $primaryKey;
	}
	function getTableUniqTextKey($table){
		return isset($this->uniqTextKeys[$table])?$this->uniqTextKeys[$table]:$this->uniqTextKey;
	}
	function getTablePrimaryKey($table){
		return isset($this->primaryKeys[$table])?$this->primaryKeys[$table]:$this->primaryKey;
	}
	
	function getUniqTextKeys(){
		return $this->uniqTextKeys;
	}
	function getPrimaryKeys(){
		return $this->primaryKeys;
	}
	function getMany2manyPrefix(){
		return $this->many2manyPrefix;
	}
	function setMany2manyPrefix($many2manyPrefix=''){
		$this->many2manyPrefix = $many2manyPrefix;
	}
	function setUniqTextKeys(array $uniqTextKeys=[]){
		$this->uniqTextKeys = $uniqTextKeys;
	}
	function setPrimaryKeys(array $primaryKeys=[]){
		$this->primaryKeys = $primaryKeys;
	}
	
	function isPrimaryKeyOf($col){
		$x = explode('_',$col);
		if(count($x)<2) return;
		$pk = array_pop($x);
		$table = implode('_',$x);
		if($pk==$this->getTablePrimaryKey($table)){
			return $table;
		}
	}
	
	function findTableWrapperClass($name=null,$tableWrapper=null){
		if($name){
			$name = CaseConvert::ucw($name);
			foreach($this->modelClassPrefix as $prefix){
				$c = $prefix.$name;
				if($tableWrapper)
					$c .= '_View_'.$tableWrapper;
				else
					$c .= '_Table';
				if(class_exists($c))
					return $c;
			}
		}
		return $this->tableWrapperClassDefault;
	}
	function findEntityClass($name=null){
		if($name){
			$name = CaseConvert::ucw($name);
			foreach($this->modelClassPrefix as $prefix){
				$c = $prefix.$name.$this->modelClassSuffix;
				if(class_exists($c))
					return $c;
			}
		}
		return class_exists($this->entityClassDefault)?$this->entityClassDefault:'stdClass';
	}
	function findEntityTable($obj,$default=null){
		$table = $default;
		if(isset($obj->_type)){
			$table = $obj->_type;
		}
		else{
			$c = get_class($obj);
			if($c!=$this->entityClassDefault){
				if($this->modelClassSuffix==''||substr($c,-1*strlen($this->modelClassSuffix))==$this->modelClassSuffix){
					foreach($this->modelClassPrefix as $prefix){
						if($prefix===false) continue;
						if($prefix==''||substr($c,0,strlen($prefix))===$prefix){
							$table = substr($c,strlen($prefix),-4);
							break;
						}
					}
				}
				$table = CaseConvert::lcw($table);
			}
		}
		return $table;
	}
	function arrayToEntity(array $array,$default=null){
		if(isset($array['_type']))
			$type = $array['_type'];
		elseif($default)
			$type = $default;
		else
			$type = $this->entityClassDefault;
		
		if(!isset($array['_modified']))
			$array['_modified'] = true;
		
		$obj = $this->entityFactory($type,$array);
		return $obj;
	}
	function offsetGet($k){
		if(!isset($this->tableMap[$k]))
			$this->tableMap[$k] = $this->loadTable($k);
		return $this->tableMap[$k];
	}
	function offsetSet($k,$v){
		if(!is_object($v))
			$v = $this->loadTable($v);
		$this->tableMap[$k] = $v;
	}
	function offsetExists($k){
		return isset($this->tableMap[$k]);
	}
	function offsetUnset($k){
		if(isset($this->tableMap[$k]))
			unset($this->tableMap[$k]);
	}
	function loadTable($k){
		$c = 'FoxORM\DataTable\\'.ucfirst($this->type);
		return new $c($k,$this);
	}
	function construct(array $config=[]){}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return;
		$obj = $this->entityFactory($type);
		
		if($obj instanceof StateFollower) $obj->__readingState(true);
		
		$this->trigger($type,'beforeRead',$obj);
		$obj = $this->readQuery($type,$id,$primaryKey,$uniqTextKey,$obj);
		if($obj){
			$obj->_type = $type;
			$this->trigger($type,'afterRead',$obj);
			$this->trigger($type,'unserializeColumns',$obj);
			
			if($obj instanceof StateFollower) $obj->__readingState(false);
			if($obj instanceof StateFollower||isset($obj->_modified)) $obj->_modified = false;
		}
		return $obj;
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return;
		if(Cast::isScalar($id)){
			$id = Cast::scalar($id);
		}
		if(is_object($id)){
			$obj = $id;
			if(isset($obj->$primaryKey))
				$id = $obj->$primaryKey;
			elseif(isset($obj->$uniqTextKey))
				$id = $obj->$uniqTextKey;
		}
		else{
			$obj = $this->entityFactory($type);
			if($id){
				if(Cast::isInt($id))
					$obj->$primaryKey = $id;
				else
					$obj->$uniqTextKey = $id;
			}
		}
		$this->trigger($type,'beforeDelete',$obj);
		$r = $this->deleteQuery($type,$id,$primaryKey,$uniqTextKey);
		if($r)
			$this->trigger($type,'afterDelete',$obj);
		return $r;
	}
	
	function putRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		
		$obj->_type = $type;
		$properties = [];
		$oneNew = [];
		$oneUp = [];
		$manyNew = [];
		$one2manyNew = [];
		$many2manyNew = [];
		$cast = [];
		$func = [];
		$fk = [];
		$refsOne = [];
		
		$manyIteratorByK = [];
		if(isset($id)){
			if($obj instanceof StateFollower) $obj->__readingState(true);
			if($uniqTextKey&&!Cast::isInt($id))
				$obj->$uniqTextKey = $id;
			else
				$obj->$primaryKey = $id;
			if($obj instanceof StateFollower) $obj->__readingState(false);
		}
		
		if(isset($obj->$primaryKey)){
			$id = $obj->$primaryKey;
		}
		elseif($uniqTextKey&&isset($obj->$uniqTextKey)){
			$id = $this->readId($type,$obj->$uniqTextKey,$primaryKey,$uniqTextKey);
			
			if($obj instanceof StateFollower) $obj->__readingState(true);
			$obj->$primaryKey = $id;
			if($obj instanceof StateFollower) $obj->__readingState(false);
		}
		
		$forcePK = isset($obj->_forcePK)?$obj->_forcePK:null;
		if($forcePK===true)
			$forcePK = $id;
		
		$update = isset($id)&&!$forcePK;
		
		$this->trigger($type,'beforeRecursive',$obj,'recursive',true);
		
		if(!isset($obj->_modified)||$obj->_modified!==false||!isset($id)){
			
			if($obj instanceof RulableInterface){
				$this->trigger($type,'beforeValidate',$obj);
				$obj->applyValidateProperties();
				$obj->applyValidatePreFilters();
				$obj->applyValidateRules();
				$obj->applyValidateFilters();
				$this->trigger($type,'afterValidate',$obj);
				if($update&&count($obj)<2) return;
			}
			
			$this->trigger($type,'beforePut',$obj);
			$this->trigger($type,'serializeColumns',$obj);
			if($update){
				$this->trigger($type,'beforeUpdate',$obj);
			}
			else{
				$this->trigger($type,'beforeCreate',$obj);
			}
		}
		
		foreach($obj as $key=>$v){
			$k = $key;
			$xclusive = substr($k,-3)=='_x_';
			if($xclusive)
				$k = substr($k,0,-3);
			$relation = false;
			
			if(Cast::isScalar($v)){
				$v = Cast::scalar($v);
			}
			
			if(substr($k,0,1)=='_'){
				if(substr($k,1,4)=='one_'){
					$k = substr($k,5);
					$relation = 'one';
				}
				elseif(substr($k,1,5)=='many_'){
					$k = substr($k,6);
					$relation = 'many';
				}
				elseif(substr($k,1,10)=='many2many_'){
					$k = substr($k,11);
					$relation = 'many2many';
				}
				else{
					if(substr($k,1,5)=='cast_'){
						$cast[substr($k,6)] = $v;
					}
					if(substr($k,1,5)=='func_'){
						$func[substr($k,6)] = $v;
					}
					continue;
				}
			}
			elseif(is_array($v)||($v instanceof ArrayIterator)){
				$relation = 'many';
			}
			elseif(is_object($v)){
				$relation = 'one';
			}
			elseif($t = $this->isPrimaryKeyOf($k)){
				$relation = 'oneByPK';
			}
			
			if($relation){
				switch($relation){
					case 'oneByPK':
						if(empty($v)) continue 2;
						$pk = $this[$t]->getPrimaryKey();
						$rc = $t.'_'.$pk;
						$addFK = [$type,$t,$rc,$pk,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
						$properties[$k] = $v;
					break;
					case 'one':
						if(empty($v)) continue 2;
						if(is_scalar($v))
							$v = $this->scalarToArray($v,$k);
						if(is_array($v))
							$v = $this->arrayToEntity($v,$k);
						
						$t = $k?$k:$this->findEntityTable($v);
						
						$pk = $this[$t]->getPrimaryKey();
						if(!is_null($v)){
							if(isset($v->$pk)){
								$oneUp[$t][$v->$pk] = $v;
							}
							else{
								$oneNew[$t][] = $v;
							}
						}
						$rc = $k.'_'.$pk;
						$refsOne[$rc] = &$v->$pk;
						
						$addFK = [$type,$t,$rc,$pk,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
						$obj->$key = $v;
					break;
					case 'many':
						if(!($v instanceof ArrayIterator)){
							$v = new ArrayIterator($v);
						}
						$v->__readingState(true);
						foreach($v as $mk=>$val){
							if(empty($val)) continue;
							if(is_scalar($val))
								$v[$mk] = $val = $this->scalarToArray($val,$k);
							if(is_array($val))
								$v[$mk] = $val = $this->arrayToEntity($val,$k);
							
							$t = $k?$k:$this->findEntityTable($v);
							
							$rc = $type.'_'.$primaryKey;
							$one2manyNew[$t][] = [$val,$rc];
							$addFK = [$t,$type,$rc,$primaryKey,$xclusive];
							if(!in_array($addFK,$fk))
								$fk[] = $addFK;
							
							$manyIteratorByK[$t] = $v;
						}
						$v->__readingState(false);
						$obj->$key = $v;
					break;
					case 'many2many':
						if(!($v instanceof ArrayIterator)){
							$obj->$key = $v = new ArrayIterator($v);
						}
						if(false!==$i=strpos($k,':')){ //via
							$inter = substr($k,$i+1);
							$k = substr($k,0,$i);
						}
						else{
							$inter = $this->many2manyTableName($type,$k);
						}
						$typeColSuffix = $type==$k?'2':'';
						$rc = $type.'_'.$primaryKey;
						$obj->{'_linkMany_'.$inter} = [];
						$v->__readingState(true);
						foreach($v as $kM2m=>$val){
							if(empty($val)) continue;
							if(is_scalar($val))
								$v[$kM2m] = $val = $this->scalarToArray($val,$k);
							if(is_array($val))
								$v[$kM2m] = $val = $this->arrayToEntity($val,$k);
							
							$t = $k?$k:$this->findEntityTable($v);
							
							$pk = $this[$t]->getPrimaryKey();
							$rc2 = $k.$typeColSuffix.'_'.$pk;
							$interm = $this->entityFactory($inter);
							$manyNew[$t][] = $val;
							$many2manyNew[$t][$k][$inter][] = [$interm,$rc,$rc2,&$val->$pk];
							$addFK = [$inter,$t,$rc2,$pk,$xclusive];
							if(!in_array($addFK,$fk))
								$fk[] = $addFK;
							$val->{'_linkOne_'.$inter} = $interm;
							$obj->{'_linkMany_'.$inter}[] = $interm;
							
							$manyIteratorByK[$t] = $v;
						}
						$v->__readingState(false);
						$addFK = [$inter,$type,$rc,$primaryKey,$xclusive];
						if(!in_array($addFK,$fk))
							$fk[] = $addFK;
						$obj->$key = $v;
					break;
				}
			}
			else{
				$properties[$k] = $v;
			}
		}
		
		foreach($oneNew as $t=>$ones){
			foreach($ones as $one){
				$this[$t][] = $one;
			}
		}
		foreach($oneUp as $t=>$ones){
			foreach($ones as $i=>$one){
				$this[$t][$i] = $one;
			}
		}
		foreach($refsOne as $rc=>$rf){
			$obj->$rc = $properties[$rc] = $rf;
		}
		
		if(!$update||!isset($obj->_modified)||$obj->_modified!==false){
			$modified = true;
			if($update){
				$r = $this->updateQuery($type,$properties,$id,$primaryKey,$uniqTextKey,$cast,$func);
				$obj->$primaryKey = $r;
				if($obj instanceof StateFollower||isset($obj->_modified))
					$obj->_modified = false;
				$this->trigger($type,'afterUpdate',$obj);
			}
			else{
				if(array_key_exists($primaryKey,$properties))
					unset($properties[$primaryKey]);
				$r = $this->createQuery($type,$properties,$primaryKey,$uniqTextKey,$cast,$func,$forcePK);
				$obj->$primaryKey = $r;
				if($obj instanceof StateFollower||isset($obj->_modified))
					$obj->_modified = false;
				$this->trigger($type,'afterCreate',$obj);
			}
		}
		else{
			$modified = false;
			$r = null;
		}
		
		foreach($one2manyNew as $k=>$v){
			if($update){
				$except = [];
				foreach($v as list($val,$rc)){
					$val->$rc =  $obj->$primaryKey;
					
					$t = $k;
					
					$pk = $this[$t]->getPrimaryKey();
					if(isset($val->$pk))
						$except[] = $val->$pk;
						
				}
				if($manyIteratorByK[$k]->__modified()){
					$this->one2manyDeleteAll($obj,$k,$except);
				}
			}
			foreach($v as list($val,$rc)){
				$val->$rc =  $obj->$primaryKey;
				$this[$k][] = $val;
			}
		}
		foreach($manyNew as $k=>$v){
			foreach($v as $val){
				$this[$k][] = $val;
			}
		}
		foreach($many2manyNew as $t=>$v){
			$modified = $manyIteratorByK[$t]->__modified();
			foreach($v as $k=>$viaLoop){
				foreach($viaLoop as $via=>$val){
					if($update){
						$except = [];
						$viaFk = $k.'_'.$this[$t]->getPrimaryKey();
						foreach($this->many2manyLink($obj,$t,$via,$viaFk) as $id=>$old){
							$pk = $this[$via]->getPrimaryKey();
							unset($old->$pk);
							if(false!==$i=array_search($old,$val)){
								$val[$i]->$pk = $id;
								$except[] = $id;
							}
						}
						if($modified){
							$this->many2manyDeleteAll($obj,$t,$via,$except,$viaFk);
						}
					}
					foreach($val as list($interm,$rc,$rc2,$vpk)){
						$interm->$rc = $obj->$primaryKey;
						$interm->$rc2 = $vpk;
						$this[$via][] = $interm;
					}
				}
			}
		}
		if(method_exists($this,'addFK')){
			foreach($fk as list($typ,$targetType,$property,$targetProperty,$isDep)){
				$this->addFK($typ,$targetType,$property,$targetProperty,$isDep);
			}
		}

		if($modified){
			$this->trigger($type,'afterPut',$obj);
			$this->trigger($type,'unserializeColumns',$obj);
		}
		
		$this->trigger($type,'afterRecursive',$obj,'recursive',false);
		return $r?$r:$obj->$primaryKey;
	}
	
	function setTableWapperFactory($factory){
		$this->tableWrapperFactory = $factory;
	}
	function tableWrapperFactory($name, DataTable $dataTable=null, $tableWrapper=null){
		if($this->tableWrapperFactory)
			return call_user_func($this->tableWrapperFactory,$name,$this,$dataTable,$tableWrapper);
		$c = $this->findTableWrapperClass($name,$tableWrapper);
		if($c)
			return new $c($name,$this,$dataTable);
	}
	
	function dataFilter($data,array $filter, $reversedFilter=false){
		if(!is_array($data)){
			$tmp = $data;
			$data = [];
			foreach($tmp as $k=>$v){
				$data[$k] = $v;
			}
		}
		if($reversedFilter){
			$data = array_filter($data, function($k)use($filter){
				return !in_array($k,$filter);
			},ARRAY_FILTER_USE_KEY);
		}
		else{
			$data = array_intersect_key($data, array_fill_keys($filter, null));
		}
		return $data;
	}
	
	function newEntity($name,$data=null,$filter=null,$reversedFilter=false){
		$preFilter = [];
		$table = $this[$name];
		$preFilter[] = $table->getPrimaryKey();
		$preFilter[] = $table->getUniqTextKey();
		if(is_array($data)){
			if(isset($data['_type'])&&$data['_type']){
				$nameSource = $data['_type'];
			}
		}
		elseif(is_object($data)){
			$nameSource = $this->findEntityTable($data);
		}
		else{
			$nameSource = null;
		}
		if($nameSource){
			$tableSource = $this[$nameSource];
			$pk = $tableSource->getPrimaryKey();
			$pku = $tableSource->getUniqTextKey();
			if(!in_array($pk,$preFilter)){
				$preFilter[] = $pk;
			}
			if(!in_array($pku,$preFilter)){
				$preFilter[] = $pku;
			}
		}
		$data = $this->dataFilter($data,$preFilter,true);
		return $this->entity($name,$data,$filter,$reversedFilter);
	}
	function entity($name,$data=null,$filter=null,$reversedFilter=false){
		return $this->entityMaker($name,$data,$filter,$reversedFilter,true);
	}
	function entityFactory($name,$data=null){
		return $this->entityMaker($name,$data,null,null,false);
	}
	function entityMaker($name,$data=null,$filter=null,$reversedFilter=false,$modified=null){
		if($data&&is_array($filter)){
			$data = $this->dataFilter($data,$filter,$reversedFilter);
		}
		if($this->entityFactory){
			$row = call_user_func($this->entityFactory,$name,$this);
		}
		else{
			$c = $this->findEntityClass($name);
			$row = new $c;
		}
		$row->_type = $name;
		if($row instanceof Box)
			$row->setDatabase($this);
		if(isset($modified)){
			$row->_modified = $modified;
		}
		if($data){
			if($modified===false&&$row instanceof StateFollower){
				$row->__readingState(true);
			}
			foreach($data as $k=>$v){
				if($k=='_type') continue;
				$row->$k = $v;
			}
			if($modified===false&&$row instanceof StateFollower){
				$row->__readingState(false);
			}
		}
		return $row;
	}
	function setEntityFactory($factory){
		$this->entityFactory = $factory;
	}
	
	function trigger($type, $event, $row, $recursive=false, $flow=null){
		return $this[$type]->trigger($event, $row, $recursive, $flow);
	}
	function triggerExec($events, $type, $event, $row, $recursive=false, $flow=null){
		if($recursive){
			if(isset($flow)){
				if($flow){
					if(isset($this->recursiveStorageOpen[$recursive])&&in_array($row,$this->recursiveStorageOpen[$recursive],true))
						return;
					$this->recursiveStorageOpen[$recursive][] = $row;
				}
				else{
					if(isset($this->recursiveStorageOpen[$recursive])&&false!==$i=array_search($row,$this->recursiveStorageOpen[$recursive],true)){
						unset($this->recursiveStorageOpen[$recursive][$i]);
						$this->recursiveStorageClose[$recursive][$i] = $row;
						if(!empty($this->recursiveStorageOpen[$recursive]))
							return;
					}
					ksort($this->recursiveStorageClose[$recursive]);
					$this->recursiveStorageClose[$recursive] = array_reverse($this->recursiveStorageClose[$recursive]);
					foreach($this->recursiveStorageClose[$recursive] as $v){
						$this->trigger($v->_type, $event, $v);
					}
					unset($this->recursiveStorageOpen[$recursive]);
					unset($this->recursiveStorageClose[$recursive]);
					return;
				}
			}
		}

		if($row instanceof Observer){
			foreach($events as $calls){
				foreach($calls as $call){
					if(is_string($call)){
						call_user_func([$row,$call], $this);
						$row->trigger($call, $recursive, $flow);
					}
					else{
						call_user_func($call, $row, $this);
					}
				}
			}
		}
		
		if($recursive){
			foreach($row as $k=>$v){
				if(substr($k,0,1)=='_'&&!in_array(current(explode('_',$k)),['one','many','many2many']))
					continue;
				if(is_array($v)){
					foreach($v as $val){
						if(is_object($val)&&!Cast::isScalar($v)){
							$this->trigger($val->_type, $event, $val, $recursive, $flow);
						}
					}
				}
				elseif(is_object($v)&&!Cast::isScalar($v)){
					$this->trigger($v->_type, $event, $v, $recursive, $flow);
				}
			}				
		}
	}
	
	function triggerTableWrapper($method,$type,$args){
		$this[$type]->triggerTableWrapper($method,$args);			
	}
	
	function create($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
		}
		else{
			list($type,$obj) = func_get_args();
		}
		return $this[$type]->offsetSet(null,$obj);
	}
	function read($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
			$pk = $this[$type]->getPrimaryKey();
			$id = $obj->$pk;
		}
		else{
			list($type,$id) = func_get_args();
		}
		return $this[$type]->offsetGet($id);
	}
	function update($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
			$pk = $this[$type]->getPrimaryKey();
			$id = $obj->$pk;
		}
		elseif(func_num_args()<3){
			list($type,$obj) = func_get_args();
			if(is_array($obj))
				$obj = $this->arrayToEntity($obj);
			$pk = $this[$type]->getPrimaryKey();
			$id = $obj->$pk;
		}
		else{
			list($type,$id,$obj) = func_get_args();
		}
		return $this[$type]->offsetSet($id,$obj);
	}
	function delete($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
			$id = $obj;
		}
		else{
			list($type,$id) = func_get_args();
		}
		return $this[$type]->offsetUnset($id);
	}
	function put($mixed){
		if(func_num_args()<2){
			$obj = is_array($mixed)?$this->arrayToEntity($mixed):$mixed;
			$type = $this->findEntityTable($obj);
		}
		else{
			list($type,$obj) = func_get_args();
		}
		return $this[$type]->offsetSet(null,$obj);
	}
	
	static function snippet($text,$query,$tokens=15,$start='<b>',$end='</b>',$sep=' <b>...</b> '){
		if(!trim($text))
			return '';
		$words = implode('|', explode(' ', preg_quote($query)));
		$s = '\s\x00-/:-@\[-`{-~'; //character set for start/end of words
		preg_match_all('#(?<=['.$s.']).{1,'.$tokens.'}(('.$words.').{1,'.$tokens.'})+(?=['.$s.'])#uis', $text, $matches, PREG_SET_ORDER);
		$results = [];
		foreach($matches as $line)
			$results[] = $line[0];
		$result = implode($sep, $results);
		$result = preg_replace('#'.$words.'#iu', $start.'$0'.$end, $result);
		return $result?$sep.$result.$sep:$text;
	}
	
	static function snippet2($text,$query,$max=60,$start='<b>',$end='</b>',$sep=' <b>...</b> '){
		if(!trim($text))
			return '';
		if($max&&strlen($text)>$max)
			$text = substr($text,0,$max).$sep;
		$x = explode(' ',$query);
		foreach($x as $q){
			$text = preg_replace('#'.preg_quote($q).'#iu',$start.'$0'.$end,$text);
		}
		return $text;
	}
	
	function one2manyDelete($obj,$k,$remove=[]){
		$remove = (array)$remove;
		$t = $this->findEntityTable($obj);
		$pk = $t.'_'.$this[$t]->getPrimaryKey();
		foreach($this->one2many($obj,$k,$except) as $o){
			if(in_array($o->$pk,$remove))
				$this->delete($o);
		}
	}
	function one2manyDeleteAll($obj,$k,$except=[]){
		$pk = $this[$k]->getPrimaryKey();
		foreach($this->one2many($obj,$k,$except) as $o){
			if(!in_array($o->$pk,$except))
				$this->delete($o);
		}
	}
	function many2manyDelete($obj,$k,$via=null,$remove=[]){
		$remove = (array)$remove;
		$pk = $k.'_'.$this[$k]->getPrimaryKey();
		foreach($this->many2manyLink($obj,$k,$via) as $o){
			if(in_array($o->$pk,$remove))
				$this->delete($o);
		}
	}
	function many2manyDeleteAll($obj,$k,$via=null,$except=[]){
		$t = $this->many2manyTableName($this->findEntityTable($obj),$k);
		$pk = $this[$t]->getPrimaryKey();
		foreach($this->many2manyLink($obj,$k,$via) as $o){
			if(!in_array($o->$pk,$except))
				$this->delete($o);
		}
	}
	
	function deleteMany($tableParent,$table,$id){
		$pk = $this[$table]->getPrimaryKey();
		foreach($this->one2many($this[$tableParent][$id],$table) as $o){
			$this->delete($o);
		}
	}
	
	function loadMany2one($obj,$type){
		return $this[$type]->loadOne($obj);
	}
	function loadOne2many($obj,$type){
		return $this[$type]->loadMany($obj);
	}
	function loadMany2many($obj,$type,$via=null){
		return $this[$type]->loadMany2many($obj,$via);
	}
	
	//abstract function many2one($obj,$type){}
	//abstract function one2many($obj,$type){}
	//abstract function many2many($obj,$type){}
	//abstract function many2manyLink($obj,$type){}
	
	function rewind(){
		reset($this->tablesList);
	}
	function current(){
		return $this[current($this->tablesList)];
	}
	function key(){
		return current($this->tablesList);
	}
	function next(){
		$next = next($this->tablesList);
		if($next!==false)
			return $this[$next];
	}
	function valid(){
		return key($this->tablesList)!==null;
	}
	
	function scalarToArray($v,$type){
		$a = ['_type'=>$type];
		if(Cast::isInt($v)){
			$a[$this[$type]->getPrimaryKey()] = $v;
		}
		else{
			$a[$this[$type]->getUniqTextKey()] = $v;
		}
		return $a;
	}
	
	function jsonSerialize(){
		$data = [];
		foreach($this as $name=>$row){
			$data[$name] = $row;
		}
		return $data;
	}
	
	function many2manyTableName(){
		$a = [];
		foreach(func_get_args() as $arg){
			if(is_array($arg)){
				$a = array_merge($a,$arg);
			}
			else{
				$a[] = $arg;
			}
		}
		sort($a);
		return $this->many2manyPrefix.implode('_',$a);
	}
	function debug($level=self::DEBUG_ON){
		if($level===true) $level = self::DEBUG_ON;
		elseif(is_string($level)) $level = $this->debugLevelStringToConstant($level);
		$this->debugLevel = $level;
	}
	protected function debugLevelStringToConstant($level){
		return constant(__CLASS__.'::DEBUG_'.strtoupper($level));
	}
	function debugLevel($level=null){
		if(!is_null($level)){
			if(is_string($level))
				$level = $this->debugLevelStringToConstant($level);
			return $this->debugLevel&$level;
		}
		else{
			return $this->debugLevel;
		}
	}
	abstract function getAll($q, $bind = []);
	abstract function getRow($q, $bind = []);
	abstract function getCol($q, $bind = []);
	abstract function getCell($q, $bind = []);
	
	function getAllIterator($q, $bind){
		return new ArrayIterator($this->getAll($q, $bind));
	}
	function getValidateService(){
		return $this->bases->getValidateService();
	}
}
}
#DataSource/SQL.php

namespace FoxORM\DataSource {
use FoxORM\Std\Cast;
use FoxORM\DataSource;
use FoxORM\Helper\SqlLogger;
use FoxORM\Exception\QueryException;
use FoxORM\Exception\SchemaException;
use FoxORM\Entity\StateFollower;
use FoxORM\Entity\Observer;
use PDOException;
use InvalidArgumentException;
abstract class SQL extends DataSource{
	protected $dsn;
	protected $pdo;
	protected $affectedRows;
	protected $resultArray;
	protected $connectUser;
	protected $connectPass;
	protected $isConnected;
	protected $logger;
	protected $options;
	protected $max = PHP_INT_MAX;
	protected $createDb;
	protected $unknownDatabaseCode;
	protected $encoding = 'utf8';
	protected $flagUseStringOnlyBinding = false;
	protected $transactionCount = 0;
	
	//QueryWriter
	const C_DATATYPE_RANGE_SPECIAL   = 80;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $frozen;
	protected $typeno_sqltype = [];
	protected $sqltype_typeno = [];
	protected $quoteCharacter = '"';
	protected $defaultValue = 'NULL';
	protected $tablePrefix;
	protected $sqlFiltersWrite = [];
	protected $sqlFiltersRead = [];
	protected $ftsTableSuffix = '_fulltext_';
	
	protected $separator = ',';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $concatenator;
	
	private $cacheTables;
	private $cacheColumns = [];
	private $cacheFk = [];
	
	function construct(array $config=[]){		
		if(isset($config[0]))
			$this->dsn = $config[0];
		else
			$this->dsn = isset($config['dsn'])?$config['dsn']:$this->buildDsnFromArray($config);
		
		if(isset($config[1]))
			$user = $config[1];
		else
			$user = isset($config['user'])?$config['user']:null;
		if(isset($config[2]))
			$password = $config[2];
		else
			$password = isset($config['password'])?$config['password']:null;
		if(isset($config[3]))
			$options = $config[3];
		else
			$options = isset($config['options'])?$config['options']:[];
		
		$frozen = isset($config[4])?$config[4]:(isset($config['frozen'])?$config['frozen']:null);
		$createDb = isset($config[5])?$config[5]:(isset($config['createDb'])?$config['createDb']:true);

		$tablePrefix = isset($config['tablePrefix'])?$config['tablePrefix']:null;
		
		$this->connectUser = $user;
		$this->connectPass = $password;
		$this->options = $options;
		$this->createDb = $createDb;
		
		$this->frozen = $frozen;
		$this->tablePrefix = $tablePrefix;
		
		if(defined('HHVM_VERSION')||$this->dsn==='test-sqlite-53')
			$this->max = 2147483647;
	}
	function readId($type,$id,$primaryKey=null,$uniqTextKey=null){
		if(is_null($primaryKey))
			$primaryKey = $this[$type]->getPrimaryKey();
		if(is_null($uniqTextKey))
			$uniqTextKey = $this[$type]->getUniqTextKey();
		$intId = Cast::isInt($id);
		if(!$this->tableExists($type)||(!$intId&&!in_array($uniqTextKey,array_keys($this->getColumns($type)))))
			return;
		$table = $this->escTable($type);
		$where = $intId?$primaryKey:$uniqTextKey;
		return $this->getCell('SELECT '.$primaryKey.' FROM '.$table.' WHERE '.$where.'=?',[$id]);
	}
	protected function createQueryExec($table,$pk,$insertcolumns,$id,$insertSlots,$suffix,$insertvalues){
		return $this->getCell('INSERT INTO '.$table.' ( '.$pk.', '.implode(',',$insertcolumns).' ) VALUES ( '.$id.', '. implode(',',$insertSlots).' ) '.$suffix,$insertvalues);
	}
	function createQuery($type,$properties,$primaryKey='id',$uniqTextKey='uniq',$cast=[],$func=[],$forcePK=null){
		$insertcolumns = array_keys($properties);
		$insertvalues = array_values($properties);
		$id = $forcePK?$forcePK:$this->defaultValue;
		$suffix  = $this->getInsertSuffix($primaryKey);
		$table   = $this->escTable($type);
		$this->adaptStructure($type,$properties,$primaryKey,$uniqTextKey,$cast);
		$pk = $this->esc($primaryKey);
		if(!empty($insertcolumns)||!empty($func)){
			$insertSlots = [];
			foreach($insertcolumns as $k=>$v){
				$insertcolumns[$k] = $this->esc($v);
				$insertSlots[] = $this->getWriteSnippet($type,$v);
			}
			foreach($func as $k=>$v){
				$insertcolumns[] = $this->esc($k);
				$insertSlots[] = $v;
			}
			$result = $this->createQueryExec($table,$pk,$insertcolumns,$id,$insertSlots,$suffix,$insertvalues);
		}
		else{
			$result = $this->getCell('INSERT INTO '.$table.' ('.$pk.') VALUES('.$id.') '.$suffix);
		}
		if($suffix)
			$id = $result;
		else
			$id = (int)$this->pdo->lastInsertId();
		if(!$this->frozen&&method_exists($this,'adaptPrimaryKey'))
			$this->adaptPrimaryKey($type,$id,$primaryKey);
		return $id;
	}
	function readQuery($type,$id,$primaryKey='id',$uniqTextKey='uniq',$obj){
		if($uniqTextKey&&!Cast::isInt($id))
			$primaryKey = $uniqTextKey;
		$table = $this->escTable($type);
		$select = $this->getSelectSnippet($type);
		$sql = "SELECT {$select} FROM {$table} WHERE {$primaryKey}=? LIMIT 1";
		$row = $this->getRow($sql,[$id]);
		if($row){
			foreach($row as $k=>$v)
				$obj->$k = $v;
			return $obj;
		}
	}
	function updateQuery($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq',$cast=[],$func=[]){
		if(!$this->tableExists($type))
			return;
		$this->adaptStructure($type,$properties,$primaryKey,$uniqTextKey,$cast);
		$fields = [];
		$binds = [];
		foreach($properties as $k=>$v){
			if($k==$primaryKey)
				continue;
			if(isset($this->sqlFiltersWrite[$type][$k])){
				$fields[] = ' '.$this->esc($k).' = '.$this->sqlFiltersWrite[$type][$k];
				$binds[] = $v;
			}
			else{
				$fields[] = ' '.$this->esc($k).' = ?';
				$binds[] = $v;
			}
		}
		foreach($func as $k=>$v){
			$fields[] = ' '.$this->esc($k).' = '.$v;
		}
		if(empty($fields))
			return $id;
		$binds[] = $id;
		$table = $this->escTable($type);
		$this->execute('UPDATE '.$table.' SET '.implode(',',$fields).' WHERE '.$primaryKey.' = ? ', $binds);
		return $id;
	}
	function deleteQuery($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&!Cast::isInt($id))
			$primaryKey = $uniqTextKey;
		$this->execute('DELETE FROM '.$this->escTable($type).' WHERE '.$primaryKey.' = ?', [$id]);
		return $this->affectedRows;
	}
	
	private function buildDsnFromArray($config){
		$type = $config['type'].':';
		$host = isset($config['host'])&&$config['host']?'host='.$config['host']:'';
		$socket = isset($config['socket'])&&$config['socket']?'unix_socket='.$config['socket']:'';
		$file = isset($config['file'])&&$config['file']?$config['file']:'';
		$port = isset($config['port'])&&$config['port']?';port='.$config['port']:null;
		$name = isset($config['name'])&&$config['name']?';dbname='.$config['name']:null;
		return $type.($socket?$socket:$host).$file.($socket?'':$port).$name;
	}
	
	
	//PDO
	function getEncoding(){
		return $this->encoding;
	}
	protected function bindParams( $statement, $bindings ){
		foreach ( $bindings as $key => &$value ) {
			if(is_integer($key)){
				if(is_null($value))
					$statement->bindValue( $key + 1, NULL, \PDO::PARAM_NULL );
				elseif(!$this->flagUseStringOnlyBinding && Cast::isInt( $value ) && abs( $value ) <= $this->max)
					$statement->bindParam($key+1,$value,\PDO::PARAM_INT);
				else
					$statement->bindParam($key+1,$value,\PDO::PARAM_STR);
			}
			else{
				if(is_null($value))
					$statement->bindValue( $key, NULL, \PDO::PARAM_NULL );
				elseif( !$this->flagUseStringOnlyBinding && Cast::isInt( $value ) && abs( $value ) <= $this->max )
					$statement->bindParam( $key, $value, \PDO::PARAM_INT );
				else
					$statement->bindParam( $key, $value, \PDO::PARAM_STR );
			}
		}
	}
	protected function runQuery( $sql, $bindings, $options = [] ){
		$this->resultArray = [];
		$this->connect();
		$sql = str_replace('{#prefix}',$this->tablePrefix,$sql);
		$debugOverride = !$this->performingSystemQuery||$this->debugLevel&self::DEBUG_SYSTEM;
		if($debugOverride&&$this->debugLevel&self::DEBUG_QUERY)
			$this->logger->logSql($sql, $bindings);
		try {
			list($sql,$bindings) = self::nestBinding($sql,$bindings);
			$statement = $this->pdo->prepare( $sql );
			$this->bindParams( $statement, $bindings );
			if($debugOverride&&$this->debugLevel&self::DEBUG_SPEED)
				$start = microtime(true);
			$statement->execute();
			if($debugOverride&&$this->debugLevel&self::DEBUG_SPEED){
				$chrono = microtime(true)-$start;
				if($chrono>=1){
					$u = 's';
				}
				else{
					$chrono = $chrono*(float)1000;
					$u = 'ms';
				}
				$this->logger->logChrono(sprintf("%.2f", $chrono).' '.$u);
			}
			if($debugOverride&&$this->debugLevel&self::DEBUG_EXPLAIN){
				try{
					$explain = $this->explain($sql,$bindings);
					if($explain)
						$this->logger->logExplain($explain);
				}
				catch(PDOException $e){
					//$this->logger->log($e->getMessage());
				}
			}
			$this->affectedRows = $statement->rowCount();
			if($statement->columnCount()){
				$fetchStyle = ( isset( $options['fetchStyle'] ) ) ? $options['fetchStyle'] : NULL;
				if ( isset( $options['noFetch'] ) && $options['noFetch'] ) {
					if($debugOverride&&$this->debugLevel&self::DEBUG_QUERY||$this->debugLevel&self::DEBUG_RESULT)
						$this->logger->log('result via iterator cursor');
					return $statement;
				}
				$this->resultArray = $statement->fetchAll( $fetchStyle );
				if($debugOverride&&$this->debugLevel&self::DEBUG_RESULT){
					$this->logger->logResult($this->resultArray);
				}
				elseif($debugOverride&&$this->debugLevel&self::DEBUG_QUERY){
					$this->logger->log('resultset: '.count($this->resultArray).' rows');
				}
			}
		}
		catch(PDOException $e){
			if(!$this->performingOptionalQuery){
				if($this->debugLevel&self::DEBUG_ERROR){
					$this->logger->log('An error occurred: '.$e->getMessage());
					$this->logger->logSql( $sql, $bindings );
					if(!$this->debugLevel&self::DEBUG_QUERY){
						$this->logger->logSql($sql, $bindings);
					}
					throw $e;
				}
			}
		}
	}
	function setUseStringOnlyBinding( $yesNo ){
		$this->flagUseStringOnlyBinding = (boolean) $yesNo;
	}
	function setMaxIntBind( $max ){
		if ( !is_integer( $max ) )
			throw new InvalidArgumentException( 'Parameter has to be integer.' );
		$oldMax = $this->max;
		$this->max = $max;
		return $oldMax;
	}
	protected function setPDO($dsn){
		$this->pdo = new \PDO($dsn,$this->connectUser,$this->connectPass);
		$this->pdo->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, TRUE );
		$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
		if(!empty($this->options)) foreach($this->options as $opt=>$attr) $this->pdo->setAttribute($opt,$attr);
	}
	function getDSN(){
		return $this->dsn;
	}
	function getDbName(){
		$dsn = $this->dsn;
		$p = strpos($this->dsn,'dbname=')+7;
		$p2 = strpos($dsn,';',$p);
		if($p2===false){
			$dbname = substr($dsn,$p);
		}
		else{
			$dbname = substr($dsn,$p,$p2-$p);
		}
		return $dbname;
	}
	function connect(){
		if($this->isConnected)
			return;
		try {
			$this->setPDO($this->dsn);
			$this->isConnected = true;
		}
		catch ( PDOException $exception ) {
			if($this->createDb&&(!$this->unknownDatabaseCode||$this->unknownDatabaseCode==$exception->getCode())){				
				$dsn = $this->dsn;
				$p = strpos($this->dsn,'dbname=')+7;
				$p2 = strpos($dsn,';',$p);
				if($p2===false){
					$dbname = substr($dsn,$p);
					$dsn = substr($dsn,0,$p-8);
				}
				else{
					$dbname = substr($dsn,$p,$p2-$p);
					$dsn = substr($dsn,0,$p-7).substr($dsn,$p2+1);
				}
				$this->setPDO($dsn);
				$this->createDatabase($dbname);
				$this->execute('use '.$dbname);
				$this->isConnected = true;
			}
			else{
				$this->isConnected = false;
				throw $exception;
			}
		}
	}
	
	function getAll( $sql, $bindings = [] ){
		$this->runQuery( $sql, $bindings );
		return $this->resultArray;
	}
	function getRow( $sql, $bindings = [] ){
		$arr = $this->getAll( $sql, $bindings );
		return array_shift( $arr );
	}
	function getCol( $sql, $bindings = [] ){
		$rows = $this->getAll( $sql, $bindings );
		$cols = [];
		if ( $rows && is_array( $rows ) && count( $rows ) > 0 )
			foreach ( $rows as $row )
				$cols[] = array_shift( $row );
		return $cols;
	}
	function getCell( $sql, $bindings = [] ){
		$arr = $this->getAll( $sql, $bindings );
		if ( !is_array( $arr ) ) return NULL;
		if ( count( $arr ) === 0 ) return NULL;
		$row1 = array_shift( $arr );
		if ( !is_array( $row1 ) ) return NULL;
		if ( count( $row1 ) === 0 ) return NULL;
		$col1 = array_shift( $row1 );
		return $col1;
	}
	function exec( $sql, $bindings = [] ){
		return $this->execute($sql, $bindings);
	}
	function execute( $sql, $bindings = [] ){
		$this->runQuery( $sql, $bindings );
		return $this->affectedRows;
	}
	
	function tryGetAll($sql,$bindings=[]){
		$tmp = $this->performingOptionalQuery;
		$this->performingOptionalQuery = true;
		$r = $this->getAll($sql,$bindings);
		$this->performingOptionalQuery = $tmp;
		return (array)$r;
	}
	function tryGetRow($sql,$bindings=[]){
		$tmp = $this->performingOptionalQuery;
		$this->performingOptionalQuery = true;
		$r = $this->getRow($sql,$bindings);
		$this->performingOptionalQuery = $tmp;
		return (array)$r;
	}
	function tryGetCol($sql,$bindings=[]){
		$tmp = $this->performingOptionalQuery;
		$this->performingOptionalQuery = true;
		$r = $this->getCol($sql,$bindings);
		$this->performingOptionalQuery = $tmp;
		return (array)$r;
	}
	function tryGetCell($sql,$bindings=[]){
		$tmp = $this->performingOptionalQuery;
		$this->performingOptionalQuery = true;
		$r = $this->getCell($sql,$bindings);
		$this->performingOptionalQuery = $tmp;
		return $r;
	}
	function tryExec($sql,$bindings=[]){
		$tmp = $this->performingOptionalQuery;
		$this->performingOptionalQuery = true;
		$r = $this->exec($sql,$bindings);
		$this->performingOptionalQuery = $tmp;
		return $r;
	}
	function tryExecute($sql,$bindings=[]){
		$tmp = $this->performingOptionalQuery;
		$this->performingOptionalQuery = true;
		$r = $this->execute($sql,$bindings);
		$this->performingOptionalQuery = $tmp;
		return $r;
	}

	function getInsertID(){
		$this->connect();
		return (int) $this->pdo->lastInsertId();
	}
	function fetch( $sql, $bindings = [] ){
		return $this->runQuery( $sql, $bindings, [ 'noFetch' => true ] );
	}
	function affectedRows(){
		$this->connect();
		return (int) $this->affectedRows;
	}
	function getLogger(){
		return $this->logger;
	}
	
	function begin(){
		$this->connect();
		if(!$this->transactionCount++){
			if($this->debugLevel&self::DEBUG_QUERY)
				$this->logger->log('TRANSACTION BEGIN');
			return $this->pdo->beginTransaction();
		}
		$this->exec('SAVEPOINT trans'.$this->transactionCount);
		if($this->debugLevel&self::DEBUG_QUERY)
			$this->logger->log('TRANSACTION SAVEPOINT trans'.$this->transactionCount);
		return $this->transactionCount >= 0;
	}

	function commit(){
		$this->connect();
		if(!--$this->transactionCount){
			if($this->debugLevel&self::DEBUG_QUERY)
				$this->logger->log('TRANSACTION COMMIT');
			return $this->pdo->commit();
		}
		return $this->transactionCount >= 0;
	}

	function rollback(){
		$this->connect();
		if(--$this->transactionCount){
			if($this->debugLevel&self::DEBUG_QUERY)
				$this->logger->log('TRANSACTION ROLLBACK TO trans'.$this->transactionCount+1);
			$this->exec('ROLLBACK TO trans'.$this->transactionCount+1);
			return true;
		}
		$this->logger->log('TRANSACTION ROLLBACK');
		return $this->pdo->rollback();
	}

	function getDatabaseType(){
		$this->connect();
		return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME );
	}
	function getDatabaseVersion(){
		$this->connect();
		return $this->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION );
	}
	function getPDO(){
		$this->connect();
		return $this->pdo;
	}
	function close(){
		$this->pdo         = null;
		$this->isConnected = null;
	}
	function isConnected(){
		return $this->isConnected;
	}
	function debug($level=self::DEBUG_ON){
		parent::debug($level);
		if($this->debugLevel&&!$this->logger)
			$this->logger = new SqlLogger(true);
	}
	function getIntegerBindingMax(){
		return $this->max;
	}
	abstract function createDatabase($dbname);
	
	private static function pointBindingLoop($sql,$binds){
		$nBinds = [];
		foreach($binds as $k=>$v){
			if(is_integer($k))
				$nBinds[] = $v;
		}
		$i = 0;
		foreach($binds as $k=>$v){
			if(!is_integer($k)){
				$find = ':'.ltrim($k,':');
				while(false!==$p=strpos($sql,$find)){
					$preSql = substr($sql,0,$p);
					$sql = $preSql.'?'.substr($sql,$p+strlen($find));
					$c = count(explode('?',$preSql))-1;
					array_splice($nBinds,$c,0,[$v]);
				}
			}
			$i++;
		}
		return [$sql,$nBinds];
	}
	private static function nestBindingLoop($sql,$binds){
		$nBinds = [];
		$ln = 0;
		foreach($binds as $k=>$v){
			if(is_array($v)){
				$c = count($v);
				$av = array_values($v);
				if($ln)
					$p = strpos($sql,'?',$ln);
				else
					$p = self::posnth($sql,'?',$k);
				if($p!==false){
					$nSql = substr($sql,0,$p);
					$nSql .= '('.implode(',',array_fill(0,$c,'?')).')';
					$ln = strlen($nSql);
					$nSql .= substr($sql,$p+1);
					$sql = $nSql;
					for($y=0;$y<$c;$y++)
						$nBinds[] = $av[$y];
				}
			}
			else{
				if($ln)
					$p = strpos($sql,'?',$ln);
				else
					$p = self::posnth($sql,'?',$k);
				$ln = $p+1;
				$nBinds[] = $v;
			}
		}
		return [$sql,$nBinds];
	}
	static function posnth($haystack,$needle,$n,$offset=0){
		$l = strlen($needle);
		for($i=0;$i<=$n;$i++){
			$indx = strpos($haystack, $needle, $offset);
			if($i==$n||$indx===false)
				return $indx;
			else
				$offset = $indx+$l;
		}
		return false;
	}
	static function nestBinding($sql,$binds){
		do{
			list($sql,$binds) = self::pointBindingLoop($sql,(array)$binds);
			list($sql,$binds) = self::nestBindingLoop($sql,(array)$binds);
			$containA = false;
			foreach($binds as $v)
				if($containA=is_array($v))
					break;
		}
		while($containA);
		if(($c=substr_count($sql,'?'))!=($c2=count($binds))){
			throw $this->queryException('ERROR: Query "'.$sql.'" need '.$c.' parameters, but request give '.$c2,$sql,$binds);
		}
		return [$sql,$binds];
	}
	
	//QueryWriter
	function adaptStructure($type,$properties,$primaryKey='id',$uniqTextKey=null,$cast=[]){
		if($this->frozen)
			return;
		if(!$this->tableExists($type))
			$this->createTable($type,$primaryKey);
		$columns = $this->getColumns($type);
		$adaptations = [];
		foreach($properties as $column=>$value){
			if(!isset($columns[$column])){
				if(isset($cast[$column])){
					$colType = $cast[$column];
					unset($cast[$column]);
				}
				else{
					$colType = $this->scanType($value,true);
				}
				$this->addColumn($type,$column,$colType);
				$adaptations[] = $column;
			}
			else{
				$typedesc = $columns[$column];
				$typenoOld = $this->columnCode($typedesc);
				if(isset($cast[$column])){
					$snip = explode(' ',$cast[$column]);
					$snip = $snip[0];
					$typeno = $this->columnCode($snip);
					$colType = $cast[$column];
					unset($cast[$column]);
				}
				else{
					$typeno = $this->scanType($value,false);
					$colType = $typeno;
				}
				if($typenoOld<self::C_DATATYPE_RANGE_SPECIAL&&$typenoOld<$typeno){
					$this->changeColumn($type,$column,$colType);
					$adaptations[] = $column;
				}
			}
			if(isset($uniqTextKey)&&$uniqTextKey==$column){
				$this->addUniqueConstraint($type,$column);
			}
		}
		foreach($cast as $column=>$value){
			if(!isset($columns[$column])){
				$this->addColumn($type,$column,$cast[$column]);
				$adaptations[] = $column;
			}
			else{
				$typedesc = $columns[$column];
				$typenoOld = $this->columnCode($typedesc);
				$snip = explode(' ',$cast[$column]);
				$snip = $snip[0];
				$typeno = $this->columnCode($snip);
				$colType = $cast[$column];
				if($typenoOld<self::C_DATATYPE_RANGE_SPECIAL&&$typenoOld<$typeno){
					$this->changeColumn($type,$column,$colType);
					$adaptations[] = $column;
				}
			}
			if(isset($uniqTextKey)&&$uniqTextKey==$column){
				$this->addUniqueConstraint($type,$column);
				$adaptations[] = $column;
			}
		}
		
		if(!empty($adaptations)){
			$this->triggerTableWrapper('onAdaptColumns',$type,[$adaptations]);
		}
	}
	
	protected function getInsertSuffix($primaryKey){
		return '';
	}
	function unbindRead($type,$property=null,$func=null){
		if(!isset($property)){
			if(isset($this->sqlFiltersRead[$type])){
				unset($this->sqlFiltersRead[$type]);
				return true;
			}
		}
		elseif(!isset($func)){
			if(isset($this->sqlFiltersRead[$type][$property])){
				unset($this->sqlFiltersRead[$type][$property]);
				return true;
			}
		}
		elseif(false!==$i=array_search($func,$this->sqlFiltersRead[$type][$property])){
			unset($this->sqlFiltersRead[$type][$property][$i]);
			return true;
		}
	}
	function bindRead($type,$property,$func){
		$this->sqlFiltersRead[$type][$property][] = $func;
	}
	function unbindWrite($type,$property=null){
		if(!isset($property)){
			if(isset($this->sqlFiltersWrite[$type])){
				unset($this->sqlFiltersWrite[$type]);
				return true;
			}
		}
		elseif(isset($this->sqlFiltersWrite[$type][$property])){
			unset($this->sqlFiltersWrite[$type][$property]);
			return true;
		}
	}
	function bindWrite($type,$property,$func){
		$this->sqlFiltersWrite[$type][$property] = $func;
	}
	function setSQLFiltersRead(array $sqlFilters){
		$this->sqlFiltersRead = $sqlFilters;
	}
	function getSQLFiltersRead(){
		return $this->sqlFiltersRead;
	}
	function setSQLFiltersWrite(array $sqlFilters){
		$this->sqlFiltersWrite = $sqlFilters;
	}
	function getSQLFiltersWrite(){
		return $this->sqlFiltersWrite;
	}
	function getReadSnippetArray($type,$aliasMap=[]){
		$sqlFilters = [];
		$table = $this->escTable($type);
		if(isset($this->sqlFiltersRead[$type])){
			foreach($this->sqlFiltersRead[$type] as $property=>$funcs){
				$property = $this->esc($property);
				foreach($funcs as $func){
					$select = $table.'.'.$property;
					if(strpos($func,'(')===false)
						$func = $func.'('.$select.')';
					else
						$func = str_replace('?',$select,$func);
					if(strpos(strtolower($func),' as ')===false){
						$func .= ' AS ';
						if(isset($aliasMap[$property]))
							$func .= $aliasMap[$property];
						else
							$func .= $property;
					}
					$sqlFilters[] = $func;
				}
			}
		}
		return $sqlFilters;
	}
	function getReadSnippet($type,$aliasMap=[]){
		$sqlFilters = $this->getReadSnippetArray($type,$aliasMap);
		return !empty($sqlFilters)?implode(',',$sqlFilters):'';
	}
	function getWriteSnippet($type,$property){
		if(isset($this->sqlFiltersWrite[$type][$property])){
			$slot = $this->sqlFiltersWrite[$type][$property];
			if(strpos($slot,'(')===false)
				$slot = $slot.'(?)';
		}
		else{
			$slot = '?';
		}
		return $slot;
	}
	function getReadSnippetCol($type,$col,$s=null){
		if(!$s)
			$s = $this->escTable($type).'.'.$this->esc($col);
		if(isset($this->sqlFiltersRead[$type][$col][0])){
			$func = $this->sqlFiltersRead[$type][$col][0];
			if(strpos($func,'(')===false)
				$s = $func.'('.$s.')';
			else
				$s = str_replace('?',$s,$func);
		}
		return $s;
	}
	function getSelectSnippet($type,$aliasMap=[]){
		$select = [];
		$load = $this[$type]->getLoadColumnsSnippet();
		$read = $this->getReadSnippet($type,$aliasMap);
		if(!empty($load))
			$select[] = $load;
		if(!empty($read))
			$select[] = $read;
		return implode(',',$select);
	}
	
	function check($struct){
		if(!preg_match('/^[a-zA-Z0-9_-]+$/',$struct))
			throw new InvalidArgumentException('Table or Column name "'.$struct.'" does not conform to FoxORM security policies' );
		return $struct;
	}
	function esc($esc){
		$this->check($esc);
		return $this->quoteCharacter.$esc.$this->quoteCharacter;
	}
	function escTable($table){
		$this->check($table);
		return $this->quoteCharacter.$this->tablePrefix.$table.$this->quoteCharacter;
	}
	function quote($v){
		if($v=='*')
			return $v;
		return $this->quoteCharacter.$this->unQuote($v).$this->quoteCharacter;
	}
	function unQuote($v){
		return trim($v,$this->quoteCharacter);
	}
	function prefixTable($table){
		$this->check($table);
		return $this->tablePrefix.$table;
	}
	function unprefixTable($table){
		if($this->tablePrefix&&substr($table,0,$l=strlen($this->tablePrefix))==$this->tablePrefix){
			$table = substr($table,$l);
		}
		return $table;
	}
	function unEsc($esc){
		return trim($esc,$this->quoteCharacter);
	}
	function getQuoteCharacter(){
		return $this->quoteCharacter;
	}
	function getTablePrefix(){
		return $this->tablePrefix;
	}
	function tableExists($table,$prefix=true){
		if($prefix)
			$table = $this->prefixTable($table);
		return in_array($table, $this->getTables());
	}
	static function startsWithZeros($value){
		$value = strval($value);
		return strlen($value)>1&&strpos($value,'0')===0&&strpos($value,'0.')!==0;
	}
	
	protected static function makeFKLabel($from, $type, $to){
		return 'from_'.$from.'_to_table_'.$type.'_col_'.$to;
	}
	
	protected function getForeignKeyForTypeProperty( $type, $property ){
		$property = $this->check($property);
		try{
			$map = $this->getKeyMapForType($type);
		}
		catch(PDOException $e){
			return null;
		}
		foreach($map as $key){
			if($key['from']===$property)
				return $key;
		}
		return null;
	}

	function getTables(){
		if(!isset($this->cacheTables))
			$this->cacheTables = $this->getTablesQuery();
		return $this->cacheTables;
	}
	function columnExists($table,$column){
		return $this->tableExists($table)&&in_array($column,array_keys($this->getColumns($table)));
	}
	function getColumnNames($type){
		if(!$this->tableExists($type)) return [];
		return array_keys($this->getColumns($type));
	}
	function getColumns($type){
		if(!isset($this->cacheColumns[$type]))
			$this->cacheColumns[$type] = $this->getColumnsQuery($type);
		return $this->cacheColumns[$type];
	}
	function addColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type])){
			if(is_integer($field)){
				$this->cacheColumns[$type][$column] = (false!==$i=array_search($field,$this->sqltype_typeno))?$i:'';
			}
			else{
				$snip = explode(' ',$field);
				$this->cacheColumns[$type][$column] = $snip;
			}
		}
		$this->addColumnQuery($type,$column,$field);
		$this->triggerTableWrapper('onAddColumn',$type,[$column]);
	}
	function changeColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type])){
			if(is_integer($field)){
				$this->cacheColumns[$type][$column] = (false!==$i=array_search($field,$this->sqltype_typeno))?$i:'';
			}
			else{
				$snip = explode(' ',$field);
				$this->cacheColumns[$type][$column] = $snip;
			}
		}
		$this->changeColumnQuery($type,$column,$field);
		$this->triggerTableWrapper('onChangeColumn',$type,[$column]);
	}
	function removeColumn($type,$column){
		$this->removeColumnQuery($type,$column);
		if(isset($this->cacheColumns[$type][$column])){
			unset($this->cacheColumns[$type][$column]);
		}
		$this->triggerTableWrapper('onRemoveColumn',$type,[$column]);
	}
	
	function createTable($type,$pk='id'){
		$table = $this->prefixTable($type);
		if(!in_array($table,$this->cacheTables))
			$this->cacheTables[] = $table;
		$this->createTableQuery($type,$pk);
		$this->triggerTableWrapper('onCreateTable',$type,[$pk]);
	}
	function drops(){
		foreach(func_get_args() as $drop){
			if(is_array($drop)){
				foreach($drop as $d){
					$this->drop($d);
				}
			}
			else{
				$this->drop($drop);
			}
		}
	}
	function drop($t){
		if(isset($this->cacheTables)&&($i=array_search($t,$this->cacheTables))!==false)
			unset($this->cacheTables[$i]);
		if(isset($this->cacheColumns[$t]))
			unset($this->cacheColumns[$t]);
		$this->_drop($t);
	}
	function dropAll(){
		$this->_dropAll();
		$this->cacheTables = [];
		$this->cacheColumns = [];
	}
	
	function many2one($obj,$type){
		$table = clone $this[$type];
		$typeE = $this->escTable($type);
		$pk = $table->getPrimaryKey();
		$pko = $type.'_'.$pk;
		$column = $this->esc($pk);
		$table->where($typeE.'.'.$column.' = ?',[$obj->$pko]);
		return $table->getRow();
	}
	function one2many($obj,$type){
		$table = clone $this[$type];
		$typeE = $this->escTable($type);
		$tb = $this->findEntityTable($obj);
		$pko = $this[$tb]->getPrimaryKey();
		$column = $this->esc($tb.'_'.$pko);
		$table->where($typeE.'.'.$column.' = ?',[$obj->$pko]);
		return $table;
	}
	function many2many($obj,$type2,$via=null){
		$type1 = $this->findEntityTable($obj);
		$pk1 = $this[$type1]->getPrimaryKey();
		$pk2 = $this[$type2]->getPrimaryKey();
		
		$t2 = $type1==$type2?'2':'';
		
		$type2E = $this->escTable($type2);
		$pk2E = $this->esc($pk2);
		
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = $this->many2manyTableName($type1,$type2);
		}
		$table = clone $this[$type2];
		$table->unFrom();
		$table->from($tbj);
		$table->join($type2E);
		
		$joinQuery = "( `$type2`.{$pk2E} = `$tbj`.`{$type2}{$t2}_{$pk2}` AND `$tbj`.`{$type1}_{$pk1}` = ? )";
		$joinParams = [$obj->$pk1];
		
		if($t2){
			$joinQuery .= "OR ( `$type2`.{$pk2E} = `$tbj`.`{$type2}_{$pk2}` AND `$tbj`.`{$type2}{$t2}_{$pk2}` = ? )";
			$joinParams[] = $obj->$pk1;
		}
		$table->joinOn($joinQuery,$joinParams);
		return $table;
	}
	function many2manyLink($obj,$type,$via=null,$viaFk=null){
		$tb = $this->findEntityTable($obj);
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = $this->many2manyTableName($type,$tb);
		}
		$table = clone $this[$tbj];
		$typeE = $this->escTable($type);
		$pk = $table->getPrimaryKey();
		$pko = $this[$tb]->getPrimaryKey();
		$typeColSuffix = $type==$tb?'2':'';
		$column1 = $viaFk?$this->esc($viaFk):$this->esc($type.$typeColSuffix.'_'.$pk);
		$column2 = $this->esc($tb.'_'.$pko);
		$tb = $this->escTable($tb);
		$tbj = $this->escTable($tbj);
		$pke = $this->esc($pk);
		$pkoe = $this->esc($pko);
		$table->join($typeE);
		$table->joinOn($tbj.'.'.$column1.' = '.$typeE.'.'.$pke);
		$table->join($tb);
		$table->joinOn($tb.'.'.$pkoe.' = '.$tbj.'.'.$column2
					.' AND '.$tb.'.'.$pkoe.' =  ?',[$obj->$pko]);
		$table->select($tbj.'.*');
		return $table;
	}
	function joinCascade($type, $map=[]){
		return $this[$type]->joinCascade($map);
	}
	function many3rd($obj1,$obj2,$type3,$via=null,$viaFk=null){
		$type1 = $this->findEntityTable($obj1);
		$type2 = $this->findEntityTable($obj2);
		if(!$via){
			$via = $this->many2manyTableName($type3,$type1,$type2);
		}
		$table = clone $this[$type3];
		$type1e = $this->escTable($type1);
		$type2e = $this->escTable($type2);
		$type3e = $this->escTable($type3);
		$viaE = $this->escTable($via);
		$pk1 = $this[$type1]->getPrimaryKey();
		$pk2 = $this[$type2]->getPrimaryKey();
		$pk3 = $table->getPrimaryKey();
		$typeColSuffix = $type1==$type2?'2':'';
		$column1 = $this->esc($type1.$typeColSuffix.'_'.$pk1);
		$column2 = $this->esc($type2.'_'.$pk2);
		$column3 = $viaFk?$this->esc($viaFk):$this->esc($type3.'_'.$pk3);
		$pk1e = $this->esc($pk1);
		$pk2e = $this->esc($pk2);
		$pk3e = $this->esc($pk3);
		$table->join($viaE.' ON '.$viaE.'.'.$column3.' = '.$type3e.'.'.$pk3e);
		$table->join($type1e.' ON '.$type1e.'.'.$pk1e.' = '.$viaE.'.'.$column1.' AND '.$type1e.'.'.$pk1e.' =  ?',[$obj1->$pk1]);
		$table->join($type2e.' ON '.$viaE.'.'.$column2.' = '.$type2e.'.'.$pk2e.' AND '.$type2e.'.'.$pk2e.' = ?',[$obj2->$pk2]);
		return $table;
	}
	
	function one2manyDeleteAll($obj,$type,$except=[]){
		if(!$this->tableExists($type))
			return;
		$typeE = $this->escTable($type);
		$tb = $this->findEntityTable($obj);
		$pko = $this[$tb]->getPrimaryKey();
		$column = $this->esc($tb.'_'.$pko);
		$notIn = '';
		$params = [$obj->$pko];
		if(!empty($except)){
			$notIn = ' AND '.$pko.' NOT IN ?';
			$params[] = $except;
		}
		$this->execute('DELETE FROM '.$typeE.' WHERE '.$column.' = ?'.$notIn,$params);
	}
	function deleteMany($tableParent,$table,$id){
		$typeE = $this->escTable($table);
		$pko = $this[$tableParent]->getPrimaryKey();
		$column = $this->esc($tableParent.'_'.$pko);
		$pk = $this[$table]->getPrimaryKey();
		$this->execute('DELETE FROM '.$typeE.' WHERE '.$column.' = ?',[$id]);
	}
	function many2manyDeleteAll($obj,$type,$via=null,$except=[],$viaFk=null){
		//work in pgsql,sqlite,cubrid but not in mysql (overloaded in Mysql.php)
		$tb = $this->findEntityTable($obj);
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = $this->many2manyTableName($type,$tb);
		}
		if(!$this->tableExists($tbj))
			return;
		$typeE = $this->escTable($type);
		$pk = $this[$tbj]->getPrimaryKey();
		$pko = $this[$tb]->getPrimaryKey();
		$typeColSuffix = $type==$tb?'2':'';
		$column1 = $viaFk?$this->esc($viaFk):$this->esc($type.$typeColSuffix.'_'.$pk);
		$column2 = $this->esc($tb.'_'.$pko);
		$tb = $this->escTable($tb);
		$tbj = $this->escTable($tbj);
		$pke = $this->esc($pk);
		$pkoe = $this->esc($pko);
		$notIn = '';
		$params = [$obj->$pko];
		if(!empty($except)){
			$notIn = ' AND '.$tbj.'.'.$pke.' NOT IN ?';
			$params[] = $except;
		}
		$this->execute('DELETE FROM '.$tbj.' WHERE '.$tbj.'.'.$pke.' IN(
			SELECT '.$tbj.'.'.$pke.' FROM '.$tbj.'
			JOIN '.$tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$column2.'
			JOIN '.$typeE.' ON '.$tbj.'.'.$column1.' = '.$typeE.'.'.$pke.'
			AND '.$tb.'.'.$pkoe.' = ? '.$notIn.'
		)',$params);
	}
	
	function getFtsTableSuffix(){
		return $this->ftsTableSuffix;
	}
	
	function getAgg(){
		return $this->agg;
	}
	function getAggCaster(){
		return $this->aggCaster;
	}
	function getSeparator(){
		return $this->separator;
	}
	function getConcatenator(){
		return $this->concatenator;
	}
	
	function explodeAgg($data,$type=null){
		$_gs = chr(0x1D);
		$row = [];
		foreach(array_keys($data) as $col){
			if(stripos($col,'<')||stripos($col,'>')){
				$sep = stripos($col,'<>')?'<>':(stripos($col,'<')?'<':'>');
				$x = explode($sep,$col);
				$tb = &$x[0];
				$_col = &$x[1];
				if(!isset($row[$tb]))
					$row[$tb] = [];
				if(empty($data[$col])){
					if(!isset($row[$tb]))
						$row[$tb] = $this->entityFactory($tb);
				}
				else{
					$_x = explode($_gs,$data[$col]);
					$pk = $this[$tb]->getPrimaryKey();
					if(isset($data[$tb.$sep.$pk])){
						$_idx = explode($_gs,$data[$tb.$sep.$pk]);
						foreach($_idx as $_i=>$_id){
							if(!isset($row[$tb][$_id]))
								$row[$tb][$_id] = $this->entityFactory($tb);
							$row[$tb][$_id]->$_col = $_x[$_i];
						}
					}
					else{
						foreach($_x as $_i=>$v){
							if(!isset($row[$tb][$_i]))
								$row[$tb][$_i] = $this->entityFactory($tb);
							$row[$tb][$_i]->$_col = $v;
						}
					}
				}
			}
			else{
				$row[$col] = $data[$col];
			}
		}
		if($type)
			$row = $this->arrayToEntity($row,$type);
		return $row;
	}
	function explodeAggTable($data,$type=null){
		$table = [];
		if(is_array($data)||$data instanceof \ArrayAccess)
			foreach($data as $i=>$d){
				$pk = $type?$this[$type]->getPrimaryKey():$this->getPrimaryKey();
				$id = isset($d[$pk])?$d[$pk]:$i;
				$table[$id] = $this->explodeAgg($d,$type);
			}
		return $table;
	}
	
	function findRow($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$table = $this->escTable($type);
		$select = $this->getSelectSnippet($type);
		$sql = "SELECT {$select} FROM {$table} {$snip} LIMIT 1";
		return $this->getRow($sql,$bindings);
	}
	function findOne($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$obj = $this->entityFactory($type);
		if($obj instanceof StateFollower)
			$obj->__readingState(true);
		$this->trigger($type,'beforeRead',$obj);
		
		$snip = 'WHERE '.$snip;
		$row = $this->findRow($type,$snip,$bindings);
		
		if($row){
			foreach($row as $k=>$v)
				$obj->$k = $v;
		}
		$this->trigger($type,'afterRead',$obj);
		$this->trigger($type,'unserializeColumns',$obj);
		if($obj instanceof StateFollower)
			$obj->__readingState(false);
		if($row)
			return $obj;
	}
	
	function findRows($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$table = $this->escTable($type);
		$select = $this->getSelectSnippet($type);
		$sql = "SELECT {$select} FROM {$table} {$snip}";
		return $this->getAll($sql,$bindings);
	}
	function findAll($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$rows = $this->findRows($type,$snip,$bindings);
		$all = [];
		foreach($rows as $row){
			$obj = $this->entityFactory($type);
			if($obj instanceof StateFollower)
				$obj->__readingState(true);
			$this->trigger($type,'beforeRead',$obj);
			foreach($row as $k=>$v){
				$obj->$k = $v;
			}
			$this->trigger($type,'afterRead',$obj);
			$this->trigger($type,'unserializeColumns',$obj);
			if($obj instanceof StateFollower)
				$obj->__readingState(false);
			$all[] = $obj;
		}
		return $all;
	}
	function find($type,$snip,$bindings=[]){
		return $this->findAll($type,'WHERE '.$snip,$bindings);
	}
	
	function execMultiline($sql,$bindings=[]){
		$this->connect();
		$this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
		$r = $this->execute($sql, $bindings);
		$this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		return $r;
	}
	
	function findOrNewOne($type,$params=[]){
		$query = [];
		$bind = [];
		foreach($params as $k=>$v){
			if($v===null)
				$query[] = $k.' IS ?';
			else
				$query[] = $k.'=?';
			$bind[] = $v;
		}
		$query = implode(' AND ',$query);
		$type = (array)$type;
		foreach($type as $t){
			if($row = $this->findOne($t,$query,$bind))
				break;
		}
		if(!$row){
			$row = $this->arrayToEntity($params,array_pop($type));
		}
		return $row;
	}
	
	function getTablesNames(){
		$tablesWithPrefix = $this->getTables();
		if(!$this->tablePrefix)
			return $tablesWithPrefix;
		$l = strlen($this->tablePrefix);
		$tables = [];
		foreach($tablesWithPrefix as $t){
			if(substr($t,0,$l)==$this->tablePrefix)
				$tables[] = substr($t,$l);
		}
		return $tables;
	}
	
	function rewind(){
		$this->tablesList = $this->getTablesNames();
	}
	
	function has($structure){
		foreach($structure as $table=>$column){
			if(!$this->tableExists($table))
				return false;
			foreach((array)$column as $col){
				if(!$this->columnExists($table,$col)){
					return false;
				}
			}
		}
		return true;
	}
	
	function getTablesQuery(){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_getTablesQuery();
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function getColumnsQuery($table){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_getColumnsQuery($table);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function createTableQuery($table,$pk='id'){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_createTableQuery($table,$pk);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function addColumnQuery($type,$column,$field){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_addColumnQuery($type,$column,$field);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function changeColumnQuery($type,$property,$dataType){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_changeColumnQuery($type,$property,$dataType);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function removeColumnQuery($type,$column){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_removeColumnQuery($type,$column);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function addFK($type,$targetType,$property,$targetProperty,$isDep){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_addFK($type,$targetType,$property,$targetProperty,$isDep);
		if($r&&isset($this->cacheFk[$type])){
			unset($this->cacheFk[$type]);
		}
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function getKeyMapForType($type, $reload=false){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		if(!isset($this->cacheFk[$type]) || $reload){
			$this->cacheFk[$type] = $this->_getKeyMapForType($type);
		}
		$this->performingSystemQuery = $tmp;
		return $this->cacheFk[$type];
	}
	function getUniqueConstraints($type,$prefix=true){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_getUniqueConstraints($type,$prefix);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function addUniqueConstraint($type,$properties){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_addUniqueConstraint($type,$properties);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	function addIndex($type,$property,$name=null){
		$tmp = $this->performingSystemQuery;
		$this->performingSystemQuery = true;
		$r = $this->_addIndex($type,$property,$name);
		$this->performingSystemQuery = $tmp;
		return $r;
	}
	protected function queryException($message,$q,$p=[]){
		$e = new QueryException($message);
		$e->setDB($this);
		$e->setQuery($q);
		$e->setParams($p);
		return $e;
	}
	protected function schemaException($message){
		$e = new SchemaException($message);
		$e->setDB($this);
		return $e;
	}
	
	abstract protected function _getTablesQuery();
	abstract protected function _getColumnsQuery($table);
	abstract protected function _createTableQuery($table,$pk='id');
	abstract protected function _addColumnQuery($type,$column,$field);
	abstract protected function _changeColumnQuery($type,$property,$dataType);
	abstract protected function _removeColumnQuery($type,$column);
	abstract protected function _addFK($type,$targetType,$property,$targetProperty,$isDep);
	abstract protected function _getKeyMapForType($type);
	abstract protected function _getUniqueConstraints($type,$prefix=true);
	abstract protected function _addUniqueConstraint($type,$properties);
	abstract protected function _addIndex($type,$property,$name);
	
	abstract function scanType($value,$flagSpecial=false);
	abstract function columnCode($typedescription,$includeSpecials);
	abstract function getTypeForID();
	
	abstract function clear($type);
	abstract protected function _drop($type);
	abstract protected function _dropAll();
	
	abstract protected function explain($sql,$bindings=[]);
}
}
#DataSource/Mysql.php

namespace FoxORM\DataSource {
class Mysql extends SQL{
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT32           = 1;
	const C_DATATYPE_UBIGINT          = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT7            = 4; //InnoDB cant index varchar(255) utf8mb4 - so keep 191 as long as possible
	const C_DATATYPE_TEXT8            = 5;
	const C_DATATYPE_TEXT16           = 6;
	const C_DATATYPE_TEXT32           = 7;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LINESTRING = 91;
	const C_DATATYPE_SPECIAL_POLYGON    = 92;
	const C_DATATYPE_SPECIFIED          = 99;
	protected $unknownDatabaseCode = 1049;
	protected $quoteCharacter = '`';
	protected $isMariaDB;
	protected $version;
	
	protected $separator = 'SEPARATOR';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $concatenator = '0x1D';
	
	protected $fluidPDO;
	protected $updateOnDuplicateKey;
	
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_BOOL             => ' TINYINT(1) UNSIGNED ',
			self::C_DATATYPE_UINT32           => ' INT(11) UNSIGNED ',
			self::C_DATATYPE_UBIGINT          => ' BIGINT(20) UNSIGNED ',
			self::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			self::C_DATATYPE_TEXT7            => ' VARCHAR(191) ',
			self::C_DATATYPE_TEXT8            => ' VARCHAR(255) ',
			self::C_DATATYPE_TEXT16           => ' TEXT ',
			self::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			self::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			self::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
			self::C_DATATYPE_SPECIAL_LINESTRING => ' LINESTRING ',
			self::C_DATATYPE_SPECIAL_POLYGON => ' POLYGON ',
		];
		foreach($this->typeno_sqltype as $k=>$v){
			$this->sqltype_typeno[trim(strtolower($v))] = $k;
		}
	}
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		$serverVersion = $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
		$this->isMariaDB = strpos($serverVersion,'MariaDB')!==false;
		if($this->isMariaDB)
			$this->version = substr($serverVersion,0,strpos($serverVersion,'-'));
		else
			$this->version = floatval($serverVersion);
		if(!$this->isMariaDB&&$this->version>=5.5)
			$this->encoding =  'utf8mb4';
		$this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$this->encoding); //on every re-connect
		$this->pdo->exec('SET NAMES '. $this->encoding); //also for current connection
	}
	function createDatabase($dbname){
		$this->pdo->exec('CREATE DATABASE `'.$dbname.'` COLLATE \'utf8_bin\'');
	}
	function scanType($value,$flagSpecial=false){
		if(is_null( $value ))
			return self::C_DATATYPE_BOOL;
		if($value === INF)
			return self::C_DATATYPE_TEXT7;
		if($flagSpecial){
			if(preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if(preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
			if(preg_match( '/^POINT\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_POINT;
			if(preg_match( '/^LINESTRING\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_LINESTRING;
			if(preg_match( '/^POLYGON\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_POLYGON;
		}
		//setter turns TRUE FALSE into 0 and 1 because database has no real bools (TRUE and FALSE only for test?).
		if( $value === FALSE || $value === TRUE || $value === '0' || $value === '1' )
			return self::C_DATATYPE_BOOL;
		if( !$this->startsWithZeros( $value ) ) {
			if( is_numeric( $value ) && floor($value)==$value && $value>=0 && $value <= 4294967295 )
				return self::C_DATATYPE_UINT32;
			elseif ( is_numeric( $value ) && floor($value)==$value && $value>0 && $value <= 18446744073709551615 )
				return self::C_DATATYPE_UBIGINT;
			if( is_numeric( $value ) )
				return self::C_DATATYPE_DOUBLE;
		}
		if( is_float( $value ) )
			return self::C_DATATYPE_DOUBLE;
		if( mb_strlen( $value, 'UTF-8' ) <= 191 )
			return self::C_DATATYPE_TEXT7;
		if( mb_strlen( $value, 'UTF-8' ) <= 255 )
			return self::C_DATATYPE_TEXT8;
		if( mb_strlen( $value, 'UTF-8' ) <= 65535 )
			return self::C_DATATYPE_TEXT16;
		return self::C_DATATYPE_TEXT32;
	}
	protected function _getTablesQuery(){
		return $this->getCol('SHOW TABLES');
	}
	protected function _getColumnsQuery($type){
		$columns = [];
		foreach($this->getAll('DESCRIBE '.$this->escTable($type)) as $r)
			$columns[$r['Field']] = $r['Type'];
		return $columns;
	}
	
	function getFluidPDO(){
		if(!isset($this->fluidPDO)){
			$this->fluidPDO = new \PDO($this->dsn,$this->connectUser,$this->connectPass);
			$this->fluidPDO->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, TRUE );
			$this->fluidPDO->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
			$this->fluidPDO->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
			if(!empty($this->options)) foreach($this->options as $opt=>$attr) $this->fluidPDO->setAttribute($opt,$attr);
		}
		return $this->fluidPDO;
	}
	function executeFluid($sql,$bindings=[]){
		$pdo = $this->pdo;
		$this->pdo = $this->getFluidPDO();
		$this->execute($sql,$bindings);
		$this->pdo = $pdo;
	}
	
	protected function _createTableQuery($table,$pk='id'){
		$table = $this->escTable($table);
		$pk = $this->esc($pk);
		$encoding = $this->getEncoding();
		$this->executeFluid('CREATE TABLE '.$table.' ('.$pk.' INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( '.$pk.' )) ENGINE = InnoDB DEFAULT CHARSET='.$encoding.' COLLATE='.$encoding.'_unicode_ci ');
	}
	protected function _addColumnQuery($type,$column,$field){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable($table);
		$column = $this->esc($column);
		if(is_integer($type))
			$type = isset($this->typeno_sqltype[$type])?$this->typeno_sqltype[$type]:'';
		$this->executeFluid('ALTER TABLE '.$table.' ADD '.$column.' '.$type);
	}
	protected function _changeColumnQuery($type,$property,$dataType ){
		$table   = $this->escTable( $type );
		$column  = $this->esc( $property );
		if(is_integer($dataType)){
			if(!isset($this->typeno_sqltype[$dataType]))
				return false;
			$dataType = $this->typeno_sqltype[$dataType];
		}
		$this->executeFluid('ALTER TABLE '.$table.' CHANGE '.$column.' '.$column.' '.$dataType);
	}
	protected function _removeColumnQuery($type,$column){
		$table  = $this->escTable($type);
		$column = $this->esc($column);
		$this->executeFluid('ALTER TABLE '.$table.' DROP COLUMN '.$column);
	}
	
	protected function _addFK( $type, $targetType, $property, $targetProperty, $isDependent = false ){
		$table = $this->escTable( $type );
		$targetTable = $this->escTable( $targetType );
		$field = $this->esc( $property );
		$fieldNoQ = $this->check( $property);
		$targetField = $this->esc( $targetProperty );
		$tableNoQ = $this->prefixTable( $type );
		$fieldNoQ = $this->check( $property);
		$casc = ( $isDependent ? 'CASCADE' : 'SET NULL' );
		$fk = $this->getForeignKeyForTypeProperty( $type, $fieldNoQ );
		if ( !is_null( $fk )
			&&($fk['on_update']==$casc||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$casc||$fk['on_delete']=='CASCADE')
		)
			return false;

		//Widen the column if it's incapable of representing a foreign key (at least INT).
		$columns = $this->getColumns( $type );
		$idType = $this->getTypeForID();
		if ( $this->columnCode( $columns[$fieldNoQ] ) !==  $idType ) {
			$this->changeColumn( $type, $property, $idType );
		}

		$fkName = 'fk_'.$tableNoQ.'_'.$fieldNoQ;
		$cName = 'c_'.$fkName;
		try {
			$this->executeFluid( "
				ALTER TABLE {$table}
				ADD CONSTRAINT $cName
				FOREIGN KEY $fkName ( {$field} ) REFERENCES {$targetTable}
				({$targetField}) ON DELETE " . $casc . ' ON UPDATE '.$casc.';');
		} catch ( \PDOException $e ) {
			// Failure of fk-constraints is not a problem
		}
		return true;
	}
	protected function _getKeyMapForType($type){
		$table = $this->prefixTable( $type );
		$keys = $this->getAll('
			SELECT
				information_schema.key_column_usage.constraint_name AS `name`,
				information_schema.key_column_usage.referenced_table_name AS `table`,
				information_schema.key_column_usage.column_name AS `from`,
				information_schema.key_column_usage.referenced_column_name AS `to`,
				information_schema.referential_constraints.update_rule AS `on_update`,
				information_schema.referential_constraints.delete_rule AS `on_delete`
				FROM information_schema.key_column_usage
				INNER JOIN information_schema.referential_constraints
					ON (
						information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
						AND information_schema.referential_constraints.constraint_schema = information_schema.key_column_usage.constraint_schema
						AND information_schema.referential_constraints.constraint_catalog = information_schema.key_column_usage.constraint_catalog
					)
			WHERE
				information_schema.key_column_usage.table_schema IN ( SELECT DATABASE() )
				AND information_schema.key_column_usage.table_name = ?
				AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
				AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
		', [$table]);
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}
	function columnCode($typedescription, $includeSpecials = FALSE ){
		$typedescription = strtolower($typedescription);
		if ( isset( $this->sqltype_typeno[$typedescription] ) )
			$r = $this->sqltype_typeno[$typedescription];
		else
			$r = self::C_DATATYPE_SPECIFIED;
		if ( $includeSpecials )
			return $r;
		if ( $r >= self::C_DATATYPE_RANGE_SPECIAL )
			return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	function getTypeForID(){
		return self::C_DATATYPE_UINT32;
	}
	protected function _addUniqueConstraint( $type, $properties ){
		$columns = [];
		foreach( (array)$properties as $key => $column )
			$columns[$key] = $this->esc( $column );
		$table = $this->escTable( $type );
		sort($columns);
		$name = 'uq_' . sha1( implode( ',', $columns ) );
		$indexMap = $this->getRow('SHOW indexes FROM '.$table.' WHERE Key_name = ?',[$name]);
		if(is_null($indexMap))
			$this->executeFluid("ALTER TABLE $table ADD UNIQUE INDEX `$name` (" . implode( ',', $columns ) . ")");
	}
	protected function _getUniqueConstraints($type,$prefix=true){
		$table = $this->escTable($type,$prefix);
		$indexes = [];
		$indexMap = $this->getAll("SHOW INDEX FROM $table WHERE non_unique = 0 AND Key_name != 'PRIMARY'");
		foreach($indexMap as $v){
			$indexes[$v['Key_name']][$v['Seq_in_index']] = $v['Column_name'];
		}
		foreach($indexes as &$v){
			ksort($v);
			$v = array_values($v);
		}
		return array_values($indexes);
	}
	protected function _addIndex( $type, $property, $name=null ){
		if(!$name) $name = 'index_'.$property;
		try {
			$table  = $this->escTable( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->esc( $property );
			$this->executeFluid("CREATE INDEX $name ON $table ($column) ");
			return true;
		}
		catch( \PDOException $e ){
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('TRUNCATE '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		$this->execute('SET FOREIGN_KEY_CHECKS = 0;');
		try{
			$this->execute('DROP TABLE IF EXISTS '.$t);
		}
		catch(\PDOException $e){}
		try{
			$this->execute('DROP VIEW IF EXISTS '.$t);
		}
		catch(\PDOException $e){}
		$this->execute('SET FOREIGN_KEY_CHECKS = 1;');
	}
	protected function _dropAll(){
		$this->execute('SET FOREIGN_KEY_CHECKS = 0;');
		foreach($this->getTables() as $t){
			try{
				$this->execute("DROP TABLE IF EXISTS `$t`");
			}
			catch(\PDOException $e){}
			try{
				$this->execute("DROP VIEW IF EXISTS `$t`");
			}
			catch(\PDOException $e){}
		}
		$this->execute('SET FOREIGN_KEY_CHECKS = 1;');
	}
	
	protected function explain($sql,$bindings=[]){
		$sql = ltrim($sql);
		if(!in_array(strtoupper(substr($sql,0,6)),['SELECT','DELETE','INSERT','UPDATE'])
			&&strtoupper(substr($sql,0,7))!='REPLACE')
			return false;
		$explain = $this->pdo->prepare('EXPLAIN EXTENDED '.$sql);
		$this->bindParams($explain,$bindings);
		$explain->execute();
		$explain = $explain->fetchAll();
		$i = 0;
		return implode("\n",array_map(function($entry)use(&$i){
			$indent = str_repeat('  ',$i);
			$s = '';
			if(isset($entry['id']))
				$s .= $indent.$entry['id'].'|';
			foreach($entry as $k=>$v){
				if($k!='id'&&$k!='Extra'&&!is_null($v))
					$s .= $indent.$k.':'.$v.'|';
			}
			if(isset($entry['Extra']))
				$s .= $indent.$entry['Extra'];
			else
				$s = rtrim($s,'|');
			$i++;
			return $s;
		}, $explain));
	}
	
	function getFkMap($type,$primaryKey='id'){
		$table = $this->prefixTable($type);
		$dbname = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
		$this->pdo->exec('use INFORMATION_SCHEMA');
		$fks = $this->getAll('SELECT table_name AS "table",column_name AS "column",constraint_name AS "constraint" FROM key_column_usage WHERE table_schema = "'.$dbname.'" AND referenced_table_name = "'.$table.'" AND referenced_column_name = "'.$primaryKey.'";');
		$this->pdo->exec('use '.$dbname);
		foreach($fks as &$fk){
			$constraint = $this->getRow('
				SELECT
					information_schema.referential_constraints.update_rule AS `on_update`,
					information_schema.referential_constraints.delete_rule AS `on_delete`
					FROM information_schema.key_column_usage
					INNER JOIN information_schema.referential_constraints
						ON (
							information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
							AND information_schema.referential_constraints.constraint_schema = information_schema.key_column_usage.constraint_schema
							AND information_schema.referential_constraints.constraint_catalog = information_schema.key_column_usage.constraint_catalog
						)
				WHERE
					information_schema.key_column_usage.table_schema IN ( SELECT DATABASE() )
					AND information_schema.key_column_usage.table_name = ?
					AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
					AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
					AND information_schema.key_column_usage.constraint_name = ?
			',[$this->prefixTable($fk['table']),$fk['constraint']]);
			$fk['on_update'] = $constraint['on_update'];
			$fk['on_delete'] = $constraint['on_delete'];
		}
		return $fks;
	}
	
	function adaptPrimaryKey($type,$id,$primaryKey='id'){
		if($id!=4294967295)
			return;
		$cols = $this->getColumns($type);
		if($cols[$primaryKey]=='bigint(20) unsigned')
			return;
		$table = $this->escTable($type);
		$pk = $this->esc($primaryKey);
		$fks = $this->getFkMap($type,$primaryKey);
		$lockTables = 'LOCK TABLES '.$table.' WRITE';
		foreach($fks as $fk){
			$lockTables .= ',`'.$fk['table'].'` WRITE';
		}
		$this->execute($lockTables);
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['constraint'].'`, MODIFY `'.$fk['column'].'` bigint(20) unsigned NULL');
		}
		$this->execute('ALTER TABLE '.$table.' CHANGE '.$pk.' '.$pk.' bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` ADD FOREIGN KEY (`'.$fk['column'].'`) REFERENCES '.$table.' ('.$pk.') ON DELETE '.$fk['on_delete'].' ON UPDATE '.$fk['on_update']);
		}
		$this->execute('UNLOCK TABLES');
		if($this->tableExists($type.$this->ftsTableSuffix))
			$this->execute('ALTER TABLE '.$this->escTable($type.$this->ftsTableSuffix).' CHANGE '.$pk.' '.$pk.' bigint(20) unsigned NOT NULL AUTO_INCREMENT');
	}
	
	function fulltextAvailableOnInnoDB(){
		$this->connect();
		if($this->isMariaDB)
			return version_compare($this->version,'10.0.5','>=');
		else
			return $this->version>=5.6;
	}
	
	function getFtsMap($type){
		$table = $this->prefixTable($type);
		$all = $this->getAll("SELECT GROUP_CONCAT(DISTINCT column_name) AS columns, INDEX_NAME AS name FROM information_schema.STATISTICS WHERE table_schema = (SELECT DATABASE()) AND table_name = '$table' AND index_type = 'FULLTEXT'");
		$map = [];
		foreach($all as $index){
			$col = explode(',',$index['columns']);
			sort($col);
			$map[$index['name']] = $col;
		}
		return $map;
	}
	function autoFillTextColumns($type,$uniqTextKey){
		$sufxL = -1*strlen($this->ftsTableSuffix);
		$columns = [];
		foreach($this->getColumns($type) as $col=>$colType){
			if((strtolower(substr($colType,0,7))=='varchar'||strtolower($colType)=='text'||strtolower($colType=='longtext'))
				&&($col==$uniqTextKey||substr($col,$sufxL)==$this->ftsTableSuffix))
				$columns[] = $col;
		}
		return $columns;
	}
	function addFtsIndex($type,&$columns=[],$primaryKey='id',$uniqTextKey='uniq'){
		$table = $this->escTable($type);
		$ftsMap = $this->getFtsMap($type);
		if(empty($columns)){
			$columns = $this->autoFillTextColumns($type,$uniqTextKey);
			if(empty($columns))
				throw $this->schemaException('Unable to find columns from "'.$table.'" to create FTS table for "'.$type.'"');
			$indexName = '_auto';
			sort($columns);
			if(isset($ftsMap[$indexName])&&$ftsMap[$indexName]!==$columns){
				$this->execute('ALTER TABLE '.$table.' DROP INDEX `'.$indexName.'`');
				unset($ftsMap[$indexName]);
			}
		}
		else{
			sort($columns);
			$indexName = implode('_',$columns);
		}
		if(!in_array($columns,$ftsMap))
			$this->execute('ALTER TABLE '.$table.' ADD FULLTEXT `'.$indexName.'` (`'.implode('`,`',$columns).'`)');
	}
	function makeFtsTableAndIndex($type,&$columns=[],$primaryKey='id',$uniqTextKey='uniq'){
		$table = $this->escTable($type);
		$ftsType = $type.$this->ftsTableSuffix;
		$ftsTable = $this->escTable($ftsType);
		$ftsMap = $this->getFtsMap($ftsType);
		if(empty($columns)){
			$columns = $this->autoFillTextColumns($type,$uniqTextKey);
			if(empty($columns))
				throw $this->schemaException('Unable to find columns from "'.$table.'" to create FTS table "'.$ftsTable.'"');
			$indexName = '_auto';
			sort($columns);
			if(isset($ftsMap[$indexName])&&$ftsMap[$indexName]!==$columns){
				$this->execute('ALTER TABLE '.$ftsTable.' DROP INDEX `'.$indexName.'`');
				unset($ftsMap[$indexName]);
			}
		}
		else{
			sort($columns);
			$indexName = implode('_',$columns);
		}
		$pTable = $this->prefixTable($type);
		$exist = $this->tableExists($ftsType);
		$makeColumns = $columns;
		if($exist){
			$oldColumns = array_keys($this->getColumns($ftsType));
			foreach($columns as $col){
				if(!in_array($col,$oldColumns)){
					$this->execute('DROP TABLE '.$ftsTable);
					foreach($oldColumns as $col){
						if(!in_array($col,$makeColumns))
							$makeColumns[] = $col;
					}
					$exist = false;
					break;
				}
			}
		}
		if(!$exist){
			$pk = $this->esc($primaryKey);
			$cols = '`'.implode('`,`',$columns).'`';
			$newCols = 'NEW.`'.implode('`,NEW.`',$columns).'`';
			$setCols = '';
			foreach($columns as $col){
				$setCols .= '`'.$col.'`=NEW.`'.$col.'`,';
			}
			$setCols = rtrim($setCols,',');
			$encoding = $this->getEncoding();
			$colsDef = implode(' TEXT NULL,',$columns).' TEXT NULL';
			$this->execute('CREATE TABLE '.$ftsTable.' ('.$pk.' INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '.$colsDef.' ) ENGINE = MyISAM DEFAULT CHARSET='.$encoding.' COLLATE='.$encoding.'_unicode_ci ');
			try{
				$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_insert');
				$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_update');
				$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_delete');
				$this->execute("CREATE TRIGGER {$pTable}_insert AFTER INSERT ON {$table} FOR EACH ROW INSERT INTO {$ftsTable}({$pk}, {$cols}) VALUES(NEW.{$pk}, {$newCols})");
				$this->execute("CREATE TRIGGER {$pTable}_update AFTER UPDATE ON {$table} FOR EACH ROW UPDATE {$ftsTable} SET {$setCols} WHERE {$pk}=OLD.{$pk}");
				$this->execute("CREATE TRIGGER {$pTable}_delete AFTER DELETE ON {$table} FOR EACH ROW DELETE FROM {$ftsTable} WHERE {$pk}=OLD.{$pk};");
				$this->execute('INSERT INTO '.$ftsTable.'('.$pk.','.$cols.') SELECT '.$pk.','.$cols.' FROM '.$table);
			}
			catch(\PDOException $e){
				if($this->debugLevel&self::DEBUG_ERROR){
					$code = $e->getCode();
					if(((string)(int)$code)!==((string)$code)&&isset($e->errorInfo)&&isset($e->errorInfo[1]))
						$code = $e->errorInfo[1];
					if((int)$code==1419){
						$this->logger->log("To fix this, in a shell, try: mysql -u USERNAME -p \nset global log_bin_trust_function_creators=1;");
					}
				}
				$this->execute('DROP TABLE '.$ftsTable);
				throw $e;
			}
		}
		if(!in_array($columns,$ftsMap))
			$this->execute('ALTER TABLE '.$ftsTable.' ADD FULLTEXT `'.$indexName.'` (`'.implode('`,`',$columns).'`)');
	}
	
	function many2manyDeleteAll($obj,$type,$via=null,$except=[],$viaFk=null){
		$tb = $this->findEntityTable($obj);
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = $this->many2manyTableName($type,$tb);
		}
		if(!$this->tableExists($tbj))
			return;
		$typeE = $this->escTable($type);
		$pk = $this[$tbj]->getPrimaryKey();
		$pko = $this[$tb]->getPrimaryKey();
		$typeColSuffix = $type==$tb?'2':'';
		$colmun1 = $viaFk?$this->esc($viaFk):$this->esc($type.$typeColSuffix.'_'.$pk);
		$colmun2 = $this->esc($tb.'_'.$pko);
		$tb = $this->escTable($tb);
		$tbj = $this->escTable($tbj);
		$pke = $this->esc($pk);
		$pkoe = $this->esc($pko);
		$notIn = '';
		$params = [$obj->$pko];
		if(!empty($except)){
			$notIn = ' AND '.$tbj.'.'.$pke.' NOT IN ?';
			$params[] = $except;
		}
		$this->execute('DELETE FROM '.$tbj.' USING('.$tbj.')
			JOIN '.$tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$colmun2.'
			JOIN '.$typeE.' ON '.$tbj.'.'.$colmun1.' = '.$typeE.'.'.$pke.'
			AND '.$tb.'.'.$pkoe.' = ? '.$notIn.'
		',$params);
	}
	
	function updateOnDuplicateKey(){
		if(func_num_args()){
			$this->updateOnDuplicateKey = (bool)func_get_arg(0);
		}
		return $this->updateOnDuplicateKey;
	}
	protected function createQueryExec($table,$pk,$insertcolumns,$id,$insertSlots,$suffix,$insertvalues){
		if(!$this->updateOnDuplicateKey){
			return $this->getCell('INSERT INTO '.$table.' ( '.$pk.', '.implode(',',$insertcolumns).' ) VALUES ( '.$id.', '. implode(',',$insertSlots).' ) ',$insertvalues);
		}
		else{
			$doubleParams = [];
			$up = [];
			foreach($insertcolumns as $i=>$col){
				$up[] = $col.' = '.$insertSlots[$i];
			}
			foreach($insertvalues as $v) $doubleParams[] = $v;
			foreach($insertvalues as $v) $doubleParams[] = $v;
			$insert = 'INSERT INTO '.$table.' ( '.$pk.', '.implode(',',$insertcolumns).' ) VALUES ( NULL, '. implode(',',$insertSlots).' ) ';
			$update = 'UPDATE '.$pk.'=LAST_INSERT_ID('.$pk.'), '.implode(',',$up);
			$query = $insert.' ON DUPLICATE KEY '.$update;
			return $this->getCell($query,$doubleParams);
		}
	}
}
}
#DataSource/Pgsql.php

namespace FoxORM\DataSource {
use FoxORM\Std\Cast;
class Pgsql extends SQL{
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_BIGINT           = 1;
	const C_DATATYPE_DOUBLE           = 2;
	const C_DATATYPE_TEXT             = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LSEG     = 91;
	const C_DATATYPE_SPECIAL_CIRCLE   = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;
	const C_DATATYPE_SPECIAL_POLYGON  = 94;
	const C_DATATYPE_SPECIFIED        = 99;
	protected $defaultValue = 'DEFAULT';
	protected $quoteCharacter = '"';
	protected $version;
	
	protected $separator = ',';
	protected $agg = 'string_agg';
	protected $aggCaster = '::text';
	protected $concatenator = 'chr(29)';
	
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER          => ' integer ',
			self::C_DATATYPE_BIGINT           => ' bigint ',
			self::C_DATATYPE_DOUBLE           => ' double precision ',
			self::C_DATATYPE_TEXT             => ' text ',
			self::C_DATATYPE_SPECIAL_DATE     => ' date ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' timestamp without time zone ',
			self::C_DATATYPE_SPECIAL_POINT    => ' point ',
			self::C_DATATYPE_SPECIAL_LSEG     => ' lseg ',
			self::C_DATATYPE_SPECIAL_CIRCLE   => ' circle ',
			self::C_DATATYPE_SPECIAL_MONEY    => ' money ',
			self::C_DATATYPE_SPECIAL_POLYGON  => ' polygon ',
		];
		$this->sqltype_typeno = [];
		foreach( $this->typeno_sqltype as $k => $v ){
			$this->sqltype_typeno[trim($v)] = $k;
		}
	}
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		$this->version = floatval($this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION));
		if($this->version<9){
			$this->separator = '),';
			$this->agg = 'array_to_string(array_agg';
		}
	}
	function createDatabase($dbname){
		$this->pdo->exec('CREATE DATABASE "'.$dbname.'"');
	}
	protected function getInsertSuffix( $primaryKey ){
		return 'RETURNING "'.$primaryKey.'" ';
	}
	protected function _getTablesQuery(){
		return $this->getCol( 'SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )' );
	}
	function scanType( $value, $flagSpecial = FALSE ){
		if ( $value === INF )
			return self::C_DATATYPE_TEXT;
		if ( $flagSpecial && $value ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
			if ( preg_match( '/^\([\d\.]+,[\d\.]+\)$/', $value ) )
				return self::C_DATATYPE_SPECIAL_POINT;
			if ( preg_match( '/^\[\([\d\.]+,[\d\.]+\),\([\d\.]+,[\d\.]+\)\]$/', $value ) )
				return self::C_DATATYPE_SPECIAL_LSEG;
			if ( preg_match( '/^\<\([\d\.]+,[\d\.]+\),[\d\.]+\>$/', $value ) )
				return self::C_DATATYPE_SPECIAL_CIRCLE;
			if ( preg_match( '/^\((\([\d\.]+,[\d\.]+\),?)+\)$/', $value ) )
				return self::C_DATATYPE_SPECIAL_POLYGON;
			if ( preg_match( '/^\-?(\$|€|¥|£)[\d,\.]+$/', $value ) )
				return self::C_DATATYPE_SPECIAL_MONEY;
		}
		if ( is_float( $value ) )
			return self::C_DATATYPE_DOUBLE;
		if ( self::startsWithZeros( $value ) )
			return self::C_DATATYPE_TEXT;
		if ( $value === FALSE || $value === TRUE || $value === NULL || ( is_numeric( $value )
				&& Cast::isInt( $value )
				&& $value <= 2147483647
				&& $value >= -2147483647 )
		)
			return self::C_DATATYPE_INTEGER;
		elseif ( is_numeric( $value )
				&& Cast::isInt( $value )
				&& $value <= 9223372036854775807
				&& $value >= -9223372036854775807 )
			return self::C_DATATYPE_BIGINT;
		elseif ( is_numeric( $value ) )
			return self::C_DATATYPE_DOUBLE;
		else
			return self::C_DATATYPE_TEXT;
	}
	protected function _getColumnsQuery($table){
		$table = $this->prefixTable($table);
		$columnsRaw = $this->getAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table'");
		$columns = [];
		foreach ( $columnsRaw as $r ) {
			$columns[$r['column_name']] = $r['data_type'];
		}
		return $columns;
	}
	protected function _createTableQuery($table,$pk='id'){
		$table = $this->escTable($table);
		$this->execute('CREATE TABLE '.$table.' ('.$pk.' SERIAL PRIMARY KEY)');
	}
	protected function _addColumnQuery( $type, $column, $field ){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable( $table );
		$column = $this->esc( $column );
		if(is_integer($type))
			$type = isset( $this->typeno_sqltype[$type] ) ? $this->typeno_sqltype[$type] : '';
		$this->execute('ALTER TABLE '.$table.' ADD '.$column.' '.$type);
	}
	protected function _changeColumnQuery( $type, $column, $dataType ){
		$table   = $this->escTable( $type );
		$column  = $this->esc( $column );
		if(is_integer($dataType))
			$dataType = $this->typeno_sqltype[$dataType];
		$this->execute('ALTER TABLE '.$table.' ALTER COLUMN '.$column.' TYPE '.$dataType);
	}
	protected function _removeColumnQuery($type,$column){
		$table  = $this->escTable($type);
		$column = $this->esc($column);
		$this->execute('ALTER TABLE '.$table.' DROP COLUMN '.$column);
	}
	
	protected function _getKeyMapForType($type){
		$keys = $this->getAll( '
			SELECT
			information_schema.key_column_usage.constraint_name AS "name",
			information_schema.key_column_usage.column_name AS "from",
			information_schema.constraint_table_usage.table_name AS "table",
			information_schema.constraint_column_usage.column_name AS "to",
			information_schema.referential_constraints.update_rule AS "on_update",
			information_schema.referential_constraints.delete_rule AS "on_delete"
				FROM information_schema.key_column_usage
			INNER JOIN information_schema.constraint_table_usage
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.constraint_table_usage.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.constraint_table_usage.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.constraint_table_usage.constraint_catalog
				)
			INNER JOIN information_schema.constraint_column_usage
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.constraint_column_usage.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.constraint_column_usage.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.constraint_column_usage.constraint_catalog
				)
			INNER JOIN information_schema.referential_constraints
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.referential_constraints.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.referential_constraints.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.referential_constraints.constraint_catalog
				)
			WHERE
				information_schema.key_column_usage.table_catalog = current_database()
				AND information_schema.key_column_usage.table_schema = ANY( current_schemas( FALSE ) )
				AND information_schema.key_column_usage.table_name = ?
		', [$type] );
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}
	protected function _addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE ){
		$table = $this->escTable( $type );
		$targetTable = $this->escTable( $targetType );
		$field = $this->esc( $property );
		$targetField = $this->esc( $targetProperty );
		$fieldNoQ = $this->check( $property );
		
		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$fk = $this->getForeignKeyForTypeProperty( $type, $fieldNoQ );
		if ( !is_null( $fk )
			&&($fk['on_update']==$casc||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$casc||$fk['on_delete']=='CASCADE')
		)
			return false;
		try{
			$this->execute( "ALTER TABLE {$table}
				ADD FOREIGN KEY ( {$field} ) REFERENCES  {$targetTable}
				({$targetField}) ON DELETE {$casc} ON UPDATE {$casc} DEFERRABLE ;" );
			return true;
		} catch ( \PDOException $e ) {
			return false;
		}
		return true;
	}
	function columnCode( $typedescription, $includeSpecials = FALSE ){
		$typedescription = strtolower($typedescription);
		$r = isset($this->sqltype_typeno[$typedescription])?$this->sqltype_typeno[$typedescription]:99;
		if ( $includeSpecials )
			return $r;
		if ( $r >= self::C_DATATYPE_RANGE_SPECIAL )
			return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	function getTypeForID(){
		return self::C_DATATYPE_INTEGER;
	}
	protected function _addUniqueConstraint( $type, $properties ){
		$tableNoQ = $this->prefixTable( $type );
		$columns = [];
		foreach( (array)$properties as $key => $column )
			$columns[$key] = $this->esc( $column );
		$table = $this->escTable( $type );
		sort($columns);
		$indexMap = $this->getCol('SELECT conname FROM pg_constraint WHERE conrelid = (SELECT oid FROM pg_class WHERE relname = ?)',[$tableNoQ]);
		$name = 'uq_'.sha1( $table . implode( ',', $columns ) );
		if(!in_array($name,$indexMap))
			$this->execute('ALTER TABLE '.$table.' ADD CONSTRAINT "'.$name.'" UNIQUE('.implode(',',$columns).')');
	}
	protected function _getUniqueConstraints($type,$prefix=true){
		$table   = $prefix?$this->prefixTable($type):$type;
		$indexMap = $this->getAll("SELECT tc.constraint_name, kcu.column_name, kcu.ordinal_position
FROM information_schema.table_constraints tc
LEFT JOIN information_schema.key_column_usage kcu ON tc.constraint_catalog = kcu.constraint_catalog AND tc.constraint_schema = kcu.constraint_schema AND tc.constraint_name = kcu.constraint_name
WHERE tc.table_name = ? AND tc.constraint_type = 'UNIQUE'",[$table]);
		$indexes = [];
		foreach($indexMap as $v){
			$indexes[$v['constraint_name']][$v['ordinal_position']] = $v['column_name'];
		}
		foreach($indexes as &$v){
			ksort($v);
			$v = array_values($v);
		}
		return array_values($indexes);
	}
	protected function _addIndex( $type, $property, $name=null ){
		if(!$name) $name = 'index_'.$property;
		$table  = $this->escTable( $type );
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $property );
		try{
			$this->execute( "CREATE INDEX {$name} ON $table ({$column}) " );
			return true;
		}
		catch(\PDOException $e){
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('TRUNCATE '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		$this->execute('SET CONSTRAINTS ALL DEFERRED');
		$this->execute("DROP TABLE IF EXISTS $t CASCADE ");
		$this->execute('SET CONSTRAINTS ALL IMMEDIATE');
	}
	protected function _dropAll(){
		$this->execute('SET CONSTRAINTS ALL DEFERRED');
		foreach($this->getTables() as $t){
			$this->execute('DROP TABLE IF EXISTS "'.$t.'" CASCADE ');
		}
		$this->execute('SET CONSTRAINTS ALL IMMEDIATE');
	}
	
	protected function explain($sql,$bindings=[]){
		$sql = ltrim($sql);
		if(!in_array(strtoupper(substr($sql,0,6)),['SELECT','DELETE','INSERT','UPDATE','VALUES'])
			&&!in_array(strtoupper(substr($sql,0,7)),['REPLACE','EXECUTE','DECLARE'])
		)
			return false;
		$explain = $this->pdo->prepare('EXPLAIN '.$sql,[\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT=>true]);
		$this->bindParams($explain,$bindings);
		$explain->execute();
		$explain = $explain->fetchAll();
		return implode("\n",array_map(function($entry){
			return implode("\n",$entry);
		}, $explain));
	}
	
	function getFkMap($type,$primaryKey='id'){
		$table = $this->prefixTable($type);
		return $this->getAll('SELECT
			tc.table_name AS table,
			kcu.column_name as column,
			tc.constraint_name as constraint,
			update_rule as on_update,
			delete_rule as on_delete
		FROM
			information_schema.referential_constraints AS rc
		JOIN
			information_schema.table_constraints AS tc USING(constraint_catalog,constraint_schema,constraint_name)
		JOIN
			information_schema.key_column_usage AS kcu USING(constraint_catalog,constraint_schema,constraint_name)
		JOIN
			information_schema.key_column_usage AS ccu ON(ccu.constraint_catalog=rc.unique_constraint_catalog AND ccu.constraint_schema=rc.unique_constraint_schema AND ccu.constraint_name=rc.unique_constraint_name)
		WHERE
			ccu.table_catalog=current_database()
			AND ccu.table_schema=ANY( current_schemas( FALSE ) )
			AND ccu.table_name=?
			AND ccu.column_name=?',[$table,$primaryKey]);
	}
	
	function adaptPrimaryKey($type,$id,$primaryKey='id'){
		if($id!=2147483647)
			return;
		$cols = $this->getColumns($type);
		if($cols[$primaryKey]=='bigint')
			return;
		$table = $this->escTable($type);
		$pk = $this->esc($primaryKey);
		$fks = $this->getFkMap($type,$primaryKey);
		foreach($fks as $fk){
			$this->execute('ALTER TABLE "'.$fk['table'].'" ALTER "'.$fk['column'].'" TYPE bigint');
		}
		$this->execute('ALTER TABLE '.$table.' ALTER '.$pk.' TYPE bigint');
	}
	
	function autoFillTextColumns($type,$uniqTextKey){
		$sufxL = -1*strlen($this->ftsTableSuffix);
		$columns = [];
		foreach($this->getColumns($type) as $col=>$colType){
			if(($colType=='text'||substr($colType,0,4)=='date')
				&&($col==$uniqTextKey||substr($col,$sufxL)==$this->ftsTableSuffix))
				$columns[] = $col;
		}
		return $columns;
	}
	function addFtsColumn($type,&$columns=[],$primaryKey='id',$uniqTextKey='uniq',$lang=null){
		$columnsMap = $this->getColumns($type);
		$table = $this->prefixTable($type);
		if(empty($columns)){
			$columns = $this->autoFillTextColumns($type,$uniqTextKey);
			if(empty($columns))
				throw $this->schemaException('Unable to find columns from "'.$table.'" to create FTS column "'.$col.'"');
			sort($columns);
			$indexName = '_auto_'.implode('_',$columns);
			$vacuum = false;
			foreach($columnsMap as $k=>$v){
				if(substr($k,6)=='_auto_'&&$type='tsvector'){
					$this->execute('ALTER TABLE "'.$table.'" DROP COLUMN "'.$indexName.'"');
					$vacuum = true;
				}
			}
			if($vacuum)
				$this->execute('VACUUM FULL "'.$table.'"');
		}
		else{
			sort($columns);
			$indexName = implode('_',$columns);
		}
		if(!isset($columnsMap[$indexName])){
			$newColumns = [];
			$tsColumns = [];
			foreach($columns as $col){
				$newColumns[] = 'COALESCE(to_tsvector(NEW."'.$col.'"),\'\')';
				$tsColumns[] = 'COALESCE(to_tsvector('.$lang.'"'.$col.'"),\'\')';
				
			}
			$newColumns = implode('||',$newColumns);
			if(!isset($name))
				$name = $table.'_'.$indexName.'_fulltext';
			$name   = preg_replace('/\W/', '', $name);
			$this->execute('ALTER TABLE "'.$table.'" ADD "'.$indexName.'" tsvector');

			$this->execute('UPDATE "'.$table.'" AS "_'.$table.'" SET '.$indexName.'=(SELECT '.implode('||',$tsColumns).' FROM '.$table.' WHERE "'.$table.'"."'.$primaryKey.'"="_'.$table.'"."'.$primaryKey.'")');
			$this->execute('
			CREATE OR REPLACE FUNCTION trigger_'.$name.'() RETURNS trigger AS $$
				begin
				  new."'.$indexName.'" :=  ('.$newColumns.');
				  return new;
				end
				$$ LANGUAGE plpgsql;
			');
			
			$this->execute('CREATE TRIGGER trigger_update_'.$name.' BEFORE INSERT OR UPDATE ON "'.$table.'"
							FOR EACH ROW EXECUTE PROCEDURE trigger_'.$name.'();');
			$this->execute('CREATE INDEX '.$name.' ON "'.$table.'" USING gin("'.$indexName.'")');
			if($lang)
				$this->execute('ALTER TABLE "'.$table.'" ADD language text NOT NULL DEFAULT(\''.$lang.'\')');
		}
		return $indexName;
	}
}
}
#DataSource/Sqlite.php

namespace FoxORM\DataSource {
class Sqlite extends SQL{
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;
	protected $quoteCharacter = '`';
	
	protected $separator = ',';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $concatenator = "cast(X'1D' as text)";
	protected $unknownDatabaseCode = 14;
	
	protected $foreignKeyEnabled;
	
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER => 'INTEGER',
			self::C_DATATYPE_NUMERIC => 'NUMERIC',
			self::C_DATATYPE_TEXT    => 'TEXT',
		];
		foreach ( $this->typeno_sqltype as $k => $v )
			$this->sqltype_typeno[strtolower($v)] = $k;
	}
	function connect(){
		if($this->isConnected)
			return;
		try {
			$this->setPDO($this->dsn);
			$this->isConnected = true;
		}
		catch ( \PDOException $exception ) {
			if($this->createDb&&(!$this->unknownDatabaseCode||$this->unknownDatabaseCode==$exception->getCode())){
				$p = strpos($this->dsn,':')+1;
				$p2 = strpos($this->dsn,';',$p);
				if($p2===false){
					$dbfile = substr($this->dsn,$p);
				}
				else{
					$dbfile = substr($this->dsn,$p,$p2-$p);
				}
				$this->createDatabase($dbfile);
				$this->setPDO($this->dsn);
				$this->isConnected = true;
			}
			else{
				$this->isConnected = false;
				throw $exception;
			}
		}
	}
	function createDatabase($dbfile){
		$dir = dirname($dbfile);
		if(is_dir($dir)){
			throw $this->schemaException('Unable to write '.$dbfile.' db file');
		}
		elseif(!mkdir($dir,0777,true)){
			throw $this->schemaException('Unable to make '.dirname($dbfile).' directory');
		}
	}
	function scanType( $value, $flagSpecial = FALSE ){
		if ( $value === NULL ) return self::C_DATATYPE_INTEGER;
		if ( $value === INF ) return self::C_DATATYPE_TEXT;

		if ( self::startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;

		if ( $value === TRUE || $value === FALSE )  return self::C_DATATYPE_INTEGER;
		
		if ( is_numeric( $value ) && ( intval( $value ) == $value ) && $value < 2147483648 && $value > -2147483648 ) return self::C_DATATYPE_INTEGER;

		if ( ( is_numeric( $value ) && $value < 2147483648 && $value > -2147483648)
			|| preg_match( '/\d{4}\-\d\d\-\d\d/', $value )
			|| preg_match( '/\d{4}\-\d\d\-\d\d\s\d\d:\d\d:\d\d/', $value )
		) {
			return self::C_DATATYPE_NUMERIC;
		}
		return self::C_DATATYPE_TEXT;
	}
	protected function _getTablesQuery(){
		return $this->getCol("SELECT name FROM sqlite_master WHERE type='table' AND name!='sqlite_sequence';");
	}
	protected function _getColumnsQuery($table){
		$table      = $this->prefixTable($table);
		$columnsRaw = $this->getAll("PRAGMA table_info('$table')");
		$columns    = [];
		foreach($columnsRaw as $r)
			$columns[$r['name']] = $r['type'];
		return $columns;
	}
	protected function _createTableQuery($table,$pk='id'){
		$table = $this->escTable($table);
		$this->execute('CREATE TABLE '.$table.' ( '.$pk.' INTEGER PRIMARY KEY AUTOINCREMENT ) ');
	}
	protected function _addColumnQuery($table, $column, $type){
		$column = $this->esc($column);
		$table  = $this->escTable($table);
		if(is_integer($type))
			$type   = $this->typeno_sqltype[$type];
		$this->execute('ALTER TABLE '.$table.' ADD '.$column.' '.$type);
	}
	protected function _changeColumnQuery($type, $column, $dataType){
		$t = $this->getTable( $type );
		if(is_integer($dataType))
			$dataType = $this->typeno_sqltype[$dataType];
		$t['columns'][$column] = $dataType;
		$this->putTable($t);
	}
	protected function _removeColumnQuery($type, $column){
		$t = $this->getTable( $type );
		$this->putTable($t, [$column]);
	}
	
	protected function putTable( $tableMap, $removeColumn=[] ){ //In SQLite we can't change columns, drop columns, change or add foreign keys so we have a table-rebuild function. You simply load your table with getTable(), modify it and then store it with putTable()
		$type = $tableMap['name'];
		$table = $this->prefixTable($type);
		$q     = [];
		$tmpName = '_tmp_backup_'.$type;
		$q[]   = "DROP TABLE IF EXISTS $tmpName;";
		$newColumnNames = [];
		$oldColumnNames = array_keys( $this->getColumns( $type ) );
		foreach($oldColumnNames as $k => $v){
			if(!in_array($k,$removeColumn)){
				$newColumnNames[$k] = $this->esc($v);
			}
		}
		$q[] = "CREATE TEMPORARY TABLE {$tmpName}(" . implode( ",", $newColumnNames ) . ");";
		$q[] = "INSERT INTO $tmpName SELECT * FROM `$table`;";
		$q[] = "PRAGMA foreign_keys = 0 ";
		$q[] = "DROP TABLE `$table`;";
		$newTableDefStr = '';
		foreach ( $tableMap['columns'] as $column => $type ) {
			if ( $column != 'id' && !in_array($column,$removeColumn)) {
				$newTableDefStr .= ",`$column` $type";
			}
		}
		$fkDef = '';
		foreach ( $tableMap['keys'] as $key ) {
			$fkDef .= ", FOREIGN KEY(`{$key['from']}`)
						 REFERENCES `{$key['table']}`(`{$key['to']}`)
						 ON DELETE {$key['on_delete']} ON UPDATE {$key['on_update']}";
		}
		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  $fkDef )";
		foreach ( $tableMap['indexes'] as $name => $index ) {
			if ( strpos( $name, 'uq_' ) === 0 ) {
				$cols = explode( '__', substr( $name, strlen( 'uq_' . $table ) ) );
				foreach ( $cols as $k => $v )
					$cols[$k] = "`$v`";
				$q[] = "CREATE UNIQUE INDEX $name ON `$table` (" . implode( ',', $cols ) . ")";
			}
				else $q[] = "CREATE INDEX $name ON `$table` ({$index['name']}) ";
		}
		$q[] = "INSERT INTO `$table` SELECT * FROM $tmpName";
		$q[] = "DROP TABLE $tmpName";
		$q[] = "PRAGMA foreign_keys = 1 ";
		foreach ( $q as $sq ){
			$this->execute( $sq );
		}
	}
	function getTable( $type ){
		$columns   = $this->getColumns($type);
		$indexes   = $this->getIndexes($type);
		$keys      = $this->getKeyMapForType($type);
		$table = [
			'columns' => $columns,
			'indexes' => $indexes,
			'keys' => $keys,
			'name' => $type
		];
		return $table;
	}
	function getIndexes( $type ){
		$table   = $this->prefixTable( $type );
		$indexes = $this->getAll("PRAGMA index_list('$table')");
		$indexInfoList = [];
		foreach ( $indexes as $i ) {
			$indexInfoList[$i['name']] = $this->getRow( "PRAGMA index_info('{$i['name']}') " );
			$indexInfoList[$i['name']]['unique'] = $i['unique'];
		}
		return $indexInfoList;
	}	
	protected function _getKeyMapForType($type){
		$table = $this->prefixTable( $type );
		$keys  = $this->getAll( "PRAGMA foreign_key_list('$table')" );
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $label,
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}
	protected function _addFK( $type, $targetType, $property, $targetProperty, $constraint = false ){
		$targetTable     = $this->prefixTable( $targetType );
		$column          = $this->check( $property );
		$targetColumn    = $this->check( $targetProperty );

		$tables = $this->getTables();
		if ( !in_array( $targetTable, $tables ) )
			return false;
		
		$consSQL = $constraint ? 'CASCADE' : 'SET NULL';
		$fk = $this->getForeignKeyForTypeProperty( $type, $column );
		if ( !is_null( $fk )
			&&($fk['on_update']==$consSQL||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$consSQL||$fk['on_update']=='CASCADE')
		)
			return false;
		$t = $this->getTable( $type );
		$label   = 'from_' . $column . '_to_table_' . $targetTable . '_col_' . $targetColumn;
		$t['keys'][$label] = array(
			'table'     => $targetTable,
			'from'      => $column,
			'to'        => $targetColumn,
			'on_update' => $consSQL,
			'on_delete' => $consSQL
		);
		$this->putTable( $t );
		return true;
	}
	function columnCode( $typedescription, $includeSpecials = FALSE ){
		$typedescription = strtolower($typedescription);
		return  ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription]:99;
	}
	function getTypeForID(){
		return self::C_DATATYPE_INTEGER;
	}
	protected function _addUniqueConstraint( $type, $properties ){
		$name  = 'uq_'.$this->prefixTable($type).implode('__',(array)$properties);
		$t     = $this->getTable($type);
		if(isset($t['indexes'][$name]))
			return true;
		$t['indexes'][$name] = ['name'=>$name];
		$this->putTable( $t );
	}
	protected function _getUniqueConstraints($type,$prefix=true){
		$table   = $prefix?$this->prefixTable($type):$type;
		$indexesList = $this->getAll("PRAGMA index_list('$table')");
		$indexes = [];
		foreach ( $indexesList as $i ) {
			if($i['unique']){
				$values = $this->getAll("PRAGMA index_info('{$i['name']}') ");
				foreach($values as $v){
					$indexes[$i['name']][$v['cid']] = $v['name'];
				}
			}
		}
		foreach($indexes as &$v){
			ksort($v);
			$v = array_values($v);
		}
		return array_values($indexes);
	}
	protected function _addIndex( $type, $column, $name=null ){
		if(!$name) $name = 'index_'.$property;
		$columns = $this->getColumns( $type );
		if ( !isset( $columns[$column] ) )
			return false;
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->check( $column );
		try {
			$t = $this->getTable( $type );
			$t['indexes'][$name] = [ 'name' => $column ];
			$this->putTable($t);
			return true;
		} catch( \PDOException $exception ) {
			return false;
		}
	}
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('DELETE FROM '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		$this->execute('PRAGMA foreign_keys = 0 ');
		try {
			$this->execute('DROP TABLE IF EXISTS '.$t);
		}
		catch (\PDOException $e ) {}
		$this->execute('PRAGMA foreign_keys = 1');
	}
	protected function _dropAll(){
		$this->execute('PRAGMA foreign_keys = 0');
		foreach($this->getTables() as $t){
			try{
				$this->execute("DROP TABLE IF EXISTS `$t`");
			}
			catch(\PDOException $e){}
		}
		$this->execute('PRAGMA foreign_keys = 1 ');
	}
	protected function explain($sql,$bindings=[]){
		$sql = ltrim($sql);
		if(!in_array(strtoupper(substr($sql,0,6)),['SELECT','DELETE','INSERT','UPDATE']))
			return false;
		$explain = $this->pdo->prepare('EXPLAIN QUERY PLAN '.$sql);
		$this->bindParams($explain,$bindings);
		$explain->execute();
		$explain = $explain->fetchAll();
		$i = 0;
		return implode("\n",array_map(function($entry)use(&$i){
			$i++;
			return str_repeat('  ',$i-1).implode('|',$entry);
		}, $explain));
	}
	function getFtsTableSuffix(){
		return $this->ftsTableSuffix;
	}
	function enableForeignKeys(){
		$this->connect();
		$this->pdo->exec('PRAGMA foreign_keys = ON');
		$this->foreignKeyEnabled = true;
	}
	function disableForeignKeys(){
		$this->connect();
		$this->pdo->exec('PRAGMA foreign_keys = OFF');
		$this->foreignKeyEnabled = false;
	}
	
	function deleteQuery($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->foreignKeyEnabled)
			$this->enableForeignKeys();
		return parent::deleteQuery($type,$id,$primaryKey,$uniqTextKey);
	}
	function updateQuery($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq',$cast=[],$func=[]){
		if(!$this->foreignKeyEnabled)
			$this->enableForeignKeys();
		return parent::updateQuery($type,$properties,$id,$primaryKey,$uniqTextKey,$cast,$func);
	}
	function createQuery($type,$properties,$primaryKey='id',$uniqTextKey='uniq',$cast=[],$func=[],$forcePK=null){
		if(!$this->foreignKeyEnabled)
			$this->enableForeignKeys();
		return parent::createQuery($type,$properties,$primaryKey,$uniqTextKey,$cast,$func,$forcePK);
	}
	
	function makeFtsTable($type,$columns=[],$primaryKey='id',$uniqTextKey='uniq',$fullTextSearchLocale=null){
		$ftsTable = $this->escTable($type.$this->ftsTableSuffix);
		$table = $this->escTable($type);
		if(empty($columns)){
			$sufxL = -1*strlen($this->ftsTableSuffix);
			foreach($this->getColumns($type) as $col=>$colType){
				if(strtolower($colType)=='text'&&($col==$uniqTextKey||substr($col,$sufxL)==$this->ftsTableSuffix))
					$columns[] = $col;
			}
			if(empty($columns))
				throw $this->schemaException('Unable to find columns from "'.$table.'" to create FTS table "'.$ftsTable.'"');
		}
		$ftsType = $type.$this->ftsTableSuffix;
		$pTable = $this->prefixTable($type);
		$exist = $this->tableExists($ftsType);
		$makeColumns = $columns;
		if($exist){
			$oldColumns = array_keys($this->getColumns($ftsType));
			foreach($columns as $col){
				if(!in_array($col,$oldColumns)){
					$this->execute('DROP TABLE '.$ftsType);
					foreach($oldColumns as $col){
						if(!in_array($col,$makeColumns))
							$makeColumns[] = $col;
					}
					$exist = false;
					break;
				}
			}
		}
		if(!$exist){
			if($fullTextSearchLocale)
				$tokenize = 'icu '.$fullTextSearchLocale;
			else
				$tokenize = 'porter';
			$pk = $this->esc($primaryKey);
			$cols = '`'.implode('`,`',$makeColumns).'`';
			$newCols = 'NEW.`'.implode('`,NEW.`',$makeColumns).'`';
			$this->execute('CREATE VIRTUAL TABLE '.$ftsTable.' USING fts4('.$cols.', tokenize='.$tokenize.')');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_bu');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_bd');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_au');
			$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_ad');
			$this->execute("CREATE TRIGGER {$pTable}_bu BEFORE UPDATE ON {$table} BEGIN DELETE FROM {$ftsTable} WHERE docid=OLD.{$pk}; END;");
			$this->execute("CREATE TRIGGER {$pTable}_bd BEFORE DELETE ON {$table} BEGIN DELETE FROM {$ftsTable} WHERE docid=OLD.{$pk}; END;");
			$this->execute("CREATE TRIGGER {$pTable}_au AFTER UPDATE ON {$table} BEGIN INSERT INTO {$ftsTable}(docid, {$cols}) VALUES(NEW.{$pk}, {$newCols}); END;");
			$this->execute("CREATE TRIGGER {$pTable}_ad AFTER INSERT ON {$table} BEGIN INSERT INTO {$ftsTable}(docid, {$cols}) VALUES(NEW.{$pk}, {$newCols}); END;");
			$this->execute('INSERT INTO '.$ftsTable.'(docid,'.$cols.') SELECT '.$pk.','.$cols.' FROM '.$table);
		}
	}
}
}
#DataSource/Filesystem.php

namespace FoxORM\DataSource {
use FoxORM\DataSource;
class Filesystem extends DataSource{
	private $directory;
	function construct(array $config=[]){
		if(isset($config[0]))
			$this->directory = rtrim($config[0],'/');
		else
			$this->directory = isset($config['directory'])?rtrim($config['directory'],'/'):'.';
	}
	function getDirectory(){
		return $this->directory;
	}
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		return file_exists($this->directory.'/'.$type.'/'.$id)?$id:false;
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		
	}
	function putRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		
	}
	function debug($level=self::DEBUG_ON){
		parent::debug($level);
	}
	
	function getAll($q, $bind = []){
		
	}
	function getRow($q, $bind = []){
		
	}
	function getCol($q, $bind = []){
		
	}
	function getCell($q, $bind = []){
		
	}
}
}
#DataSource/Cubrid.php

namespace FoxORM\DataSource {
use PDOException;
use BadMethodCallException;
class Cubrid extends SQL{
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_BIGINT           = 1;
	const C_DATATYPE_DOUBLE           = 2;
	const C_DATATYPE_STRING           = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIFIED        = 99;
	protected $quoteCharacter = '`';
	protected $max = 2147483647;
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_INTEGER          => ' INTEGER ',
			self::C_DATATYPE_BIGINT           => ' BIGINT ',
			self::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			self::C_DATATYPE_STRING           => ' STRING ',
			self::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
		];
		$this->sqltype_typeno = [];
		foreach( $this->typeno_sqltype as $k => $v ){
			$this->sqltype_typeno[strtolower(trim($v))] = $k;
		}
		$this->sqltype_typeno['string(1073741823)'] = self::C_DATATYPE_STRING;
	}
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		if($this->debugLevel&self::DEBUG_EXPLAIN)
			$this->pdo->exec('SET TRACE ON');
	}
	function debug($level=self::DEBUG_ON){
		parent::debug($level);
		if($this->debugLevel&self::DEBUG_EXPLAIN&&$this->isConnected)
			$this->pdo->exec('SET TRACE ON');
	}
	function createDatabase($dbname){
		throw $this->schemaException('Unable to create database '.$dbname.'. CUBRID does not allow to create or drop a database from within the SQL query');
	}
	function scanType($value, $flagSpecial = false){
		if ( is_null( $value ) )
			return self::C_DATATYPE_INTEGER;
		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
		}
		$value = strval( $value );
		if ( !$this->startsWithZeros( $value ) ) {
			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= -2147483647 && $value <= 2147483647 )
				return self::C_DATATYPE_INTEGER;
			elseif ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= -9223372036854775807 && $value <= 9223372036854775807 )
				return self::C_DATATYPE_BIGINT;
			if ( is_numeric( $value ) )
				return self::C_DATATYPE_DOUBLE;
		}
		return self::C_DATATYPE_STRING;
	}
	protected function _getTablesQuery(){
		return $this->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );
	}
	protected function _getColumnsQuery( $table ){
		$table = $this->escTable( $table );
		$columnsRaw = $this->getAll( "SHOW COLUMNS FROM $table" );
		$columns = [];
		foreach($columnsRaw as $r)
			$columns[$r['Field']] = $r['Type'];
		return $columns;
	}
	protected function _createTableQuery($table,$pk='id'){
		$sql  = 'CREATE TABLE '.$this->escTable($table)
			.' ("'.$pk.'" integer AUTO_INCREMENT, CONSTRAINT "pk_'
			.$this->prefixTable($table)
			.'_'.$pk.'" PRIMARY KEY("'.$pk.'"))';
		$this->execute( $sql );
	}
	protected function _addColumnQuery( $type, $column, $field ){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable( $table );
		$column = $this->esc( $column );
		if(is_integer($type))
			$type   = array_key_exists( $type, $this->typeno_sqltype ) ? $this->typeno_sqltype[$type] : '';
		$this->execute( "ALTER TABLE $table ADD COLUMN $column $type " );
	}
	protected function _changeColumnQuery( $type, $property, $dataType ){
		$table   = $this->escTable( $type );
		$column  = $this->esc( $property );
		if(is_integer($dataType)){
			if( !isset($this->typeno_sqltype[$dataType]) )
				return false;
			$dataType = $this->typeno_sqltype[$dataType];
		}
		$this->execute( "ALTER TABLE $table CHANGE $column $column $dataType " );
	}
	protected function _removeColumnQuery($type,$column){
		$table  = $this->escTable($type);
		$column = $this->esc($column);
		$this->execute('ALTER TABLE '.$table.' DROP COLUMN '.$column);
	}
	
	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type           type that will have a foreign key field
	 * @param  string $targetType     points to this type
	 * @param  string $field          field that contains the foreign key value
	 * @param  string $targetField    field where the fk points to
	 *
	 * @return void
	 */
	protected function _addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE ){
		$table           = $this->escTable( $type );
		$targetTable     = $this->escTable( $targetType );
		$column          = $this->esc( $property );
		$columnNoQ       = $this->check( $property );
		$targetColumn    = $this->esc( $targetProperty );
		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$fk = $this->getForeignKeyForTypeProperty( $type, $columnNoQ );
		if ( !is_null( $fk )&&($fk['on_delete']==$casc||$fk['on_delete']=='CASCADE'))
			return false;
		$sql  = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc";
		try {
			$this->execute($sql);
		} catch( PDOException $e ) {
			return false;
		}
		return true;
	}
	protected function _getKeyMapForType( $type  ){
		$table = $this->prefixTable($type);
		$sqlCode = $this->getAll("SHOW CREATE TABLE `{$table}`");
		if(!isset($sqlCode[0]))
			return [];
		preg_match_all('/CONSTRAINT\s+\[([\w_]+)\]\s+FOREIGN\s+KEY\s+\(\[([\w_]+)\]\)\s+REFERENCES\s+\[([\w_]+)\](\s+ON\s+DELETE\s+(CASCADE|SET\sNULL|RESTRICT|NO\sACTION)\s+ON\s+UPDATE\s+(SET\sNULL|RESTRICT|NO\sACTION))?/', $sqlCode[0]['CREATE TABLE'], $matches);
		$list = [];
		if(!isset($matches[0]))
			return $list;
		$max = count($matches[0]);
		for($i = 0; $i < $max; $i++) {
			$label = self::makeFKLabel( $matches[2][$i], $matches[3][$i], 'id' );
			$list[ $label ] = array(
				'name' => $matches[1][$i],
				'from' => $matches[2][$i],
				'table' => $matches[3][$i],
				'to' => 'id',
				'on_update' => $matches[6][$i],
				'on_delete' => $matches[5][$i]
			);
		}
		return $list;
	}
	function columnCode( $typedescription, $includeSpecials = FALSE ){
		$typedescription = strtolower($typedescription);
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED );
		if ( $includeSpecials )
			return $r;
		if ( $r >= self::C_DATATYPE_RANGE_SPECIAL )
			return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	function getTypeForID(){
		return self::C_DATATYPE_INTEGER;
	}
	protected function _addUniqueConstraint( $type, $properties ){
		$columns = [];
		foreach( (array)$properties as $key => $column )
			$columns[$key] = $this->esc( $column );
		$table = $this->escTable( $type );
		sort($columns);
		$name = 'uq_' . sha1( implode( ',', $columns ) );
		$indexMap = $this->getAll('SHOW indexes FROM '.$table);
		$exists = false;
		foreach($indexMap as $index){
			if($index['Key_name']==$name){
				$exists = true;
				break;
			}
		}
		if(!$exists)
			$this->execute("ALTER TABLE $table ADD CONSTRAINT UNIQUE `$name` (" . implode( ',', $columns ) . ")");
	}
	protected function _getUniqueConstraints($type,$prefix=true){
		throw new BadMethodCallException('method '.__FUNCTION__.' is not allready implemented in '.__CLASS__.', too busy for now, if you want to write it, feel free to do and send me the source');
	}
	protected function _addIndex( $type, $column, $name=null ){
		if(!$name) $name = 'index_'.$property;
		try {
			$table  = $this->escTable( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->esc( $column );
			$this->execute("CREATE INDEX $name ON $table ($column) ");
			return true;
		} catch ( PDOException $e ) {
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('TRUNCATE '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		foreach($this->getKeyMapForType($type) as $k){
			$this->execute('ALTER TABLE '.$t.' DROP FOREIGN KEY "'.$k['name'].'"');
		}
		$this->execute('DROP TABLE '.$t);
	}
	protected function _dropAll(){
		foreach($this->getTables() as $t){
			$this->_drop($this->unprefixTable($t));
		}
	}
	
	protected function explain($sql,$bindings=[]){
		$explain = $this->pdo->query('SHOW TRACE')->fetchAll();
		return implode("\n",array_map(function($entry){
			return implode("\n",$entry);
		}, $explain));
	}
	
	function getFkMap($type,$primaryKey='id'){
		//foreign keys can only reference primary keys in CUBRID
		$fks = [];
		$table = $this->prefixTable($type);
		foreach($this->getTables() as $tb){
			$sqlCode = $this->getAll("SHOW CREATE TABLE `{$tb}`");
			if(!isset($sqlCode[0]))
				continue;
			preg_match_all('/CONSTRAINT\s+\[([\w_]+)\]\s+FOREIGN\s+KEY\s+\(\[([\w_]+)\]\)\s+REFERENCES\s+\[([\w_]+)\](\s+ON\s+DELETE\s+(CASCADE|SET\sNULL|RESTRICT|NO\sACTION)\s+ON\s+UPDATE\s+(SET\sNULL|RESTRICT|NO\sACTION))?/', $sqlCode[0]['CREATE TABLE'], $matches);
			if(!isset($matches[0]))
				continue;
			$max = count($matches[0]);
			for($i = 0; $i < $max; $i++){
				if($matches[3][$i]==$table)
					$fks[] = [
						'table'=>$tb,
						'column'=>$matches[2][$i],
						'constraint'=>$matches[1][$i],
						'on_update'=>$matches[6][$i],
						'on_delete'=>$matches[5][$i],
					];
			}
		}
		return $fks;
	}
	
	function adaptPrimaryKey($type,$id,$primaryKey='id'){
		if($id!=2147483647)
			return;
		$cols = $this->getColumns($type);
		if($cols[$primaryKey]=='BIGINT')
			return;
		$table = $this->escTable($type);
		$pk = $this->esc($primaryKey);
		$fks = $this->getFkMap($type,$primaryKey);
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['constraint'].'`, MODIFY `'.$fk['column'].'` BIGINT NULL');
		}
		$this->execute('ALTER TABLE '.$table.' CHANGE '.$pk.' '.$pk.' BIGINT NOT NULL AUTO_INCREMENT');
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` ADD FOREIGN KEY (`'.$fk['column'].'`) REFERENCES '.$table.' ('.$pk.') ON DELETE '.$fk['on_delete'].' ON UPDATE '.$fk['on_update']);
		}
	}
}
}
#SqlComposer/Exception.php

namespace FoxORM\SqlComposer {
class Exception extends \Exception {}
}
#SqlComposer/Where.php

namespace FoxORM\SqlComposer {
abstract class Where extends Base {
	protected $where = [];
	protected $with = [];
	protected $likeEscapeChar = '=';
	function hasWhere(){
		return !empty($this->where);
	}
	function hasWith(){
		return !empty($this->with);
	}
	function getWhere(){
		return $this->where;
	}
	function getWith(){
		return $this->with;
	}
	function unWhere($where=null,$params=null){
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function replaceWhere($v=null,$new=null){
		foreach(array_keys($this->where) as $i){
			if($this->where[$i]==$v){
				if(is_array($this->where[$i])){
					$this->where[$i][0] = $new;
				}
				else{
					$this->where[$i] = $new;
				}
				break;
			}
		}
		return $this;
	}
	function unWith($with=null,$params=null){
		$this->remove_property('with',$with,$params);
		return $this;
	}
	function unWhereIn($where,$params=null){
		list($where, $params) = self::in($where, $params);
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unWhereOp($column, $op, array $params=null){
		list($where, $params) = self::applyOperator($column, $op, $params);
		$this->remove_property('where',$where,$params);
		return $this;
	}
	function unOpenWhereAnd() {
		$this->remove_property('where',['(', 'AND']);
		return $this;
	}
	function unOpenWhereOr() {
		$this->remove_property('where',['(', 'OR']);
		return $this;
	}
	function unOpenWhereNotAnd() {
		$this->remove_property('where',['(', 'NOT']);
		return $this->unOpenWhereAnd();
	}
	function unOpenWhereNotOr() {
		$this->remove_property('where',['(', 'NOT']);
		return $this->unOpenWhereOr();
	}
	function unCloseWhere() {
		$this->remove_property('where',[')']);
		return $this;
	}
	function where($where,  array $params = null) {
		$this->where[] = $where;
		$this->_add_params('where', $params);
		return $this;
	}
	function whereIn($where,  array $params) {
		list($where, $params) = self::in($where, $params);
		return $this->where($where, $params);
	}
	function whereOp($column, $op,  array $params=null) {
		list($where, $params) = self::applyOperator($column, $op, $params);
		return $this->where($where, $params);
	}
	function openWhereAnd() {
		$this->where[] = ['(', 'AND'];
		return $this;
	}
	function openWhereOr() {
		$this->where[] = ['(', 'OR'];
		return $this;
	}
	function openWhereNotAnd() {
		$this->where[] = ['(', 'NOT'];
		$this->openWhereAnd();
		return $this;
	}
	function openWhereNotOr() {
		$this->where[] = ['(', 'NOT'];
		$this->openWhereOr();
		return $this;
	}
	function closeWhere() {
		if(is_array($e=end($this->where))&&count($e)>1)
			array_pop($this->where);
		else
			$this->where[] = [')'];
		return $this;
	}
	function with($with,  array $params = null) {
		$this->with[] = $with;
		$this->_add_params('with', $params);
		return $this;
	}
	
	function escapeLike($like){
		return str_replace([$this->likeEscapeChar,'%','_'],[$this->likeEscapeChar.$this->likeEscapeChar,$this->likeEscapeChar.'%',$this->likeEscapeChar.'_'],$like);
	}
	function likeLeft($columns, $search, $and=false, $not=false){
		$search = $this->escapeLike($search).'%';
		$searchPattern = "? ESCAPE '".$this->likeEscapeChar."'";
		return $this->like($columns, $searchPattern, $search, $not);
	}
	function likeRight($columns, $search, $and=false, $not=false){
		$search = '%'.$this->escapeLike($search);
		$searchPattern = "? ESCAPE '".$this->likeEscapeChar."'";
		return $this->like($columns, $searchPattern, $search, $not);
	}
	function likeBoth($columns, $search, $and=false, $not=false){
		$search = '%'.$this->escapeLike($search).'%';
		$searchPattern = "? ESCAPE '".$this->likeEscapeChar."'";
		return $this->like($columns, $searchPattern, $search, $not);
	}
	function like($columns, $searchPattern, $search, $and=false, $not=false){
		$columns = (array)$columns;
		$multi = count($columns)>1;
		$prefix = $not?' NOT':'';
		if($multi){
			if($and){
				$this->openWhereAnd();
			}
			else{
				$this->openWhereOr();
			}
		}
		foreach($columns as $column){
			$this->where($column.$prefix.' LIKE '.$searchPattern, [$search]);
		}
		if($multi){
			$this->closeWhere();
		}
		return $this;
	}
	
	
	function notLike($columns, $searchPattern, $search, $and=false){
		return $this->like($columns, $searchPattern, $search, $and, true);
	}
	function notLikeLeft($columns, $search, $and=false){
		return $this->likeLeft($columns, $search, $and, true);
	}
	function notLikeRight($columns, $search, $and=false){
		return $this->likeRight($columns, $search, $and, true);
	}
	function notLikeBoth($columns, $search, $and=false){
		return $this->likeBoth($columns, $search, $and, true);
	}
	
	protected function _render_where($removeUnbinded=true){
		$where = $this->where;
		if($removeUnbinded)
			$where = $this->removeUnbinded($where);
		return self::render_bool_expr($where);
	}
}
}
#SqlComposer/Select.php

namespace FoxORM\SqlComposer {
use BadMethodCallException;
class Select extends Where {
	protected $distinct = false;
	protected $group_by = [];
	protected $with_rollup = false;
	protected $having = [];
	protected $order_by = [];
	protected $sort = [];
	protected $limit;
	protected $offset;
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = '', $execCallback=null, $dbType=null){
		parent::__construct($mainTable,$quoteCharacter, $tablePrefix, $execCallback, $dbType);
	}
	
	function hasSelect(){
		return $this->hasColumn();
	}
	function getSelect(){
		return $this->getColumn();
	}
	function hasDistinct(){
		return $this->distinct;
	}
	function hasGroupBy(){
		return !empty($this->group_by);
	}
	function getGroupBy(){
		return $this->group_by;
	}
	function hasWithRollup(){
		return $this->with_rollup;
	}
	function hasHaving(){
		return !empty($this->having);
	}
	function getHaving(){
		return $this->having;
	}
	function hasOrderBy(){
		return !empty($this->order_by);
	}
	function getOrderBy(){
		return $this->order_by;
	}
	function hasSort(){
		return !empty($this->sort);
	}
	function getSort(){
		return $this->sort;
	}
	function hasLimit(){
		return !empty($this->limit);
	}
	function getLimit(){
		return $this->limit;
	}
	function hasOffset(){
		return isset($this->offset);
	}
	function getOffset(){
		return $this->offset;
	}
	
	function select($select,  array $params = null){
		foreach((array)$select as $s){
			if(!empty($params)||!in_array($s,$this->columns))
				$this->columns[] = $s;
		}
		$this->_add_params('select', $params);
		return $this;
	}
	function selectMain($select,  array $params = null){
		foreach((array)$select as $s){
			if(!empty($params)||!in_array($s,$this->columns)){
				$this->columns[] = $this->formatColumnName($s);
			}
		}
		$this->_add_params('select', $params);
		return $this;
	}
	function distinct($distinct = true) {
		$this->distinct = (bool)$distinct;
		return $this;
	}
	function groupBy($group_by,  array $params = null) {
		if(!empty($params)||!in_array($group_by,$this->group_by))
			$this->group_by[] = $group_by;
		$this->_add_params('group_by', $params);
		return $this;
	}
	function withRollup($with_rollup = true) {
		$this->with_rollup = $with_rollup;
		return $this;
	}
	function orderByMain($order_by,  array $params = null) {
		$order_by = $this->formatColumnName($order_by);
		if(!empty($params)||!in_array($order_by,$this->order_by))
			$this->order_by[] = $order_by;
		$this->_add_params('order_by', $params);
		return $this;
	}
	function orderBy($order_by,  array $params = null) {
		if(!empty($params)||!in_array($order_by,$this->order_by))
			$this->order_by[] = $order_by;
		$this->_add_params('order_by', $params);
		return $this;
	}
	function sort($desc=false) {
		if(is_string($desc))
			$desc = strtoupper($desc);
		$this->sort[] = ($desc&&$desc!='ASC')||$desc=='DESC'?'DESC':'ASC';
		return $this;
	}
	function limit($limit){
		$limit = (int)$limit;
		$this->limit = $limit>0?$limit:null;
		if(func_num_args()>1)
			$this->offset(func_get_arg(1));
		return $this;
	}
	function offset($offset) {
		$this->offset = (int)$offset;
		return $this;
	}
	function having($having,  array $params = null) {
		$this->having = array_merge($this->having, (array)$having);
		$this->_add_params('having', $params);
		return $this;
	}
	function havingIn($having,  array $params) {
		if (!is_string($having)) throw new BadMethodCallException("Method having_in must be called with a string as first argument.");
		list($having, $params) = self::in($having, $params);
		return $this->having($having, $params);
	}
	function havingOp($column, $op,  array $params=null) {
		list($where, $params) = self::applyOperator($column, $op, $params);
		return $this->having($where, $params);
	}
	function openHavingAnd() {
		$this->having[] = [ '(', 'AND' ];
		return $this;
	}
	function openHavingOr() {
		$this->having[] = [ '(', 'OR' ];
		return $this;
	}
	function openHavingNotAnd() {
		$this->having[] = [ '(', 'NOT' ];
		$this->openHavingAnd();
		return $this;
	}
	function openHavingNotOr() {
		$this->having[] = [ '(', 'NOT' ];
		$this->openHavingOr();
		return $this;
	}
	function closeHaving() {
		if(is_array($e=end($this->having))&&count($e)>1)
			array_pop($this->having);
		else
			$this->having[] = [ ')' ];
		return $this;
	}
	function unSelect($select=null,  array $params = null){
		$this->remove_property('columns',$select,$params);
		return $this;
	}
	function unDistinct(){
		$this->distinct = false;
		return $this;
	}
	function unGroupBy($group_by=null,  array $params = null){
		$this->remove_property('group_by',$group_by,$params);
		return $this;
	}
	function unWithRollup(){
		$this->with_rollup = false;
		return $this;
	}
	function unOrderBy($order_by=null,  array $params = null){
		$i = $this->remove_property('order_by',$order_by,$params);
		if(isset($this->sort[$i]))
			unset($this->sort[$i]);
		return $this;
	}
	function unSort(){
		array_pop($this->sort);
		return $this;
	}
	function unLimit() {
		$this->limit = null;
		return $this;
	}
	function unOffset(){
		$this->offset = null;
		return $this;
	}
	function unHaving($having=null,  array $params = null){
		$this->remove_property('having',$having,$params);
		return $this;
	}
	function unHavingIn($having,  array $params){
		if (!is_string($having)) throw new BadMethodCallException("Method having_in must be called with a string as first argument.");
		list($having, $params) = self::in($having, $params);
		return $this->unHaving($having, $params);
	}
	function unHavingOp($column, $op,  array $params=null){
		list($where, $params) = self::applyOperator($column, $op, $params);
		return $this->unHaving($where, $params);
	}
	function unOpenHavingAnd() {
		$this->remove_property('having',[ '(', 'AND' ]);
		return $this;
	}
	function unOpenHavingOr() {
		$this->remove_property('having',[ '(', 'OR' ]);
		return $this;
	}
	function unOpenHavingNotAnd() {
		$this->remove_property('having',[ '(', 'NOT' ]);
		$this->unOpenHavingAnd();
		return $this;
	}
	function unOpenHavingNotOr() {
		$this->remove_property('having',[ '(', 'NOT' ]);
		$this->unOpenHavingOr();
		return $this;
	}
	function unCloseHaving() {
		$this->remove_property('having',[ ')' ]);
		return $this;
	}
	protected function _render_having($removeUnbinded=true){
		$having = $this->having;
		if($removeUnbinded)
			$having = $this->removeUnbinded($having);
		return self::render_bool_expr($having);
	}
	function render($removeUnbinded=true) {
		$with = empty($this->with) ? '' : 'WITH '.implode(', ', $this->with); //Postgresql specific
		$columns = empty($this->columns) ? '*' : implode(', ', $this->columns);
		$distinct = $this->distinct ? 'DISTINCT' : "";
		$from = '';
		$tables = [];
		$joins = [];
		$mt = $this->getMainTable();
		foreach($this->tables as $t){
			if(!is_array($t))
				$tables[] = $t;
			elseif(isset($t[1]))
				$joins[$t[0]][] = $t[1];
			elseif($mt)
				$joins[$mt][] = $t[0];
			else
				$joins[] = $t[0];
		}
		foreach($tables as $t){
			$from .= $this->formatTableName($t);
			if(isset($joins[$t])){
				foreach($joins[$t] as $j){
					$from .= ' '.$j;
				}
				unset($joins[$t]);
			}
			$from .= ',';
		}
		$from = rtrim($from,',');
		foreach($joins as $j){
			if(is_array($j)){
				foreach($j as $jj){
					$from .= ' '.$jj;
				}
			}
			else{
				$from .= ' '.$j;
			}
		}
		
		$from = "FROM ".$from;
		$where = $this->_render_where($removeUnbinded);
		if(!empty($where))
			$where =  "WHERE $where";
		$group_by = empty($this->group_by) ? "" : "GROUP BY " . implode(", ", $this->group_by);
		$order_by = '';
		if(!empty($this->order_by)){
			$order_by .= "ORDER BY ";
			foreach($this->order_by as $i=>$gb){
				$order_by .= $this->esc($gb);
				if(isset($this->sort[$i]))
					$order_by .= ' '.$this->sort[$i];
				$order_by .= ',';
			}
			$order_by = rtrim($order_by,',');
		}
		$with_rollup = $this->with_rollup ? "WITH ROLLUP" : "";
		$having = empty($this->having) ? "" : "HAVING " . $this->_render_having($removeUnbinded);
		$limit = "";
		if ($this->limit) {
			$limit = 'LIMIT '.$this->limit;
			if ($this->offset) {
				$limit .= ' OFFSET '.$this->offset;
			}
		}
		return "{$with} SELECT {$distinct} {$columns} {$from} {$where} {$group_by} {$with_rollup} {$having} {$order_by} {$limit}";
	}
	function getParams($type=null){
		if($type){
			return $this->_get_params($type);
		}
		return $this->_get_params('with','select', 'tables', 'where', 'group_by', 'having', 'order_by');
	}
}
}
#SqlComposer/Delete.php

namespace FoxORM\SqlComposer {
class Delete extends Where {
	protected $delete_from = [];
	protected $ignore = false;
	protected $order_by = [ ];
	protected $limit = null;
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = '', $execCallback=null, $dbType=null){
		$this->mainTable = $mainTable;
		$this->quoteCharacter = $quoteCharacter;
		$this->tablePrefix = $tablePrefix;
		$this->execCallback = $execCallback;
		$this->dbType = $dbType;
		if($this->mainTable)
			$this->delete_from($this->mainTable);
	}
	function delete_from($table) {
		$this->delete_from = array_merge($this->delete_from, (array)$table);
		return $this;
	}
	function using($table,  array $params = null) {
		return $this->add_table($table, $params);
	}
	function orderBy($order_by) {
		$this->order_by = array_merge($this->order_by, (array)$order_by);
		return $this;
	}
	function limit($limit) {
		$this->limit = $limit;
		return $this;
	}
	function ignore($ignore = true) {
		$this->ignore = $ignore;
		return $this;
	}
	function render() {
		$ignore = $this->ignore?'IGNORE':'';
		$delete_from = implode(", ", $this->delete_from);
		$using = empty($this->tables) ? "" : "\nUSING " . implode("\n\t", $this->tables);
		$where = $this->_render_where();
		$order_by = empty($this->order_by) ? "" : "\nORDER BY " . implode(", ", $this->order_by);
		$limit = !isset($this->limit) ? "" : "\nLIMIT " . $this->limit;
		return "DELETE {$ignore} FROM {$delete_from} {$using} \nWHERE {$where} {$order_by} {$limit}";
	}
	function getParams() {
		return $this->_get_params('tables', 'using', 'where');
	}
}
}
#SqlComposer/Update.php

namespace FoxORM\SqlComposer {
class Update extends Where {
	protected $set = [];
	protected $order_by = [];
	protected $limit = null;
	protected $ignore = false;
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = '', $execCallback=null, $dbType=null){
		parent::__construct($mainTable,$quoteCharacter, $tablePrefix, $execCallback, $dbType);
	}
	function update($table) {
		$this->add_table($table);
		return $this;
	}
	function set($set,  $params = null) {
		$set = (array)$set;
		if(self::is_assoc($set)) {
			foreach($set as $col => $val)
				$this->set[] = "{$col}=?";
			$params = array_values($set);
		}
		else{
			$this->set = array_merge($this->set, $set);
		}
		$this->_add_params('set', $params);
		return $this;
	}
	function orderBy($order_by) {
		$this->order_by = array_merge($this->order_by, (array)$order_by);
		return $this;
	}
	function limit($limit) {
		$this->limit = $limit;
		return $this;
	}
	function ignore($ignore = true) {
		$this->ignore = $ignore;
		return $this;
	}
	function render(){
		
		$ignore = '';
		if($this->ignore){
			if($this->dbType=='sqlite'){
				$ignore .= 'OR ';
			}
			$ignore .= 'IGNORE';
		}
		
		$tables = implode("\n\t", $this->tables);
		$set = "\nSET " . implode(', ', $this->set);
		$where = $this->_render_where();
		$order_by = empty($this->order_by) ? '' : "\nORDER BY " . implode(', ', $this->order_by);
		$limit = isset($this->limit) ? "\nLIMIT {$this->limit}" : '';
		return "UPDATE {$ignore} {$tables} {$set} {$where} {$order_by} {$limit}";
	}
	function getParams() {
		return $this->_get_params('set', 'where');
	}
}
}
#SqlComposer/Base.php

namespace FoxORM\SqlComposer {
abstract class Base {
	protected static $operators = ['>','>=','<','<=','=','!=','between','in'];
	private static $__apiProp = [
		'select'=>'columns',
		'join'=>'tables',
		'from'=>'tables',
	];
	protected $columns = [];
	protected $tables = [];
	protected $params = [];
	protected $paramsAssoc = [];
	protected $quoteCharacter;
	protected $tablePrefix;
	protected $mainTable;	
	protected $execCallback;	
	protected $dbType;	
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = '', $execCallback=null, $dbType=null){
		$this->mainTable = $mainTable;
		$this->quoteCharacter = $quoteCharacter;
		$this->tablePrefix = $tablePrefix;
		$this->execCallback = $execCallback;
		$this->dbType = $dbType;
		if($this->mainTable)
			$this->from($this->mainTable);
	}
	function getMainTable(){
		return $this->mainTable;
	}
	function debug() {
		return $this->getQuery() . "\n\n" . print_r($this->getParams(), true);
	}
	function quote($v){
		if($v=='*')
			return $v;
		return $this->quoteCharacter.$this->unQuote($v).$this->quoteCharacter;
	}
	function unQuote($v){
		return trim($v,$this->quoteCharacter);
	}
	function getQuery($removeUnbinded=true){
		$q = $this->render($removeUnbinded);
		$q = str_replace('{#prefix}',$this->tablePrefix,$q);
		return $q;
	}
	function __get($k){
		if(isset(self::$__apiProp[$k]))
			$k = self::$__apiProp[$k];
		if(property_exists($this,$k))
			return $this->$k;
	}
	function hasColumn(){
		return !empty($this->columns);
	}
	function getColumn(){
		return $this->columns;
	}
	function hasTable(){
		return !empty($this->tables);
	}
	function getTable(){
		return $this->tables;
	}
	function hasJoin(){
		foreach($this->tables as $table){
			if(is_array($table))
				return true;
		}
		return false;
	}
	function getJoin(){
		$joins = [];
		foreach($this->tables as $table){
			if(is_array($table))
				$joins[] = $table;
		}
		return $joins;
	}
	function hasFrom(){
		foreach($this->tables as $table){
			if(!is_array($table))
				return true;
		}
		return false;
	}
	function getFrom(){
		$froms = [];
		foreach($this->tables as $table){
			if(!is_array($table))
				$froms[] = $table;
		}
		return $froms;
	}
	function esc($v){
		if(strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($v);
		return $v;
	}
	function formatColumnName($v){
		if($this->mainTable&&strpos($v,'(')===false&&strpos($v,')')===false&&strpos($v,' as ')===false&&strpos($v,'.')===false)
			$v = $this->quote($this->tablePrefix.$this->mainTable).'.'.$this->quote($v);
		return $v;
	}
	function formatTableName($t){
		if(strpos($t,'(')===false&&strpos($t,')')===false&&strpos($t,' ')===false&&strpos($t,$this->quoteCharacter)===false)
			$t = $this->quote($this->tablePrefix.$t);
		return $t;
	}
	function add_table($table,  array $params = null, $for = null){
		if($for){
			$i = array_search($for,$this->tables);
			if($i===false){
				$this->tables[] = $for;
				$i = count($this->tables)-1;
			}
			$c = count($this->tables)-1;
			$and = false;
			while($i++<$c){
				if(!(is_array($this->tables[$i])&&strtoupper(rtrim(substr($this->tables[$i][0],0,3)))=='ON'))
					break;
				elseif(!$and)
					$and = true;
			}
			if(is_array($table))
				$r = &$table[0];
			else
				$r = &$table;
			if($and){
				if(strtoupper(rtrim(substr($r,0,4)))!='AND')
					$r = 'AND '.$r;
			}
			else{
				if(strtoupper(rtrim(substr($r,0,3)))!='ON')
					$r = 'ON '.$r;
			}
			array_splice($this->tables, $i, 0, [$table]);
			
			$indexedParams = $params;
			$params = [];
			foreach($indexedParams as $k=>$v){
				if(is_integer($k)){
					$k = uniqid('join');
					$r = self::str_replace_once('?',':'.$k,(string)$r);
				}
				$params[$k] = $v;
			}
			
		}
		else{
			if(!empty($params)||!in_array($table,$this->tables)){
				$this->tables[] = $table;
			}
		}
		$this->_add_params('tables', $params);
		return $this;
	}
	function tableJoin($table,$join,array $params = null) {
		return $this->add_table([$table,$join], $params);
	}
	function joinAdd($join,array $params = null, $for = null) {
		return $this->add_table((array)$join, $params, (array)$for);
	}
	function join($join,array $params = null){
		return $this->joinAdd('JOIN '.$this->formatTableName($join),$params);
	}
	function joinLeft($join,array $params = null){
		return $this->joinAdd('LEFT JOIN '.$this->formatTableName($join),$params);
	}
	function joinRight($join,array $params = null){
		return $this->joinAdd('RIGHT JOIN '.$this->formatTableName($join),$params);
	}
	function joinOn($join,array $params = null){
		return $this->joinAdd('ON '.$join,$params);
	}
	function joinOnFor($join,$for,array $params = null){
		return $this->joinAdd($join,$params, 'JOIN '.$this->formatTableName($for));
	}
	function from($table,  array $params = null) {
		return $this->add_table($table, $params);
	}
	function unTableJoin($table=null,$join=null,$params=null){
		$this->remove_property('tables',[$table,$join],$params);
		return $this;
	}
	function unJoin($join=null,$params=null){
		$this->remove_property('tables',$join,$params);
		$this->add_table($this->mainTable);
		return $this;
	}
	function unFrom($table=null,$params=null){
		$this->remove_property('tables',$table,$params);
		return $this;
	}
	protected function _add_params($clause,  array $params = null) {
		if (isset($params)){
			if (!isset($this->params[$clause]))
				$this->params[$clause] = [];
			$addParams = [];
			foreach($params as $k=>$v){
				if(is_integer($k))
					$addParams[] = $v;
				else
					$this->set($k,$v);
			}
			if(!empty($addParams))
				$this->params[$clause][] = $addParams;
		}
		return $this;
	}
	protected function _get_params($order) {
		if (!is_array($order))
			$order = func_get_args();
		$params = [];
		foreach ($order as $clause) {
			if(empty($this->params[$clause]))
				continue;
			foreach($this->params[$clause] as $p)
				$params = array_merge($params, $p);
		}
		foreach($this->paramsAssoc as $k=>$v)
			$params[$k] = $v;
		return $params;
	}
	function set($k,$v){
		$k = ':'.ltrim($k,':');
		$this->paramsAssoc[$k] = $v;
	}
	function get($k){
		return $this->paramsAssoc[$k];
	}
	function remove_property($k,$v=null,$params=null,$once=null){
		if($params===false){
			$params = null;
			$once = true;
		}
		$r = null;
		foreach(array_keys($this->$k) as $i){
			if(!isset($v)||$this->{$k}[$i]==$v){
				$found = $this->_remove_params($k,$i,$params);
				if(!isset($params)||$found)
					unset($this->{$k}[$i]);
				if((isset($params)&&$found)||(!isset($params)&&$once)){
					$r = $i;
					break;
				}
			}
		}
		if(isset($this->params[$k]))
			$this->params[$k] = array_values($this->params[$k]);
		$this->{$k} = array_values($this->{$k});
		return $r;
	}
	function removeUnbinded($a){
		foreach(array_keys($a) as $k){
			if(is_array($a[$k]))
				continue;
			$e = str_replace('::','',$a[$k]);
			if(strpos($e,':')!==false){
				preg_match_all('/:((?:[a-z][a-z0-9_]*))/is',$e,$match);
				if(isset($match[0])){
					foreach($match[0] as $m){
						if(!isset($this->paramsAssoc[$m])){
							unset($a[$k]);
							break;
						}
					}
				}
			}
		}
		return $a;
	}
	private function _remove_params($clause,$i=null,$params=null){
		if($clause=='columns')
			$clause = 'select';
		if(isset($this->params[$clause])){
			if(!isset($i))
				$i = count($this->params[$clause])-1;
			if(isset($this->params[$clause][$i])&&(!isset($params)||$params==$this->params[$clause][$i])){
				unset($this->params[$clause][$i]);
				return true;
			}
		}
	}
	static function render_bool_expr(array $expression){
		$str = "";
		$stack = [ ];
		$op = "AND";
		$first = true;
		foreach ($expression as $expr) {
			if (is_array($expr)) {
				if ($expr[0] == '(') {
					array_push($stack, $op);
					if (!$first)
						$str .= " " . $op;
					if ($expr[1] == "NOT") {
						$str .= " NOT";
					} else {
						$str .= " (";
						$op = $expr[1];
					}
					$first = true;
					continue;
				}
				elseif ($expr[0] == ')') {
					$op = array_pop($stack);
					$str .= " )";
				}
				else{
					if (!$first)
						$str .= " " . $op;
					$str .= " (" . implode('',$expr) . ")";
				}
			}
			else {
				if (!$first)
					$str .= " " . $op;
				$str .= " (" . $expr . ")";
			}
			$first = false;
		}
		$str .= str_repeat(" )", count($stack));
		return $str;
	}
	abstract function render();
	function __toString(){
		$str = $this->getQuery();
		return $str;
	}
	function getClone(){
		return clone $this;
	}

	static function in($sql, array $params){
		$given_params = $params;
		$placeholders = [ ];
		$params = [];
		foreach($given_params as $p){
			$placeholders[] = "?";
			$params[] = $p;
		}
		$placeholders = implode(", ", $placeholders);
		$sql = str_replace("?", $placeholders, $sql);
		return [$sql, $params];
	}
	static function is_assoc($array){
		return (array_keys($array) !== range(0, count($array) - 1));
	}
	static function isValidOperator($op){
		return in_array($op, self::$operators);
	}
	static function applyOperator($column, $op, array $params=null){
		switch ($op) {
			case '>': case '>=':
			case '<': case '<=':
			case '=': case '!=':
				return ["{$column} {$op} ?", $params];
			case 'in':
				return self::in("{$column} in (?)", $params);
			case 'between':
				$sql = "{$column} between ";
				$p = array_shift($params);
				$sql .= "?";
				array_push($params, $p);
				$sql .= " and ";
				$p = array_shift($params);
				$sql .= "?";
				array_push($params, $p);
				return [$sql, $params];
			default:
				throw new Exception('Invalid operator: '.$op);
		}
	}
	static function str_replace_once($search, $replace, $subject) {
		$firstChar = strpos($subject, $search);
		if($firstChar !== false) {
			$beforeStr = substr($subject,0,$firstChar);
			$afterStr = substr($subject, $firstChar + strlen($search));
			return $beforeStr.$replace.$afterStr;
		}
		else {
			return $subject;
		}
	}
	function exec(array $mergeParams=null){
		return $this->execute($mergeParams);
	}
	function execute(array $mergeParams=null){
		$params = $this->getParams();
		if(isset($mergeParams)){
			$params = array_merge($params,$mergeParams);
		}
		return call_user_func_array($this->execCallback,[$this->getQuery(),$params]);
	}
}
}
#SqlComposer/Insert.php

namespace FoxORM\SqlComposer {
use BadMethodCallException;
class Insert extends Base {
	protected $ignore = false;
	protected $select;
	protected $on_duplicate = [];
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = '', $execCallback=null, $dbType=null){
		parent::__construct($mainTable,$quoteCharacter, $tablePrefix, $execCallback, $dbType);
	}
	function insert_into($table) {
		return $this->into($table);
	}
	function into($table) {
		$this->add_table($table);
		return $this;
	}
	function ignore($ignore = true) {
		$this->ignore = $ignore;
		return $this;
	}
	function columns($column) {
		$this->columns = array_merge($this->columns, (array)$column);
		return $this;
	}
	function values( array $values) {
		if(isset($this->select))
			throw new BadMethodCallException("Cannot use 'INSERT INTO ... VALUES' when a SELECT is already set!");
		return $this->_add_params('values', $values);
	}
	function select($select = null,  array $params = null) {
		if(isset($this->params['values']))
			throw new BadMethodCallException("Cannot use 'INSERT INTO ... SELECT' when values are already set!");
		if (!isset($this->select)) 
			$this->select = new Select();
		if (isset($select))
			$this->select->select($select, $params);
		return $this->select;
	}
	function onDuplicate($update,  array $params = null) {
		$this->on_duplicate = array_merge($this->on_duplicate, (array)$update);
		$this->_add_params('on_duplicate', $params);
		return $this;
	}
	function render() {
		$ignore = '';
		if($this->ignore){
			if($this->dbType=='sqlite'){
				$ignore .= 'OR ';
			}
			$ignore .= 'IGNORE';
		}
		
		$table = $this->tables[0];
		
		$columns = $this->_get_columns();
		$columns = empty($columns) ? "" : "(" . implode(", ", $columns) . ")";
		if(isset($this->select)){
			$values = "\n" . $this->select->render();
		}
		else{
			$placeholders = "(" . implode(", ", array_fill(0, $this->_num_columns(), "?")) . ")";
			$num_values = count($this->params['values']);
			$values = "\nVALUES " . implode(", ", array_fill(0, $num_values, $placeholders));
		}
		$on_duplicate =	(empty($this->on_duplicate)) ? "" : "\nON DUPLICATE KEY UPDATE " . implode(", ", $this->on_duplicate);
		return "INSERT {$ignore} INTO {$table} {$columns} {$values} {$on_duplicate}";
	}
	function getParams() {
		if (isset($this->select)) {
			$params = $this->select->getParams();
		}
		else{
			$params = [ ];
			$columns = $this->_get_columns();
			$num_cols = $this->_num_columns();
			foreach ($this->params["values"] as $values) {
				if (self::is_assoc($values)) {
					foreach ($columns as $col)
						$params[] = $values[$col];
				}
				else{
					$params = array_merge($params, array_slice($values, 0, $num_cols));
				}
			}

		}
		return array_merge($params, (array)$this->params['on_duplicate']);
	}
	protected function _get_columns() {
		if (!empty($this->columns)) {
			return $this->columns;
		}
		elseif (self::is_assoc($this->params['values'][0])) {
			return array_keys($this->params['values'][0]);
		}
		else{
			return [];
		}
	}
	protected function _num_columns() {
		if(!empty($this->columns)){
			return count($this->columns);
		}
		else{
			return count($this->params['values'][0]);
		}
	}
	function __clone(){
		if(isset($this->select))
			$this->select = clone $this->select;
	}
}
}
#SqlComposer/Replace.php

namespace FoxORM\SqlComposer {
use BadMethodCallException;
class Replace extends Base {
	protected $select;
	function __construct($mainTable = null,$quoteCharacter = '"', $tablePrefix = '', $execCallback=null, $dbType=null){
		parent::__construct($mainTable,$quoteCharacter, $tablePrefix, $execCallback, $dbType);
	}
	function replace_into($table) {
		return $this->into($table);
	}
	function into($table) {
		$this->add_table($table);
		return $this;
	}
	function columns($column) {
		$this->columns = array_merge($this->columns, (array)$column);
		return $this;
	}
	function values( array $values) {
		if (isset($this->select)) throw new BadMethodCallException("Cannot use 'REPLACE INTO ... VALUES' when a SELECT is already set!");

		return $this->_add_params('values', $values);
	}
	function select($select = null,  array $params = null) {
		if (isset($this->params['values'])) throw new BadMethodCallException("Cannot use 'REPLACE INTO ... SELECT' when values are already set!");

		if (!isset($this->select)) {
			$this->select = new Select();
		}

		return $this->select->select($select, $params);
	}
	function render() {
		$table = $this->tables[0];

		$columns = $this->_get_columns();
		$columns = empty($columns) ? "" : "(" . implode(", ", $columns) . ")";

		if (isset($this->select)) {
			$values = "\n" . $this->select->render();
		} else {
			$placeholders = "(" . implode(", ", array_fill(0, $this->_num_columns(), "?")) . ")";

			$num_values = count($this->params['values']);

			$values = "\nVALUES " . implode(", ", array_fill(0, $num_values, $placeholders));
		}

		return "REPLACE INTO {$table} {$columns} {$values}";
	}
	function getParams() {

		if (isset($this->select)) {

			$params = $this->select->getParams();

		} else {

			$params = [ ];
			$columns = $this->_get_columns();
			$num_cols = $this->_num_columns();
			foreach ($this->params["values"] as $values) {
				if (self::is_assoc($values)) {
					foreach ($columns as $col) $params[] = $values[$col];
				} else {
					$params = array_merge($params, array_slice($values, 0, $num_cols));
				}
			}
		}
		return $params;
	}
	protected function _get_columns() {
		if (!empty($this->columns)) {
			return $this->columns;
		}
		elseif (self::is_assoc($this->params['values'][0])) {
			return array_keys($this->params['values'][0]);
		}
		else {
			return [];
		}
	}
	protected function _num_columns() {
		if (!empty($this->columns)) {
			return count($this->columns);
		} else {
			return count($this->params['values'][0]);
		}
	}

}
}
#DataTable.php

namespace FoxORM {
use FoxORM\Helper\Pagination;
use FoxORM\Std\Cast;
use FoxORM\Std\ArrayIterator;
use BadMethodCallException;
abstract class DataTable implements \ArrayAccess,\Iterator,\Countable,\JsonSerializable{
	private static $defaultEvents = [
		'beforeRecursive',
		'beforeValidate',
		'afterValidate',
		'beforePut',
		'beforeCreate',
		'beforeRead',
		'beforeUpdate',
		'beforeDelete',
		'afterPut',
		'afterCreate',
		'afterRead',
		'afterUpdate',
		'afterDelete',
		'afterRecursive',
		'serializeColumns',
		'unserializeColumns',
	];
	private $events = [];	
	protected $name;
	protected $dataSource;
	protected $data = [];
	protected $useCache = false;
	protected $counterCall;
	protected $isClone;
	protected $tableWrapper;
	protected $isOptional = false;
	
	function __construct($name,$dataSource){
		
		if($p=strpos($name,':')){
			$tableWrapper = substr($name,$p+1);
			$name = substr($name,0,$p);
		}
		else{
			$tableWrapper = null;
		}
		
		$this->name = $name;
		$this->dataSource = $dataSource;
		$this->tableWrapper = $dataSource->tableWrapperFactory($name,$this,$tableWrapper);
		
		foreach(self::$defaultEvents as $event)
			$this->on($event);
	}
	function getTableName(){
		return $this->name;
	}
	function getPrimaryKey(){
		return $this->dataSource->getTablePrimaryKey($this->name);
	}
	function getUniqTextKey(){
		return $this->dataSource->getTableUniqTextKey($this->name);
	}
	function getDataSource(){
		return $this->dataSource;
	}
	function setUniqTextKey($uniqTextKey='uniq'){
		$this->dataSource->setTableUniqTextKey($this->name,$uniqTextKey);
	}
	function setPrimaryKey($primaryKey='id'){
		$this->dataSource->setTablePrimaryKey($this->name,$primaryKey);
	}
	function offsetExists($id){
		return (bool)$this->readId($id);
	}
	function offsetGet($id){
		if(!$this->useCache||!array_key_exists($id,$this->data))
			$row = $this->readRow($id);
		else
			$row = $this->data[$id];
		if($this->useCache)
			$this->data[$id] = $row;
		return $row;
	}
	function offsetSet($id,$obj){
		if(is_array($obj)){
			$tmp = $obj;
			$obj = $this->dataSource->entityFactory($this->name);
			foreach($tmp as $k=>$v)
				$obj->$k = $v;
			unset($tmp);
		}
		if(!$id){
			$id = $this->putRow($obj);
			$obj->{$this->getPrimaryKey()} = $id;
		}
		elseif($obj===null){
			return $this->offsetUnset($id);
		}
		else{
			$this->putRow($obj,$id);
		}
		if($this->useCache)
			$this->data[$id] = $obj;
		return $obj;
	}
	function offsetUnset($id){
		if(is_array($id)){
			$id = $this->entityFactory($id);
		}
		if(Cast::isScalar($id)){
			$id = Cast::scalar($id);
		}
		$offset = is_object($id)?$id->{$this->getPrimaryKey()}:$id;
		if(isset($this->data[$offset]))
			unset($this->data[$offset]);
		return $this->deleteRow($id);
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
		if($this->counterCall)
			return call_user_func($this->counterCall,$this);
		else
			return count($this->data);
	}
	function paginate($page,$limit=2,$href='',$prefix='?page=',$maxCols=6){
		$pagination = new Pagination();
		$pagination->setLimit($limit);
		$pagination->setMaxCols($maxCols);
		$pagination->setHref($href);
		$pagination->setPrefix($prefix);
		$pagination->setCount($this->count());
		$pagination->setPage($page);
		if($pagination->resolve($page)){
			$this->limit($pagination->limit);
			$this->offset($pagination->offset);
			return $pagination;
		}
	}
	function setCache($enable){
		$this->useCache = (bool)$enable;
	}
	function resetCache(){
		$this->data = [];
	}
	function readId($id){
		return $this->dataSource->readId($this->name,$id,$this->getPrimaryKey(),$this->getUniqTextKey());
	}
	function readRow($id){
		return $this->dataSource->readRow($this->name,$id,$this->getPrimaryKey(),$this->getUniqTextKey());
	}
	function putRow($obj,$id=null){
		return $this->dataSource->putRow($this->name,$obj,$id,$this->getPrimaryKey(),$this->getUniqTextKey());
	}
	function deleteRow($id){
		return $this->dataSource->deleteRow($this->name,$id,$this->getPrimaryKey(),$this->getUniqTextKey());
	}
	
	function loadOne($obj){
		return $obj->{'_one_'.$this->name} = $this->one($obj)->getRow();
	}
	function loadMany($obj){
		return $obj->{'_many_'.$this->name} = $this->many($obj)->getAllIterator();
	}
	function loadMany2many($obj,$via=null){
		return $obj->{'_many2many_'.$this->name} = $this->many2many($obj,$via)->getAllIterator();
	}
	function one($obj){
		return $this->dataSource->many2one($obj,$this->name);
	}
	function many($obj){
		$many = $this->dataSource->one2many($obj,$this->name);
		$many = new ArrayIterator($many);
		return $many;
	}
	function many2many($obj,$via=null){
		$many = $this->dataSource->many2many($obj,$this->name,$via);
		$many = new ArrayIterator($many);
		return $many;
	}
	function many2manyLink($obj,$via=null,$viaFk=null){
		$many = $this->dataSource->many2manyLink($obj,$this->name,$via,$viaFk);
		$many = new ArrayIterator($many);
		return $many;
	}
	
	abstract function getAll();
	abstract function getRow();
	abstract function getCol();
	abstract function getCell();
	
	function getAllIterator(){
		return new ArrayIterator($this->getAll());
	}
	
	function on($event,$call=null,$index=0,$prepend=false){
		if($index===true){
			$prepend = true;
			$index = 0;
		}
		if(is_null($call))
			$call = $event;
		if(!isset($this->events[$event][$index]))
			$this->events[$event][$index] = [];
		if($prepend)
			array_unshift($this->events[$event][$index],$call);
		else
			$this->events[$event][$index][] = $call;
		return $this;
	}
	function off($event,$call=null,$index=0){
		if(func_num_args()===1){
			if(isset($this->events[$event]))
				unset($this->events[$event]);
		}
		elseif(func_num_args()===2){
			foreach($this->events[$event] as $index){
				if(false!==$i=array_search($call,$this->events[$event][$index],true)){
					unset($this->events[$event][$index][$i]);
				}
			}
		}
		elseif(isset($this->events[$event][$index])){
			if(!$call)
				unset($this->events[$event][$index]);
			elseif(false!==$i=array_search($call,$this->events[$event][$index],true))
				unset($this->events[$event][$index][$i]);
		}
		return $this;
	}
	function trigger($event, $row, $recursive=false, $flow=null){
		if(isset($this->events[$event]))
			$this->dataSource->triggerExec($this->events[$event], $this->name, $event, $row, $recursive, $flow);
		return $this;
	}
	function triggerTableWrapper($method,$args){
		if(!$this->tableWrapper) return;
		$sysmethod = '_'.$method;
		if(method_exists($this->tableWrapper,$sysmethod)){
			call_user_func_array([$this->tableWrapper,$sysmethod],$args);
		}
		if(method_exists($this->tableWrapper,$method)){
			call_user_func_array([$this->tableWrapper,$method],$args);
		}
	}
	static function setDefaultEvents(array $events){
		self::$defaultEvents = $events;
	}
	static function getDefaultEvents(){
		return self::$defaultEvents;
	}
	function setCounter($call){
		$this->counterCall = $call;
	}
	
	function getClone(){
		return clone $this;
	}
	function __clone(){
		$this->isClone = true;
		if($this->tableWrapper){
			$this->tableWrapper = clone $this->tableWrapper;
			$this->tableWrapper->_setDataTable($this);
		}
	}
	
	function __call($f,$args){
		if($this->tableWrapper&&method_exists($this->tableWrapper,$f)){
			return call_user_func_array([$this->tableWrapper,$f],$args);
		}
		throw new BadMethodCallException('Call to undefined method '.get_class($this).'->'.$f);
	}
	
	function jsonSerialize(){
		return $this->getAllIterator();
	}
	
	function entity($data=null,$filter=null,$reversedFilter=false){
		return $this->dataSource->entity($this->name,$data,$filter,$reversedFilter);
	}
	function newEntity($data=null,$filter=null,$reversedFilter=false){
		return $this->dataSource->newEntity($this->name,$data,$filter,$reversedFilter);
	}
	function entityFactory($data){
		return $this->dataSource->entityFactory($this->name,$data);
	}
	
	function create($mixed){
		return $this->offsetSet(null,$mixed);
	}
	function read($mixed){
		if(!is_scalar($mixed)){
			$pk = $this->getPrimaryKey();
			if(is_array($mixed)){
				$mixed = $mixed[$pk];
			}
			elseif(Cast::isScalar($mixed)){
				$mixed = Cast::scalar($mixed);
			}
			elseif(is_object($mixed)){
				$mixed = $mixed->$pk;
			}
		}
		return $this->offsetGet($mixed);
	}
	function update($mixed){
		if(func_num_args()<2){
			$pk = $this->getPrimaryKey();
			if(is_array($mixed)){
				$id = $mixed[$pk];
				$obj = $mixed;
			}
			elseif(Cast::isScalar($mixed)){
				$id = Cast::scalar($mixed);
				$obj = $this->read($id);
			}
			elseif(is_object($mixed)){
				$id = $mixed->$pk;
				$obj = $mixed;
			}
			else{
				$id = $mixed;
				$obj = $this->read($id);
			}
		}
		else{
			list($id,$obj) = func_get_args();
		}
		return $this->offsetSet($id,$obj);
	}
	function delete($mixed){
		if(!is_scalar($mixed)){
			$pk = $this->getPrimaryKey();
			if(is_array($mixed)){
				$mixed = $mixed[$pk];
			}
			elseif(Cast::isScalar($mixed)){
				$mixed = Cast::scalar($mixed);
			}
			elseif(is_object($mixed)){
				$mixed = $mixed->$pk;
			}
		}
		return $this->offsetUnset($mixed);
	}
	function put($obj){
		return $this->offsetSet(null,$obj);
	}
	function isOptional($b=true){
		if(!$this->isClone){
			return $this->getClone()->isOptional($b);
		}
		$this->isOptional = $b;
		return $this;
	}
	function getColumns(){
		return $this->dataSource->getColumns($this->name);
	}
	function getColumnNames(){
		return $this->dataSource->getColumnNames($this->name);
	}
	function getArray(){
		$a = [];
		foreach($this as $row){
			$a[] = $row;
		}
		return $a;
	}
	function deleteMany($type,$id){
		return $this->dataSource->deleteMany($this->name,$type,$id);
	}
}
}
#DataTable/SQL.php

namespace FoxORM\DataTable {
use FoxORM\Std\Cast;
use FoxORM\DataTable;
use FoxORM\SqlComposer\Select;
use FoxORM\SqlComposer\Insert;
use FoxORM\SqlComposer\Update;
use FoxORM\SqlComposer\Replace;
use FoxORM\SqlComposer\Delete;
use FoxORM\Entity\StateFollower;
use FoxORM\DataSource;
use FoxORM\DataSource\SQL as DataSourceSQL;
use BadMethodCallException;
class SQL extends DataTable{
	private $stmt;
	private $row;
	protected $select;
	protected $hasSelectRelational;
	protected $tablePrefix;
	protected $quoteCharacter;
	function __construct($name,DataSourceSQL $dataSource){
		parent::__construct($name,$dataSource);
		$this->tablePrefix = $dataSource->getTablePrefix();
		$this->quoteCharacter = $dataSource->getQuoteCharacter();
		$this->select = $this->selectQuery();
		
		$this->select->select($this->getLoadColumns());
		$readSnippet = $this->dataSource->getReadSnippetArray($name);
		if(!empty($readSnippet)){
			$this->select->select($readSnippet);
		}
	}
	function getLoadColumns(){
		if($this->tableWrapper&&method_exists($this->tableWrapper,__FUNCTION__))
			return $this->tableWrapper->getLoadColumns();
		return [$this->quote($this->name).'.*'];
	}
	function getLoadColumnsSnippet(){
		if($this->tableWrapper&&method_exists($this->tableWrapper,__FUNCTION__))
			return $this->tableWrapper->getLoadColumnsSnippet();
		return $this->quote($this->name).'.*';
	}
	function exists(){
		return $this->dataSource->tableExists($this->name);
	}
	function fetch(){
		return $this->dataSource->fetch($this->select->getQuery(),$this->select->getParams());
	}
	
	function getAll(){
		return $this->getClean(__FUNCTION__);
	}
	function getRow(){
		return $this->getClean(__FUNCTION__);
	}
	function getCol(){
		return $this->getClean(__FUNCTION__);
	}
	function getCell($column=null,$selectMain=null){
		$o = $this;
		if($column){
			$o = clone $o;
			$o->unSelect();
			if($selectMain){
				$o->select($column);
			}
			else{
				$o->selectMain($column);
			}
		}
		return $o->getClean(__FUNCTION__);
	}
	
	function tryGetAll(){
		return $this->getClean(__FUNCTION__);
	}
	function tryGetRow(){
		return $this->getClean(__FUNCTION__);
	}
	function tryGetCol(){
		return $this->getClean(__FUNCTION__);
	}
	function tryGetCell(){
		return $this->getClean(__FUNCTION__);
	}
	
	protected function getClean($method){
		$select = $this->select;
		$addNull = [];
		foreach($select->getSelect() as $v){
			if($this->isSimpleColumnName($v,true)&&!$this->columnExists($v)){
				$select->unSelect($v);
				$addNull[] = $v;
			}
		}
		foreach($select->getWhere() as $v){
			$col = is_array($v)?$v[0]:$v;
			if($this->isSimpleColumnName($col,true)&&!$this->columnExists($col)){
				//$select->unWhere($v);
				$select->replaceWhere($v,'NULL');
			}
		}
		$emptySelect = !count($select->getSelect());
		if(!$emptySelect){
			$all = $this->dataSource->$method($this->select->getQuery(),$this->select->getParams());
		}
		switch($method){
			case 'getAll':
			case 'tryGetAll':					
				if($emptySelect){
					$all = [];
				}
				else{
					if(!empty($addNull)){
						foreach($all as &$row){
							foreach($addNull as $add){
								$row[$add] = null;
							}
						}
					}
					$all = $this->collectionToEntities($all);
				}
			break;
			case 'getRow':
			case 'tryGetRow':					
				if($emptySelect){
					$all = [];
				}
				else{
					if(!empty($addNull)){
						foreach($addNull as $add){
							$all[$add] = null;
						}
					}
					$all = $this->collectionToEntity($all);
				}
			break;
			case 'getCol':
			case 'tryGetCol':
				if($emptySelect){
					$all = [];
				}
				else if(!empty($addNull)){
					foreach($addNull as $add){
						$all[$add] = null;
					}
				}
			break;
			case 'tryGetCell':
			case 'getCell':
				if($emptySelect){
					$all = null;
				}
			break;
		}
		return $all;
	}
	
	function collectionToEntities($all){
		$table = [];
		if($this->hasSelectRelational)
			$all = $this->dataSource->explodeAggTable($all);
		foreach($all as $row){
			$row = $this->dataSource->arrayToEntity($row,$this->name);
			if(isset($row->{$this->getPrimaryKey()}))
				$table[$row->{$this->getPrimaryKey()}] = $row;
			else
				$table[] = $row;
		}
		return $table;
	}
	function collectionToEntity($row){
		if($this->hasSelectRelational)
			$row = $this->dataSource->explodeAgg($row);
		if($row)
			$row = $this->dataSource->arrayToEntity($row,$this->name);
		return $row;
	}
	
	function rewind(){
		if(!$this->exists())
			return;
		$this->stmt = $this->fetch();
		$this->next();
	}
	function current(){
		return $this->row;
	}
	function key(){
		if($this->row)
			return $this->row->{$this->getPrimaryKey()};
	}
	function valid(){
		return (bool)$this->row;
	}
	function next(){
		$this->row = $this->dataSource->entityFactory($this->name);
		if($this->row instanceof StateFollower)
			$this->row->__readingState(true);
		$this->trigger('beforeRead',$this->row);
		$row = $this->stmt->fetch();
		if($this->dataSource->debugLevel(DataSource::DEBUG_RESULT)){
			$this->dataSource->getLogger()->logResult($row);
		}
		if($row){
			if($this->hasSelectRelational){
				$row = $this->dataSource->explodeAgg($row);
			}
			foreach($row as $k=>$v){
				$this->row->$k = $v;
			}
			if($this->useCache){
				$pk = isset($this->row->{$this->getPrimaryKey()})?$this->row->{$this->getPrimaryKey()}:count($this->data)+1;
				$this->data[$pk] = $this->row;
			}
		}
		$this->trigger('afterRead',$this->row);
		$this->trigger('unserializeColumns',$this->row);
		if($this->row instanceof StateFollower)
			$this->row->__readingState(false);
		if(!$row){
			$this->row = null;
		}
	}
	function count(){
		if($this->counterCall)
			return call_user_func($this->counterCall,$this);
		else
			return $this->countSimple();
	}
	function countSimple(){
		if(!$this->exists())
			return;
		$select = $this->select
			->getClone()
			->unOrderBy()
			->unSelect()
			->select('COUNT(*)')
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function countNested(){
		if(!$this->exists())
			return;
		$select = $this->selectQuery();
		$queryCount = $this->select
			->getClone()
			->unOrderBy()
			->unSelect()
			->select($this->getPrimaryKey())
		;
		$select
			->select('COUNT(*)')
			->from('('.$queryCount->getQuery().') as TMP_count',$queryCount->getParams())
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function countAll(){
		if(!$this->exists())
			return;
		$select = $this->selectQuery();
		$select
			->select('COUNT(*)')
			->from($this->name)
		;
		return (int)$this->dataSource->getCell($select->getQuery(),$select->getParams());
	}
	function createSelect(){ //deprecated
		return new Select($this->name, $this->quoteCharacter, $this->tablePrefix, [$this->dataSource,'getAll'], $this->dataSource->getType());
	}
	function selectQuery(){
		return new Select($this->name, $this->quoteCharacter, $this->tablePrefix, [$this->dataSource,'getAll'], $this->dataSource->getType());
	}
	function insertQuery(){
		return new Insert($this->name, $this->quoteCharacter, $this->tablePrefix, [$this->dataSource,'getAll'], $this->dataSource->getType());
	}
	function updateQuery(){
		return new Insert($this->name, $this->quoteCharacter, $this->tablePrefix, [$this->dataSource,'getAll'], $this->dataSource->getType());
	}
	function replaceQuery(){
		return new Replace($this->name, $this->quoteCharacter, $this->tablePrefix, [$this->dataSource,'getAll'], $this->dataSource->getType());
	}
	function deleteQuery(){
		return new Delete($this->name, $this->quoteCharacter, $this->tablePrefix, [$this->dataSource,'getAll'], $this->dataSource->getType());
	}
	function __clone(){
		parent::__clone();
		if(isset($this->select))
			$this->select = clone $this->select;
	}
	
	function selectMany2many($select,$colAlias=null){
		return $this->selectRelational('<>'.$select,$colAlias);
	}
	function selectMany($select,$colAlias=null){
		return $this->selectRelational('>'.$select,$colAlias);
	}
	function selectOne($select,$colAlias=null){
		return $this->selectRelational('<'.$select,$colAlias);
	}
	function prefixTable($table){
		return $this->dataSource->prefixTable($table);
	}
	function escTable($table){
		return $this->dataSource->escTable($table);
	}
	function processRelational($select,$colAlias=null,$autoSelectId=false){
		$selection = explode('~~',ltrim(str_replace(['<','>','<>','<~~~>','.'],['~~<~','~~>~','~~<>~','<>','~~.'],$select),'~'));
		$selection = array_reverse($selection);
		$column = ltrim(array_shift($selection),'.');
		$selection = array_map(function($v){
			return explode('~',$v);
		},$selection);
		$tmp = $selection;
		$selection = [];
		foreach($tmp as $i=>list($relation,$table)){
			if($relation=='<>'){
				$joinWith = isset($tmp[$i+1])?$tmp[$i+1][1]:$this->name;
				$relationTable = $this->dataSource->many2manyTableName($table, $joinWith);
				$selection[] = ['<',$table];
				$selection[] = ['>',$relationTable];
			}
			else{
				$selection[] = [$relation,$table];
			}
		}
		list($relation,$table) = array_shift($selection);
		$qTable = $table;
		$q = $this->quoteCharacter;
		$Q = new Select($table,$this->quoteCharacter,$this->tablePrefix);
		$agg = $this->dataSource->getAgg();
		$aggc = $this->dataSource->getAggCaster();
		$aggc = $this->dataSource->getAggCaster();
		$sep = $this->dataSource->getSeparator();
		$cc = $this->dataSource->getConcatenator();
		$Q->select("{$agg}( COALESCE(".$Q->formatColumnName($column)."{$aggc}, ''{$aggc}) {$sep} {$cc} )");
		$Q->from($table);
		$qPk = $pk = $this->dataSource[$table]->getPrimaryKey();
		
		$previousTable = $table;
		$previousRelation = $relation;
		$qRelation = $relation;
		$previousTablePk = $pk;
		$tableQ = $this->escTable($table);
		$pkQ = $Q->quote($pk);
		
		foreach($selection as list($relation,$table)){
			//list($table, $alias) = self::specialTypeAliasExtract($table,$superalias);
			
			$Q->join($table);
			$pk = $this->dataSource[$table]->getPrimaryKey();
			
			$tableQ = $this->escTable($table);
			$pkQ = $Q->quote($pk);
			$previousTableQ = $this->escTable($previousTable);
			$previousTablePkQ = $Q->quote($previousTablePk);
			
			if($previousRelation=='<'){
				$col1 = $Q->quote($previousTable.'_'.$previousTablePk);
				$col2 = $previousTablePkQ;
			}
			elseif($previousRelation=='>'){
				$col1 = $pkQ;
				$col2 = $Q->quote($table.'_'.$pk);
			}
			$Q->joinOn($tableQ.'.'.$col1.' = '.$previousTableQ.'.'.$col2);
			
			$previousTable = $table;
			$previousTablePk = $pk;
			$previousRelation = $relation;
		}
		
		if($relation=='<'){
			$Q->where($tableQ.'.'.$pkQ.' = '.$this->escTable($this->name).'.'.$Q->quote($table.'_'.$this->getPrimaryKey()));
		}
		elseif($relation=='>'){
			$Q->where($this->escTable($this->name).'.'.$pkQ.' = '.$tableQ.'.'.$Q->quote($this->name.'_'.$this->getPrimaryKey()));
		}
		
		
		$colAlias = $Q->quote($qTable.$qRelation.$column);
		$this->select("($Q) as $colAlias");
		if($autoSelectId&&$column!=$qPk){
			$Q2 = clone $Q;
			$Q2->unSelect();
			$Q2->select("{$agg}( COALESCE(".$Q->formatColumnName($qPk)."{$aggc}, ''{$aggc}) {$sep} {$cc} )");
			$colIdAlias = $Q->quote($qTable.$qRelation.$qPk);
			$this->select("($Q2) as $colIdAlias");
		}
	}
	/*
	static function specialTypeAliasExtract($type,&$superalias=null){
		$alias = null;
		if(($p=strpos($type,':'))!==false){
			if(isset($type[$p+1])&&$type[$p+1]==':'){
				$superalias = trim(substr($type,$p+2));
				$type = trim(substr($type,0,$p));
			}
			else{
				$alias = trim(substr($type,$p+1));
				$type = trim(substr($type,0,$p));
			}
		}
		return [$type,$alias?$alias:$type];
	}
	*/
	function hasSelectRelational(){
		return $this->hasSelectRelational;
	}
	function columnExists($col){
		return $this->dataSource->columnExists($this->name,$col);
	}
	
	function esc($v){
		return $this->dataSource->esc($v);
	}
	function quote($v){
		return $this->dataSource->quote($v);
	}
	function unQuote($v){
		return $this->dataSource->unQuote($v);
	}
	function isSimpleColumnName(&$val,$unQuote=false){
		$v = trim($val);
		if($unQuote){
			$v = $this->unQuote($v);
		}
		$ok = !preg_match('/[^a-z_\-0-9]/i', $v);
		if($ok){
			$val = $v;
		}
		return $ok;
	}
	function formatColumnName($v){
		if($this->name&&$this->isSimpleColumnName($v,true))
			$v = $this->quote($this->tablePrefix.$this->name).'.'.$this->quote($v);
		return $v;
	}
	
	function trySelect($col, array $params = null){
		if(!$this->isSimpleColumnName($col,true)){
			throw new BadMethodCallException("You can't make a trySelect on a non simpleColumnName: '$col'");
		}
		if($this->columnExists($col)){
			$this->select->select($col, $params);
		}
		return $this;
	}
	function tryWhere($where, array $params = null){
		if(is_array($where)){
			$col = $where[0];
		}
		else{
			$col = $where;
			$where = $col.' = ?';
		}
		if(!$this->isSimpleColumnName($col,true)){
			throw new BadMethodCallException("You can't make a tryWhere on a non simpleColumnName: '$col'");
		}
		if($this->columnExists($col)){
			$this->select->where($where, $params);
		}
		return $this;
	}
	
	
	function __call($f,$args){
		if(method_exists($this,$m='compose_'.$f)){
			$o = $this->isClone?$this:(clone $this);
			return call_user_func_array([$o,$m],$args);
		}
		return parent::__call($f,$args);
	}
	
	function joinCascade($map=[]){
		$q = $this;
		$db = $this->dataSource;
		foreach($map as $table=>$on){
			
			$parent = array_shift($on);
			if(substr($table,0,4)=='via:'){
				$table = substr($table,4);
				$inversion = true;
			}
			else{
				$inversion = false;
			}
			
			$tableEsc = $this->esc($table);
			$tablePk = $db[$table]->getPrimaryKey();
			$tablePkEsc = $this->esc($tablePk);
			
			$parentEsc = $this->esc($parent);
			$parentPk = $db[$parent]->getPrimaryKey();
			$parentPkEsc = $this->esc($parentPk);
			
			$parentCol = $parent.'_'.$parentPk;
			$parentColEsc = $this->esc($parentCol);
			
			$tableCol = $table.'_'.$tablePk;
			$tableColEsc = $this->esc($tableCol);
			
			if($inversion){
				$join = "$tableEsc ON $tableEsc.$parentColEsc = $parentEsc.$parentPkEsc";
			}
			else{
				$join = "$tableEsc ON $tableEsc.$tablePkEsc = $parentEsc.$tableColEsc";
			}
			
			$params = [];
			foreach($on as $extra){
				if(Cast::isInt($extra)){
					$params[] = $extra;
					$extra = "$tableEsc.$tablePkEsc = ?";
				}
				if(is_array($extra)){
					$tmp = array_shift($extra);
					$params[] = $extra;
					$extra = $tmp;
				}
				$join .= " AND $extra";
			}
			
			$q = $q->join($join,$params);
			
		}
		return $q;
	}
	
	function compose_selectRelational($select,$colAlias=null){
		$this->hasSelectRelational = true;
		$table = $this->dataSource->escTable($this->name);
		$this->select($table.'.*');
		if(is_array($select)){
			foreach($select as $k=>$s)
				if(is_integer($k))
					$this->selectRelationnal($s,null);
				else
					$this->selectRelationnal($k,$s);
		}
		else{
			$this->processRelational($select,$colAlias,true);
		}
		return $this;
	}
	function compose_tableJoin($table, $join, array $params = null){
		$this->select->tableJoin($table, $join, $params);
		return $this;
	}
	function compose_joinAdd($join,array $params = null){
		$this->select->joinAdd($join, $params);
		return $this;
	}
	function compose_join($join, array $params = null){
		$this->select->join($join, $params);
		return $this;
	}
	function compose_joinLeft($join, array $params = null){
		$this->select->joinLeft($join, $params);
		return $this;
	}
	function compose_joinRight($join, array $params = null){
		$this->select->joinRight($join, $params);
		return $this;
	}
	function compose_joinOn($join, array $params = null){
		$this->select->joinOn($join, $params);
		return $this;
	}
	function compose_from($table, array $params = null){
		$this->select->from($table, $params);
		return $this;
	}
	function compose_unTableJoin($table=null,$join=null,$params=null){
		$this->select->unTableJoin($table,$join,$params);
		return $this;
	}
	function compose_unJoin($join=null,$params=null){
		$this->select->unJoin($join,$params);
		return $this;
	}
	function compose_unFrom($table=null,$params=null){
		$this->select->unFrom($table,$params);
		return $this;
	}
	function compose_setParam($k,$v){
		$this->select->set($k,$v);
		return $this;
	}
	function compose_getParam($k){
		return $this->select->get($k);
	}
	function compose_unWhere($where=null,$params=null){
		$this->select->unWhere($where,$params);
		return $this;
	}
	function compose_unWith($with=null,$params=null){
		$this->select->unWith($with,$params);
		return $this;
	}
	function compose_unWhereIn($where,$params=null){
		$this->select->unWhereIn($where,$params);
		return $this;
	}
	function compose_unWhereOp($column, $op,  array $params=null){
		$this->select->unWhereOp($column, $op, $params);
		return $this;
	}
	function compose_unOpenWhereAnd(){
		$this->select->unOpenWhereAnd();
		return $this;
	}
	function compose_unOpenWhereOr(){
		$this->select->unOpenWhereOr();
		return $this;
	}
	function compose_unOpenWhereNotAnd(){
		$this->select->unOpenWhereNotAnd();
		return $this;
	}
	function compose_unOpenWhereNotOr(){
		$this->select->unOpenWhereNotOr();
		return $this;
	}
	function compose_unCloseWhere(){
		$this->select->unCloseWhere();
		return $this;
	}
	function compose_where($where, array $params = null){
		$this->select->where($where, $params);
		return $this;
	}
	function compose_whereIn($where, array $params){
		$this->select->whereIn($where, $params);
		return $this;
	}
	function compose_whereOp($column, $op, array $params=null){
		$this->select->whereOp($column, $op, $params);
		return $this;
	}
	function compose_openWhereAnd(){
		$this->select->openWhereAnd();
		return $this;
	}
	function compose_openWhereOr(){
		$this->select->openWhereOr();
		return $this;
	}
	function compose_openWhereNotAnd(){
		$this->select->openWhereNotAnd();
		return $this;
	}
	function compose_openWhereNotOr(){
		$this->select->openWhereNotOr();
		return $this;
	}
	function compose_closeWhere(){
		$this->select->closeWhere();
		return $this;
	}
	function compose_with($with, array $params = null){
		$this->select->with($with, $params);
		return $this;
	}
	function compose_select($select, array $params = null){
		$this->select->select($select, $params);
		return $this;
	}
	function compose_selectMain($select, array $params = null){
		$this->select->selectMain($select, $params);
		return $this;
	}
	function compose_distinct($distinct = true){
		$this->select->distinct($distinct);
		return $this;
	}
	function compose_groupBy($group_by, array $params = null){
		$this->select->groupBy($group_by, $params);
		return $this;
	}
	function compose_withRollup($with_rollup = true){
		$this->select->withRollup($with_rollup);
		return $this;
	}
	function compose_orderBy($order_by, array $params = null){
		$this->select->orderBy($order_by, $params);
		return $this;
	}
	function compose_orderByMain($order_by, array $params = null){
		$this->select->orderByMain($order_by, $params);
		return $this;
	}
	function compose_sort($desc=false){
		$this->select->sort($desc);
		return $this;
	}
	function compose_limit($limit){
		$this->select->limit($limit);
		return $this;
	}
	function compose_offset($offset){
		$this->select->offset($offset);
		return $this;
	}
	function compose_having($having, array $params = null){
		$this->select->having($having, $params);
		return $this;
	}
	function compose_havingIn($having, array $params){
		$this->select->havingIn($having, $params);
		return $this;
	}
	function compose_havingOp($column, $op, array $params=null){
		$this->select->havingOp($column, $op, $params);
		return $this;
	}
	function compose_openHavingAnd(){
		$this->select->openHavingAnd();
		return $this;
	}
	function compose_openHavingOr(){
		$this->select->openHavingOr();
		return $this;
	}
	function compose_openHavingNotAnd(){
		$this->select->openHavingNotAnd();
		return $this;
	}
	function compose_openHavingNotOr(){
		$this->select->openHavingNotOr();
		return $this;
	}
	function compose_closeHaving(){
		$this->select->closeHaving();
		return $this;
	}
	function compose_unSelect($select=null, array $params = null){
		$this->select->unSelect($select, $params);
		return $this;
	}
	function compose_unDistinct(){
		$this->select->unDistinct();
		return $this;
	}
	function compose_unGroupBy($group_by=null, array $params = null){
		$this->select->unGroupBy($group_by, $params);
		return $this;
	}
	function compose_unWithRollup(){
		$this->select->unWithRollup();
		return $this;
	}
	function compose_unOrderBy($order_by=null, array $params = null){
		$this->select->unOrderBy($order_by, $params);
		return $this;
	}
	function compose_unSort(){
		$this->select->unSort();
		return $this;
	}
	function compose_unLimit(){
		$this->select->unLimit();
		return $this;
	}
	function compose_unOffset(){
		$this->select->unOffset();
		return $this;
	}
	function compose_unHaving($having=null, array $params = null){
		$this->select->unHaving($having,  $params);
		return $this;
	}
	function compose_unHavingIn($having, array $params){
		$this->select->unHavingIn($having, $params);
		return $this;
	}
	function compose_unHavingOp($column, $op, array $params=null){
		$this->select->unHavingOp($column, $op,  $params);
		return $this;
	}
	function compose_unOpenHavingAnd(){
		$this->select->unOpenHavingAnd();
		return $this;
	}
	function compose_unOpenHavingOr(){
		$this->select->unOpenHavingOr();
		return $this;
	}
	function compose_unOpenHavingNotAnd(){
		$this->select->unOpenHavingNotAnd();
		return $this;
	}
	function compose_unOpenHavingNotOr(){
		$this->select->unOpenHavingNotOr();
		return $this;
	}
	function compose_unCloseHaving(){
		$this->select->unCloseHaving();
		return $this;
	}
	function compose_hasColumn(){
		return $this->select->hasColumn();
	}
	function compose_getColumn(){
		return $this->select->getColumn();
	}
	function compose_hasTable(){
		return $this->select->hasTable();
	}
	function compose_getTable(){
		return $this->select->getTable();
	}
	function compose_hasJoin(){
		return $this->select->hasJoin();
	}
	function compose_getJoin(){
		return $this->select->getJoin();
	}
	function compose_hasFrom(){
		return $this->select->hasFrom();
	}
	function compose_getFrom(){
		return $this->select->getFrom();
	}
	function compose_hasWhere(){
		return $this->select->hasWhere();
	}
	function compose_hasWith(){
		return $this->select->hasWith();
	}
	function compose_getWhere(){
		return $this->select->getWhere();
	}
	function compose_getWith(){
		return $this->select->getWith();
	}
	function compose_hasSelect(){
		return $this->select->hasSelect();
	}
	function compose_getSelect(){
		return $this->select->getSelect();
	}
	function compose_hasDistinct(){
		return $this->select->hasDistinct();
	}
	function compose_hasGroupBy(){
		return $this->select->hasGroupBy();
	}
	function compose_getGroupBy(){
		return $this->select->getGroupBy();
	}
	function compose_hasWithRollup(){
		return $this->select->hasWithRollup();
	}
	function compose_hasHaving(){
		return $this->select->hasHaving();
	}
	function compose_getHaving(){
		return $this->select->getHaving();
	}
	function compose_hasOrderBy(){
		return $this->select->hasOrderBy();
	}
	function compose_getOrderBy(){
		return $this->select->getOrderBy();
	}
	function compose_hasSort(){
		return $this->select->hasSort();
	}
	function compose_getSort(){
		return $this->select->getSort();
	}
	function compose_hasLimit(){
		return $this->select->hasLimit();
	}
	function compose_getLimit(){
		return $this->select->getLimit();
	}
	function compose_hasOffset(){
		return $this->select->hasOffset();
	}
	function compose_getOffset(){
		return $this->select->getOffset();
	}
	
	function compose_getQuery(){
		return $this->select->getQuery();
	}
	function compose_getParams(){
		return $this->select->getParams();
	}
	
	function compose_joinOnFor($join,$for,array $params = null){
		return $this->select->joinOnFor($join,$for,$params);
	}
	
	function compose_escapeLike($like){
		return $this->select->escapeLike($like);
	}
	function compose_likeLeft($columns, $search, $and=false, $not=false){
		return $this->select->likeLeft($columns, $search, $and, $not);
	}
	function compose_likeRight($columns, $search, $and=false, $not=false){
		return $this->select->likeRight($columns, $search, $and, $not);
	}
	function compose_likeBoth($columns, $search, $and=false, $not=false){
		return $this->select->likeBoth($columns, $search, $and, $not);
	}
	function compose_like($columns, $searchPattern, $search, $and=false, $not=false){
		return $this->select->like($columns, $searchPattern, $search, $and, $not);
	}
	
	function compose_notLikeLeft($columns, $search, $and=false){
		return $this->select->notLikeLeft($columns, $search, $and);
	}
	function compose_notLikeRight($columns, $search, $and=false){
		return $this->select->notLikeRight($columns, $search, $and);
	}
	function compose_notLikeBoth($columns, $search, $and=false){
		return $this->select->notLikeBoth($columns, $search, $and);
	}
	function compose_notLike($columns, $searchPattern, $search, $and=false){
		return $this->select->notLike($columns, $searchPattern, $search, $and);
	}
}
}
#DataTable/Mysql.php

namespace FoxORM\DataTable {
class Mysql extends SQL{
	function fullTextSearch($text,$mode='',$columns=[]){
		if($mode){
			switch(strtoupper($mode)){
				case 'EXP':
				case 'EXPANSION':
				case 'QUERY EXPANSION':
				case 'WITH QUERY EXPANSION':
					$mode = 'WITH QUERY EXPANSION';
				break;
				case 'BOOL':
				case 'BOOLEAN':
				case 'IN BOOLEAN MODE':
					$mode = 'IN BOOLEAN MODE';
				break;
				case 'NATURAL':
				case 'LANGUAGE':
				case 'IN NATURAL LANGUAGE':
				case 'IN NATURAL LANGUAGE MODE':
					$mode = 'IN NATURAL LANGUAGE MODE';
				break;
				default:
					$mode = '';
				break;
			}
		}
		if($this->dataSource->fulltextAvailableOnInnoDB())
			$this->fullTextSearchInnoDB($text,$mode,$columns);
		else
			$this->fullTextSearchMyISAM($text,$mode,$columns);
	}
	function fullTextSearchInnoDB($text,$mode='',&$columns=[]){
		$table = $this->dataSource->escTable($this->name);
		$this->dataSource->addFtsIndex($this->name,$columns,$this->getPrimaryKey(),$this->getUniqTextKey());
		$cols = '`'.implode('`,`',$columns).'`';
		$this->where('MATCH('.$cols.') AGAINST (? '.$mode.')',[$text]);
		$this->select('MATCH('.$cols.') AGAINST (? '.$mode.') AS _rank',[$text]);
		$this->select($table.'.*');
		$this->orderBy('_rank DESC');
		$this->setCounter(function()use($cols,$table,$text){
			return $this->dataSource->getCell('SELECT COUNT(IF(MATCH ('.$cols.') AGAINST (?), 1, NULL)) FROM '.$table,[$text]);
		});
	}
	function fullTextSearchMyISAM($text,$mode='',&$columns=[]){
		$table = $this->dataSource->escTable($this->name);
		$ftsTable = $this->dataSource->escTable($this->name.$this->dataSource->getFtsTableSuffix());
		$this->dataSource->makeFtsTableAndIndex($this->name,$columns,$this->getPrimaryKey(),$this->getUniqTextKey());
		$cols = '`'.implode('`,`',$columns).'`';
		$pk = $this->dataSource->esc($this->getPrimaryKey());
		$this->select($table.'.*');
		$this->unFrom($table);
		$limit = $this->getLimit();
		$offset = $this->getOffset();
		if($limit)
			$limit = 'LIMIT '.$limit;
		$offset = $offset?'OFFSET '.$offset:'';
		
		$this->join("(
			SELECT $ftsTable.$pk, MATCH($cols) AGAINST(? $mode) AS rank
				FROM $ftsTable
				WHERE MATCH($cols) AGAINST(? $mode)
				ORDER BY rank DESC
				$limit $offset
		) AS _ranktable ON _ranktable.$pk = $table.$pk",[$text,$text]);
		$this->orderBy('_ranktable.rank DESC');
		$this->setCounter(function()use($cols,$ftsTable,$text){
			return $this->dataSource->getCell('SELECT COUNT(IF(MATCH ('.$cols.') AGAINST (?), 1, NULL)) FROM '.$ftsTable,[$text]);
		});
	}
}
}
#DataTable/Pgsql.php

namespace FoxORM\DataTable {
use InvalidArgumentException;
class Pgsql extends SQL{
	protected $fulltextHeadline = [
		'MaxFragments'=>2,
		'MaxWords'=>25,
		'MinWords'=>20,
		'ShortWord'=>3,
		'FragmentDelimiter'=>' ... ',
		'StartSel'=>'<b>',
		'StopSel'=>'</b>',
		'HighlightAll'=>false,
	];
	protected $fullTextSearchLang;
	function setFullTextSearchLang($lang){
		if(!preg_match('/[a-z]/i',$lang))
			throw new InvalidArgumentException('Lang "'.$lang.'" is not a valid lang name');
		$this->fullTextSearchLang = $lang;
	}
	function setFulltextHeadline($config){
		$this->fulltextHeadline = $config+$this->fulltextHeadline;
	}
	function getFulltextHeadlineString(){
		$conf = '';
		foreach($this->fulltextHeadline as $k=>$v){
			if(is_bool($v))
				$conf .= $k.'='.($v?'TRUE':'FALSE').',';
			elseif(is_string($v))
				$conf .= $k.'="'.$v.'",';
			else
				$conf .= $k.'='.$v.',';
		}
		$conf = rtrim($conf,',');
		return $conf;
	}

	function fullTextSearch($text,$columns=[],$alias=null,$toVector=null){
		$indexName = $this->dataSource->addFtsColumn($this->name,$columns,$this->getPrimaryKey(),$this->getUniqTextKey(),$this->fullTextSearchLang);
		$lang = $this->fullTextSearchLang?"'".$this->fullTextSearchLang."',":'';
		$c = $this->select->formatColumnName($indexName);
		if(!$alias) $alias = $indexName.'_rank';
		$table = $this->dataSource->escTable($this->name);
		foreach(array_keys($columns) as $k){
			$columns[$k] = $this->select->formatColumnName($columns[$k]);
			if($toVector)
				$columns[$k] = 'to_tsvector('.$columns[$k].')';
		}
		$this->select("ts_rank({$c}, plainto_tsquery({$lang}?)) as $alias",[$text]);
		$sufx = $this->dataSource->getFtsTableSuffix();
		$sufxL = -1*strlen($sufx);
		foreach($this->dataSource->getColumns($this->name) as $col=>$colType){
			if(substr($col,0,6)!='_auto_'&&substr($col,$sufxL)!=$sufx){
				$col = $this->dataSource->esc($col);
				$this->select($table.'.'.$col.' as '.$col);
			}
		}
		$snippet = [];
		$headline = $this->getFulltextHeadlineString();
		$selectParams = [];
		foreach($columns as $v){
			$snippet[] = 'COALESCE(ts_headline('.$v.',plainto_tsquery('.$lang.'?),?),\'\')';
			$selectParams[] = $text;
			$selectParams[] = $headline;
		}
		$this->select(implode('||\''.$this->fulltextHeadline['FragmentDelimiter'].'\'||',$snippet).' as _snippet',$selectParams);
		$this->orderBy("ts_rank({$c}, plainto_tsquery({$lang}?))",[$text]);
		$this->where($table.'."'.$indexName.'" @@ plainto_tsquery('.$lang.'?)',[$text]);
		$this->setCounter(function()use($table,$indexName,$text){
			return $this->dataSource->getCell('SELECT COUNT(*) FROM '.$table.' WHERE '.$table.'."'.$indexName.'"  @@ plainto_tsquery(?)',[$text]);
		});
	}
}
}
#DataTable/Sqlite.php

namespace FoxORM\DataTable {
use InvalidArgumentException;
class Sqlite extends SQL{
	protected $fullTextSearchLocale;
	function setFullTextSearchLocale($locale){
		if(!preg_match('/[a-z]{2,3}\_[A-Z]{2,3}$/',$locale))
			throw new InvalidArgumentException('Locale "'.$locale.'" is not a valid locale name');
		$this->fullTextSearchLocale = $locale;
	}
	function fullTextSearch($text,$tokensNumber=30,$targetColumnIndex=-1,
		$start='<b>',$end='</b>',$sep='<b>...</b>',$columns=[]
	){
		if($tokensNumber>64)
			$tokensNumber = 64;
		$sufx = $this->dataSource->getFtsTableSuffix();
		$ftsTable = $this->dataSource->escTable($this->name.$sufx);
		$table = $this->dataSource->escTable($this->name);
		$pk = $this->dataSource->esc($this->getPrimaryKey());
		$this->dataSource->makeFtsTable($this->name,$columns,$this->getPrimaryKey(),$this->getUniqTextKey(),$this->fullTextSearchLocale);
		$this->select('snippet('.$ftsTable.',?,?,?,?,?) as _snippet',
			[$start,$end,$sep,(int)$targetColumnIndex,(int)$tokensNumber]);
		$this->select('docid as '.$pk);
		$this->select($table.'.*');
		$this->unFrom($table);
		$limit = $this->getLimit();
		$offset = $this->getOffset();
		if($limit)
			$limit = 'LIMIT '.$limit;
		if($offset)
			$offset = 'OFFSET '.$offset;
		$this->join("(
			SELECT docid as $pk, matchinfo($ftsTable) AS rank
				FROM $ftsTable 
				WHERE $ftsTable MATCH ?
				ORDER BY rank DESC
				$limit $offset
		) AS _ranktable USING($pk)",[$text]);
		$this->join("$ftsTable ON $table.$pk=$ftsTable.rowid");
		$this->where($ftsTable.' MATCH ?',[$text]);
		$this->orderBy('_ranktable.rank DESC');
		$this->setCounter(function()use($ftsTable,$text){
			if(!$this->exists())
				return;
			return (int)$this->dataSource->getCell("SELECT COUNT(*) FROM $ftsTable WHERE $ftsTable MATCH ?",[$text]);
		});
	}
}
}
#DataTable/Filesystem.php

namespace FoxORM\DataTable {
use FoxORM\DataTable;
class Filesystem extends DataTable{
	private $directoryIterator;
	private $patterns = [];
	private $antiPatterns = [];
	private $rewind;
	function __construct($name,$dataSource){
		parent::__construct($name,$dataSource);
		$this->directoryIterator = new \DirectoryIterator($this->dataSource->getDirectory().'/'.$this->name);
	}
	function rewind(){
		$this->directoryIterator->rewind();
		$this->rewind = true;
		$this->next();
		$this->rewind = false;
	}
	function current(){
		$iterator = $this->directoryIterator->current();
		if($iterator){
			$obj = $this->dataSource->entityFactory($this->name);
			$obj->{$this->getPrimaryKey()} = $iterator->getFilename();
			$obj->iterator = $iterator;
			if($this->useCache)
				$this->data[$obj->{$this->getPrimaryKey()}] = $obj;
			return $obj;
		}
	}
	function key(){
		return $this->directoryIterator->current()->getFilename();
	}
	function valid(){
		return $this->directoryIterator->valid();
	}
	function next(){
		$iterator = $this->directoryIterator->current();
		if(!$this->rewind)
			$iterator->next();
		while(
			$this->valid()&&
			(
				$iterator->isDot()
				||$this->patternMatch()
				||$this->antiPatternMatch()
			)
		)
			$iterator->next();
	}
	function patternMatch(){
		foreach($this->patterns as $p)
			if($p&&!preg_match($p,$this->key()))
				return true;
	}
	function AntiPatternMatch(){
		foreach($this->antiPatterns as $p)
			if($p&&preg_match($p,$this->key()))
				return true;
	}
	function addPattern($pattern){
		$this->patterns[] = $pattern;
	}
	function addAntiPattern($pattern){
		$this->antiPatterns[] = $pattern;
	}
	function setPattern($pattern){
		$this->patterns = $pattern;
	}
	function setAntiPattern($pattern){
		$this->antiPatterns = $pattern;
	}
	function getPrefixedBy($prefix){
		$a = [];
		foreach($this as $file=>$obj){
			if(strpos($file,$prefix)===0)
				$a[$file] = $obj;
		}
		return $a;
	}
	function __clone(){
		$this->directoryIterator = clone $this->directoryIterator;
	}
	
	function getAll(){
		
	}
	function getRow(){
		
	}
	function getCol(){
		
	}
	function getCell(){
		
	}
}
}
#DataTable/Cubrid.php

namespace FoxORM\DataTable {
class Cubrid extends SQL{
	
}
}
#Entity/TableWrapper.php

namespace FoxORM\Entity {
use FoxORM\DataSource;
use FoxORM\DataTable;
use BadMethodCallException;
class TableWrapper implements \ArrayAccess,\Iterator,\Countable,\JsonSerializable{
	protected $type;
	protected $db;
	protected $dataTable;
	protected $uniqTextKey;
	function __construct($type, DataSource $db=null, DataTable $table=null){
		$this->type = $type;
		$this->db = $db;
		$this->dataTable = $table;
		if(isset($this->uniqTextKey)){
			$this->setUniqTextKey($this->uniqTextKey);
		}
	}
	function __call($f,$args){
		if(method_exists($this->dataTable,$f)){
			return call_user_func_array([$this->dataTable,$f],$args);
		}
		throw new BadMethodCallException('Call to undefined method '.get_class($this).'->'.$f);
	}
	function offsetExists($id){
		return $this->dataTable->offsetExists($id);
	}
	function offsetGet($id){
		return $this->dataTable->offsetGet($id);
	}
	function offsetSet($id,$obj){
		return $this->dataTable->offsetSet($id,$obj);
	}
	function offsetUnset($id){
		return $this->dataTable->offsetUnset($id);
	}
	function rewind(){
		$this->dataTable->rewind();
	}
	function current(){
		return $this->dataTable->current();
	}
	function key(){
		return $this->dataTable->key();
	}
	function next(){
		return $this->dataTable->next();
	}
	function valid(){
		return $this->dataTable->valid();
	}
	function count(){
		return $this->dataTable->count();
	}
	function jsonSerialize(){
		return $this->dataTable->jsonSerialize();
	}
	function _setDataTable(DataTable $dataTable){
		$this->dataTable = $dataTable;
	}
}
}
#Entity/TableWrapperSQL.php

namespace FoxORM\Entity {
use FoxORM\DataSource;
use FoxORM\DataTable;
class TableWrapperSQL extends TableWrapper{
	protected $loadColumns = [];
	protected $dontLoadColumns;
	protected $uniqColumns = [];
	function getLoadColumns(){
		$loadColumns = $this->loadColumns;
		$tableAll = $this->dataTable->formatColumnName('*');
		if(empty($loadColumns)){
			$loadColumns[] = $tableAll;
		}
		if( 
			!empty($this->dontLoadColumns)
			&&
			(false!==($i=array_search('*',$loadColumns)) || false!==($i=array_search($tableAll,$loadColumns)))
		){
			$columns = [];
			foreach($this->db->getColumnNames($this->type) as $col){
				if(!in_array($col,$this->dontLoadColumns)){
					$columns[] = $this->dataTable->formatColumnName($col);
				}
			}
			array_splice($loadColumns, $i, 1, $columns);
		}
		return $loadColumns;
	}
	function getLoadColumnsSnippet(){
		$columns = $this->getLoadColumns();
		if(empty($columns)) return '';
		foreach($columns as &$col){
			$col = $this->formatColumnName($col);
		}
		return implode(',',$columns);
	}
	function _onAddColumn($column){
		foreach($this->uniqColumns as $uniq){
			if(is_array($uniq)){
				if(in_array($column,$uniq)){
					$ok = true;
					foreach($uniq as $u){
						if($u!=$column&&!$this->db->columnExists($this->type,$u)){
							$ok = false;
							break;
						}
					}
					if($ok){
						$this->db->addUniqueConstraint($this->type,$uniq);
					}
				}
			}
			elseif($uniq==$column){
				$this->db->addUniqueConstraint($this->type,$uniq);
			}
		}
	}
	function __call($f,$args){
		if(method_exists($this->dataTable,'compose_'.$f)){
			return call_user_func_array([$this->dataTable,$f],$args);
		}
		return parent::__call($f,$args);
	}
}
}
#Entity/StateFollower.php

namespace FoxORM\Entity {
interface StateFollower extends \Iterator{
	function __set($k,$v);
	function __get($k);
	function __isset($k);
	function __unset($k);
	function __readingState($b);
}
}
#Entity/Box.php

namespace FoxORM\Entity {
interface Box{
	function setDatabase($db);
	function getDatabase();
}
}
#Entity/Observer.php

namespace FoxORM\Entity {
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
}
#Entity/Model.php

namespace FoxORM\Entity {
use FoxORM\Std\ScalarInterface;
use FoxORM\Std\Cast;
use FoxORM\DataSource;
use FoxORM\Entity\StateFollower;
class Model implements Observer,Box,StateFollower,\ArrayAccess,\JsonSerializable{
	private $__readingState;
	private $__data = [];
	private $__cursor = [];
	private $__events = [];
	
	protected $db;
	protected $_table;
	
	public $_modified = false;
	public $_type;
	
	function beforeRecursive(){}
	function beforePut(){}
	function beforeCreate(){}
	function beforeRead(){}
	function beforeUpdate(){}
	function beforeDelete(){}
	function afterPut(){}
	function afterCreate(){}
	function afterRead(){}
	function afterUpdate(){}
	function afterDelete(){}
	function afterRecursive(){}
	function serializeColumns(){}
	function unserializeColumns(){}
	
	function __construct($array=[],$type=null){
		foreach($array as $k=>$v){
			$this->__set($k,$v);
		}
		if($type){
			$this->_type = $type;
		}
	}
	
	function __set($k,$v){
		$meta = substr($k,0,1)=='_';
		if(!$this->__readingState&&!$meta&&(!isset($this->__data[$k])||$this->__data[$k]!=$v)&&Cast::isScalar($v)){
			$this->_modified = true;
		}
		if($meta&&substr($k,0,5)==='_one_'){
			$relationKey = $k;
			$xclusive = substr($relationKey,-3)=='_x_';
			if($xclusive)
				$relationKey = substr($relationKey,0,-3);
			$relationKey = substr($relationKey,5);
			$pk = $this->db[$relationKey]->getPrimaryKey();
			if(!$v||Cast::isInt($v)){
				$k = $relationKey.'_'.$pk;
			}
			elseif(is_scalar($v)||Cast::isScalar($v)){
				$uk = $this->db[$relationKey]->getUniqTextKey();
				$k = $relationKey.'_'.$uk;
				$v = Cast::scalar($v);
			}
			else{
				$k = $relationKey.'_'.$pk;
				$v = is_object($v)?$v->$pk:$v[$pk];
			}
		}
		elseif($pkOf=$this->db->isPrimaryKeyOf($k)){
			$k1 = '_one_'.$pkOf;
			$k2 = '_one_'.$pkOf.'_x_';
			$pk = $this->db[$pkOf]->getPrimaryKey();
			if(array_key_exists($k1,$this->__data)){
				$this->__data[$k1] = $v;
			}
			if(array_key_exists($k2,$this->__data)){
				$this->__data[$k2] = $v;
			}
		}
		$this->__cursor[$k] = &$this->__data[$k];
		$this->__data[$k] = $v;
	}
	function &__get($k){
		if(!array_key_exists($k,$this->__data)){
			if(substr($k,0,1)==='_'){
				$relationKey = $k;
				$xclusive = substr($relationKey,-3)=='_x_';
				if($xclusive)
					$relationKey = substr($relationKey,0,-3);
				if(substr($k,0,5)==='_one_'){
					$relationKey = substr($relationKey,5);
					
					
					$relationTable = $this->db[$relationKey];
					$relationFk = $relationKey.'_'.$relationTable->getPrimaryKey();
					if(isset($this->data[$relationFk])&&$this->data[$relationFk]){
						$relationId = $this->data[$relationFk];
						$this->__data[$k] = $relationTable[$relationId];
					}
					else{
						$this->__data[$k] = $this->one($relationKey);
					}
					
					$this->__cursor[$k] = &$this->__data[$k];
				}
				elseif(substr($k,0,6)==='_many_'){
					$relationKey = substr($relationKey,6);
					if($this->getId()){
						$this->__data[$k] = $this->many($relationKey);
					}
					else{
						$this->__data[$k] = [];
					}
					$this->__cursor[$k] = &$this->__data[$k];
				}
				elseif(substr($k,0,11)==='_many2many_'){
					$relationKey = substr($relationKey,11);
					if($this->getId()){
						$this->__data[$k] = $this->many2many($relationKey);
					}
					else{
						$this->__data[$k] = [];
					}
					$this->__cursor[$k] = &$this->__data[$k];
				}
				elseif(substr($k,0,15)==='_many2manyLink_'){
					$relationKey = substr($relationKey,15);
					if($this->getId()){
						$this->__data[$k] = $this->many2manyLink($relationKey);
					}
					else{
						$this->__data[$k] = [];
					}
					$this->__cursor[$k] = &$this->__data[$k];
				}
				elseif($pkOf=$this->db->isPrimaryKeyOf($k)){
					$k1 = '_one_'.$pkOf;
					$k2 = '_one_'.$pkOf.'_x_';
					$pk = $this->db[$pkOf]->getPrimaryKey();
					if(array_key_exists($k1,$this->__data)){
						$this->__data[$k] = $this->__data[$k1]->$pk;
					}
					elseif(array_key_exists($k2,$this->__data)){
						$this->__data[$k] = $this->__data[$k2]->$pk;
					}
					else{
						$this->__data[$k] = $this->getValueOf($k);
					}
				}
				else{
					$this->__data[$k] = $this->getValueOf($k);
				}
			}
			else{
				$this->__data[$k] = $this->getValueOf($k);
			}
		}
		return $this->__data[$k];
	}
	function __isset($k){
		return array_key_exists($k,$this->__cursor);
	}
	function __unset($k){
		if(array_key_exists($k,$this->__data)){
			unset($this->__data[$k]);
		}
		if(array_key_exists($k,$this->__cursor)){
			unset($this->__cursor[$k]);
		}
	}
	
	function rewind(){
		foreach($this->__data as $k=>$v){
			if(!array_key_exists($k,$this->__cursor)&&!empty($v)){
				$this->__cursor[$k] = &$this->__data[$k];
			}
		}
		reset($this->__cursor);
	}
	function current(){
		return current($this->__cursor);
	}
	function key(){
		return key($this->__cursor);
	}
	function next(){
		return next($this->__cursor);
	}
	function valid(){
		return key($this->__cursor)!==null;
	}
	
	function keys(){
		return array_keys($this->__cursor);
	}
	
	function offsetSet($k,$v){
		$this->__set($k,$v);
	}
	function &offsetGet($k){
		$ref = $this->__get($k);
		return $ref;
	}
	function offsetExists($k){
		return $this->__isset($k);
	}
	function offsetUnset($k){
		$this->__unset($k);
	}
	
	function setDatabase($db){
		$this->db = $db;
		$this->_table = $this->db[$this->_type];
	}
	function getDatabase(){
		return $this->db;
	}
	function __readingState($b,$recursive=false){
		$this->__readingState = (bool)$b;
	}
	function setArray(array $data){
		$this->__data = $data;
	}
	function getArrayTree(){
		if(func_num_args()){
			$o = func_get_arg(0);
		}
		else{
			$o = $this->__data;
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
		return $this->__data;
	}
	function jsonSerialize(){
		return $this->__data;
	}
	function getArrayScalar(){
		$a = [];
		foreach($this->__data as $k=>$v){
			if(Cast::isScalar($v))
				$a[$k] = Cast::scalar($v);
		}
		return $a;
	}
	function getValueOf($col,$id=null,$type=null){
		if(isset($this->$col)) return $this->$col;
		if(is_null($type)) $type = $this->_type;
		$table = $this->db[$type];
		$pk = $table->getPrimaryKey();
		$uk = $table->getPrimaryKey();
		if(is_null($id)&&isset($this->$pk)) $id = $this->$pk;
		if(is_null($id)&&isset($this->$uk)) $id = $this->$uk;
		$k = Cast::isInt($id)?$pk:$uk;
		if($table->columnExists($col))
			return $this->db->getCell('SELECT '.$table->formatColumnName($col).' FROM '.$this->db->escTable($type).' WHERE '.$table->formatColumnName($k).' = ?',[$id]);
	}
	function getOneId($type,$primaryKey=null){
		if(!$primaryKey) $primaryKey = $this->db[$type]->getPrimaryKey();
		$id = $type.'_'.$primaryKey;
		if($this->$id) return $this->$id;
		if(
				( isset($this->{'_one_'.$type})&&($o=$this->{'_one_'.$type}) )
			||	( isset($this->{'_one_'.$type.'_x_'})&&($o=$this->{'_one_'.$type.'_x_'}) )
		){
			if(Cast::isScalar($o)){
				if(Cast::isInt($o)){
					return $o;
				}
				else{
					$o = Cast::scalar($o);
					return $this->db[$type][$o]->$primaryKey;
				}
			}
			elseif(is_object($o)){
				return $o->$primaryKey;
			}
			elseif(is_array($o)){
				return $o[$primaryKey];
			}
		}
	}
	
	function one($one){
		//return $this->db->many2one($this,$one);
		return $this->db[$one]->one($this);
	}
	function many($many){
		//return $this->db->one2many($this,$many);
		return $this->db[$many]->many($this);
	}
	function many2many($many,$via=null){
		//return $this->db->many2many($this,$many,$via);
		return $this->db[$many]->many2many($this,$via);
	}
	function many2manyLink($many,$via=null){
		//return $this->db->many2manyLink($this,$many,$via);
		return $this->db[$many]->many2manyLink($this,$via);
	}
	
	function store(){
		$this->_table[] = $this;
	}
	
	function load(){
		$pk = $this->db->getPrimaryKey();
		$this->__readingState(true);
		foreach($this->_table->where($pk.' = ?',[$this->__get($pk)])->getRow() as $k=>$v){
			$this->__set($k,$v);
		}
		$this->__readingState(false);
	}
	
	function import($data, $filter=null, $reversedFilter=false){
		if($filter){
			$data = $this->db->dataFilter($data,$filter,$reversedFilter);
		}
		foreach($data as $k=>$v){
			if($k=='_type'&&$this->_type) continue;
			$this->__set($k,$v);
		}
		return $data;
	}
	function newImport($data, $filter=null, $reversedFilter=false){
		$preFilter = [];
		$table = $this->db[$name];
		$preFilter[] = $table->getPrimaryKey();
		$preFilter[] = $table->getUniqTextKey();
		if(is_array($data)){
			if(isset($data['_type'])&&$data['_type']){
				$nameSource = $data['_type'];
			}
		}
		elseif(is_object($data)){
			$nameSource = $this->db->findEntityTable($obj);
		}
		else{
			$nameSource = null;
		}
		if($nameSource){
			$tableSource = $this->db[$nameSource];
			$pk = $tableSource->getPrimaryKey();
			$pku = $tableSource->getUniqTextKey();
			if(!in_array($pk,$preFilter)){
				$preFilter[] = $pk;
			}
			if(!in_array($pku,$preFilter)){
				$preFilter[] = $pku;
			}
		}
		$data = $this->dataFilter($data,$preFilter,true);
		return $this->import($data, $filter, $reversedFilter);
	}
	
	function delete(){
		$this->_table->delete($this);
	}
	
	function on($event,$call=null,$index=0,$prepend=false){
		if($index===true){
			$prepend = true;
			$index = 0;
		}
		if(is_null($call))
			$call = $event;
		if(!isset($this->__events[$event][$index]))
			$this->__events[$event][$index] = [];
		if($prepend)
			array_unshift($this->__events[$event][$index],$call);
		else
			$this->__events[$event][$index][] = $call;
		return $this;
	}
	function off($event,$call=null,$index=0){
		if(func_num_args()===1){
			if(isset($this->__events[$event]))
				unset($this->__events[$event]);
		}
		elseif(func_num_args()===2){
			foreach($this->__events[$event] as $index){
				if(false!==$i=array_search($call,$this->__events[$event][$index],true)){
					unset($this->__events[$event][$index][$i]);
				}
			}
		}
		elseif(isset($this->__events[$event][$index])){
			if(!$call)
				unset($this->__events[$event][$index]);
			elseif(false!==$i=array_search($call,$this->__events[$event][$index],true))
				unset($this->__events[$event][$index][$i]);
		}
		return $this;
	}
	function trigger($event, $recursive=false, $flow=null){
		if(isset($this->__events[$event]))
			$this->db->triggerExec($this->__events[$event], $this->_type, $event, $this, $recursive, $flow);
		return $this;
	}
	function getId(){
		$pk = $this->_table->getPrimaryKey();
		return isset($this->__data[$pk])?$this->__data[$pk]:null;
	}
}
}
#Entity/RulableInterface.php

namespace FoxORM\Entity {
interface RulableInterface{
	function applyValidateProperties();
	function applyValidatePreFilters();
	function applyValidateRules();
	function applyValidateFilters();
	function getValidate();
	function beforeValidate();
	function afterValidate();
}
}
#Entity/RulableModel.php

namespace FoxORM\Entity {
use FoxORM\Exception\ValidationException;
use FoxORM\Std\ArrayIterator;
class RulableModel extends Model implements RulableInterface {
	protected $validatePreFilters = [];
	protected $validateRules = [];
	protected $validateFilters = [];
	protected $validateProperties = false;
	protected $validatePropertiesSilent = true;
	function applyValidateProperties(){
		if($this->validateProperties===false) return;
		$pk = $this->_table->getPrimaryKey();
		foreach($this->keys() as $k){
			if($k==$pk) continue;
			if(!in_array($k,$this->validateProperties)){
				if($this->validatePropertiesSilent){
					$this->__unset($k);
				}
				else{
					$e = new ValidationException('Property '.$k.' not allowed for entity of type "'.$this->_type.'" by model class "'.get_class().'"');
					$e->setEntity($this);
					$e->setDB($this->db);
					throw $e;
				}
			}
		}
	}
	function applyValidatePreFilters(){
		$this->_applyFilters($this->validatePreFilters);
	}
	function applyValidateRules(){
		$this
			->getValidate()
			->createRule($this->validateRules)
			->assert($this);
	}
	function applyValidateFilters(){
		$this->_applyFilters($this->validateFilters);
	}
	function getValidate(){
		return $this->db->getValidateService();
	}
	function beforeValidate(){}
	function afterValidate(){}
	
	protected function _applyFilters(array $filters){
		$this->__readingState(true);
		
		$properties = $this->getArray();
		
		$filteredProperties = $this
			->getValidate()
			->createFilter($filters)
			->filter($properties);
		
		foreach($filteredProperties as $k=>$v){
			if($properties[$k]!==$v){
				$this->$k = $v;
			}
		}
		
		$this->__readingState(false);
	}
}
}
#Helper/Pagination.php

namespace FoxORM\Helper {
class Pagination{
	var $start;
	var $end;
	var $max;
	var $count;
	var $limit;
	var $offset;
	var $page;
	var $maxCols;
	var $href;
	var $prefix;
	var $pagesTotal;
	function __construct($config=null){
		if($config){
			foreach($config as $k=>$v){
				$this->{'set'.ucfirst($k)}($v);
			}
		}
	}
	function setLimit($limit){
		$this->limit = $limit;
	}
	function setMaxCols($maxCols){
		$this->maxCols = $maxCols;
	}
	function setHref($href){
		$this->href = $href;
	}
	function setPrefix($prefix){
		$this->prefix = $prefix;
	}
	function setCount($count){
		$this->count = $count;
	}
	function setPage($page){
		$this->page = $page;
		$this->offset = $this->page?($this->page-1)*$this->limit:0;
	}
	function resolve(){
		if(!$this->page){
			$this->page = 1;
		}
		elseif(
			!is_integer(filter_var($this->page,FILTER_VALIDATE_INT))
			||($this->page=(int)$this->page)<2
			||$this->count<=$this->offset
		){
			return false;
		}
		$this->pagesTotal = (int)ceil($this->count/$this->limit);
		if($this->maxCols>$this->pagesTotal)
			$this->max = $this->pagesTotal-1;
		else
			$this->max = $this->maxCols-1;
		$this->start = $this->page-(int)floor($this->max/2);
		if($this->start<=0)
			$this->start = 1;
		$this->end = ($this->start+$this->max)>$this->pagesTotal?$this->pagesTotal:$this->start+$this->max;
		if($this->end-$this->start<$this->max)
			$this->start = $this->end-$this->max;
		return true;
	}
}
}
#Helper/SqlLogger.php

namespace FoxORM\Helper {
use SqlFormatter;
use RedCat\Debug\Vars as DebugVars;
class SqlLogger {
	protected $echo;
	protected $keep;
	protected $html;
	protected $enhanceQuery;
	protected $useRedCatDebug;
	protected $logs = [];
	function __construct($echo=null,$keep=null,$html=null,$enhanceQuery=true,$useRedCatDebug=true){
		$this->setEcho($echo);
		$this->setKeep($keep);
		$this->setHtml($html);
		$this->setEnhanceQuery($enhanceQuery);
		$this->setUseUnitDebug($useRedCatDebug);
	}
	function setEcho($b=true){
		$this->echo = (bool)$b;
	}
	function setKeep($b=true){
		$this->keep = (bool)$b;
	}
	function setEnhanceQuery($b=true){
		$this->enhanceQuery = (bool)$b;
	}
	function setHtml($b=null){
		if(is_null($b)){
			$b = php_sapi_name() !== 'cli';
		}
		$this->html = (bool)$b;
	}
	function setUseUnitDebug($b=true){
		$this->useRedCatDebug = (bool)$b;
	}
	function getLogs(){
		return $this->logs;
	}
	function clear(){
		$this->logs = [];
	}
	private function writeQuery( $newSql, $newBindings ){
		uksort( $newBindings, function( $a, $b ) {
			return ( strlen( $b ) - strlen( $a ) );
		} );
		$newStr = $newSql;
		foreach( $newBindings as $slot => $value ) {
			if ( strpos( $slot, ':' ) === 0 ) {
				$newStr = str_replace( $slot, $this->fillInValue( $value ), $newStr );
			}
		}
		return $newStr;
	}
	protected function fillInValue( $value ){
		if(is_array($value)){
			$r = [];
			foreach($value as $v)
				$r[] = $this->fillInValue($v);
			return '('.implode(',',$r).')';
		}
		if ( is_null( $value ) ) $value = 'NULL';
		if(is_numeric( $value ))
			$value = str_replace(',','.',$value);
		elseif ( $value !== 'NULL'){
			if($this->html&&!class_exists(SqlFormatter::class))
				$value = "'".htmlentities($value)."'";
			else
				$value = "'".str_replace("'","\'",$value)."'";
		}
		return $value;
	}
	protected function output($str, $wrap=true){
		if($this->keep)
			$this->logs[] = $str;
		if($this->echo){
			if($this->html&&!headers_sent()){
				header('Content-type:text/html;charset=utf-8');
				if(ob_get_length()){
					ob_flush();
				}
				flush();
			}
			if($wrap&&$this->html){
				echo '<pre class="debug-model">',$str,'</pre><br />';
			}
			else{
				echo $str."\n";
			}
		}
	}
	protected function normalizeSlots( $sql ){
		$i = 0;
		$newSql = $sql;
		while($i < 20 && strpos($newSql, '?') !== FALSE ){
			$pos   = strpos( $newSql, '?' );
			$slot  = ':slot'.$i;
			$begin = substr( $newSql, 0, $pos );
			$end   = substr( $newSql, $pos+1 );
			$newSql = $begin . $slot . $end;
			$i++;
		}
		return $newSql;
	}
	protected function normalizeBindings( $bindings ){
		$i = 0;
		$newBindings = array();
		foreach( $bindings as $key => $value ) {
			if ( is_numeric($key) ) {
				$newKey = ':slot'.$i;
				$newBindings[$newKey] = $value;
				$i++;
			} else {
				$newBindings[$key] = $value;
			}
		}
		return $newBindings;
	}
	function logResult($r){
		if(!$this->keep&&!$this->echo)
			return;
		if($this->useRedCatDebug&&class_exists(DebugVars::class)){
			if($this->html)
				$newStr = DebugVars::debug_html_return($r);
			else
				$newStr = DebugVars::debug_return($r);
		}
		else{
			if($this->html){
				$html_errors = ini_get('html_errors');
				ini_set('html_errors',1);
				ob_start();
				var_dump($r);
				$newStr = ob_get_clean().'<br>';
				ini_set('html_errors',$html_errors);
			}
			else{
				$newStr = print_r($r,true);
			}
		}
		return $this->output($newStr,false);
	}
	function logSql($sql,$bindings=[]){
		if(!$this->keep&&!$this->echo)
			return;
		$newStr = $this->writeQuery($this->normalizeSlots($sql), $this->normalizeBindings($bindings));
		if($this->enhanceQuery&&class_exists(SqlFormatter::class))
			$newStr = SqlFormatter::format($newStr);
		$this->output($newStr,!$this->html);
	}
	function logChrono($chrono){
		if($this->html)
			$chrono = '<span style="color:#d00;font-size:12px;">'.$chrono.'</span>';
		$this->output($chrono,false);
	}
	function logExplain($explain){
		if($this->html){
			$id = 'explain'.uniqid();
			$explain = '<span onclick="document.getElementById(\''.$id.'\').style.display=document.getElementById(\''.$id.'\').style.display==\'none\'?\'block\':\'none\';" style="color:#d00;font-size:11px;margin-left:16px;text-decoration:underline;cursor:pointer;">EXPLAIN</span><div id="'.$id.'" style="display:none;color:#333;font-size:12px;"><pre>'.$explain.'</pre></div><br>';
		}
		$this->output($explain,false);
	}
	function log($txt){
		if(!$this->keep&&!$this->echo)
			return;
		$this->output($txt);
	}
}
}
#MainDb.php

namespace FoxORM {
class MainDb implements \ArrayAccess {
	protected $db;
	function __construct(Bases $databases){
		$this->db = $databases[0];
	}
	function __call($f,$a){
		return call_user_func_array([$this->db,$f],$a);
	}
	function offsetSet($k,$v){
		$this->db[$k] = $v;
	}
	function offsetExists($k){
		return $this->db[$k];
	}
	function offsetGet($k){
		return $this->db[$k];
	}
	function offsetUnset($k){
		unset($this->db[$k]);
	}
}
}
#F.php

namespace FoxORM {
use RedCat\Strategy\Di;
use BadMethodCallException;
class F{
	protected static $bases;
	protected static $currentDataSource;
	static $useStrategyDi = true;
	static function _init(){
		if(!isset(self::$bases)){
			if(class_exists(Di::class)&&self::$useStrategyDi){
				self::$bases = Di::getInstance()->get(Bases::class);
				if(isset(self::$bases[0]))
					self::selectDatabase(0);
			}
			else{
				self::$bases = new Bases();
			}
		}
	}
	static function getBases(){
		return self::$bases;
	}
	static function setup($dsn = null, $username = null, $password = null, $config = []){
		if(is_null($dsn))
			$dsn = 'sqlite:/'.sys_get_temp_dir().'/bases.db';
		self::addDatabase(0, $dsn, $username, $password, $config);
		self::selectDatabase(0);
		return self::$bases;
	}
	static function addDatabase($key,$dsn,$user=null,$password=null,$config=[]){
		self::$bases[$key] = [
			'dsn'=>$dsn,
			'user'=>$user,
			'password'=>$password,
		]+$config;
		if(!isset(self::$currentDataSource))
			self::selectDatabase($key);
	}
	static function selectDatabase($key){
		if(func_num_args()>1)
			call_user_func_array(['self','addDatabase'],func_get_args());
		return self::$currentDataSource = self::$bases[$key];
	}
	static function __callStatic($f,$args){
		self::_init();
		if(!isset(self::$currentDataSource))
			throw new BadMethodCallException('Use '.__CLASS__.'::setup() first');
		return call_user_func_array([self::$currentDataSource,$f],$args);
	}
	
	static function create($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function read($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function update($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function delete($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function put($mixed){
		return call_user_func_array([self::$currentDataSource,__FUNCTION__],func_get_args());
	}
	static function readId($type,$id){
		return call_user_func_array([self::$currentDataSource,'readId'],func_get_args());
	}
	static function exists($type,$id){
		return call_user_func_array([self::$currentDataSource,'readId'],func_get_args());
	}
	
	static function dispense($type){
		return self::$currentDataSource->entityFactory($type);
	}
	
	static function execute($sql,$binds=[]){
		return self::$currentDataSource->execute($sql,$binds);
	}
	
	static function exec($sql,$binds=[]){
		return self::$currentDataSource->execute($sql,$binds);
	}
	
	static function getDatabase(){
		return self::$currentDataSource;
	}
	static function getTable($type){
		return self::$currentDataSource[$type];
	}
	
	static function on($type,$event,$call=null,$index=0,$prepend=false){
		return self::$currentDataSource[$type]->on($event,$call,$index,$prepend);
	}
	static function off($type,$event,$call=null,$index=0){
		return self::$currentDataSource[$type]->off($event,$call,$index);
	}
	
	static function many2one($obj,$type){
		return self::$currentDataSource->many2one($obj,$type);
	}
	static function one2many($obj,$type){
		return self::$currentDataSource->one2many($obj,$type);
	}
	static function many2many($obj,$type,$via=null){
		return self::$currentDataSource->many2many($obj,$type,$via);
	}
	static function loadMany2one($obj,$type){
		return self::$currentDataSource->loadMany2one($obj,$type);
	}
	static function loadOne2many($obj,$type){
		return self::$currentDataSource->loadMany($obj,$type);
	}
	static function loadMany2many($obj,$type,$via=null){
		return self::$currentDataSource->loadMany2many($obj,$type,$via);
	}
	
	static function setModelClassPrefix($modelClassPrefix='Model\\'){
		return self::$bases->setModelClassPrefix($modelClassPrefix);
	}
	static function appendModelClassPrefix($modelClassPrefix){
		return self::$bases->appendModelClassPrefix($modelClassPrefix);
	}
	static function prependModelClassPrefix($modelClassPrefix){
		return self::$bases->prependModelClassPrefix($modelClassPrefix);
	}
	static function setEntityClassDefault($entityClassDefault='stdClass'){
		return self::$bases->setEntityClassDefault($entityClassDefault);
	}
	static function setPrimaryKeyDefault($primaryKeyDefault='id'){
		return self::$bases->setPrimaryKeyDefault($primaryKeyDefault);
	}
	static function setUniqTextKeyDefault($uniqTextKeyDefault='uniq'){
		return self::$bases->setUniqTextKeyDefault($uniqTextKeyDefault);
	}
	
	static function debug(){
		return call_user_func_array([self::$currentDataSource,'debug'],func_get_args());
	}
}
F::_init();
}
