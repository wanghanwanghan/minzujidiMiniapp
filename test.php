<?php

$str = '股东信息身份证少一位';

$res = preg_match('/[协议|承诺]/', $str);

var_dump($res);
