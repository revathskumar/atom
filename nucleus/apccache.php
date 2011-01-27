<?php
abstract class apcCacheAbstract {
abstract function fetch($key);
abstract function store($key,$data,$ttl);
abstract function delete($key);
}

?>
