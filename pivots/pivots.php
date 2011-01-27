<?php
interface pivmodules{
public function add();
public function delete($id);
public function update($id);
public function select($id=null);
public function trash($id);
}
?>
