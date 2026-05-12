<?php

function sys_md5($str, $key = '')
{
    return '' === $str ? '' : md5(sha1($str) . $key);
}
