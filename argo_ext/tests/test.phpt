--TEST--
Check for php_func_post
--SKIPIF--
<?php if (!extension_loaded("argo_ext")) print "skip"; ?>
--FILE--
<?php 
ext_test_post();
?>
--EXPECT--
do nothing but post
