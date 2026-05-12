<?php

namespace app\service;

class PayService
{
    
    public function lbpalSign($data,$md5key){
        // 1. 过滤 sign 和空值
        $params = [];
        foreach ($data as $k => $v) {
            if ($k === 'sign') continue;           // 排除 sign
            if ($v === '' || $v === null) continue; // 排除空值
            $params[$k] = $v;
        }
    
        // 2. 按键名首字母排序（区分大小写）
        ksort($params);
    
        // 3. 拼接成 URL 查询字符串
        $query = http_build_query($params);
        // 让其保持原样（避免 urlencode 编码，如有要求可不使用 http_build_query）
        $query = urldecode($query);
    
        // 4. 加上 key=
        $query .= '&key=' . $md5key;
    
        // 5. md5 小写
        return strtoupper(md5($query));
    }
	
    public function curl_request($url, $data=null, $method='POST', $header = array("content-type: application/json"), $https=true, $timeout = 10){
        $method = strtoupper($method);
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//访问的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        if($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https请求 不验证HOST
        }
        if ($method != "GET") {
            if($method == 'POST'){
                curl_setopt($ch, CURLOPT_POST, true);//请求方式为post请求
            }
            if ($method == 'PUT' || strtoupper($method) == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//请求数据
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        //curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息
        $result = curl_exec($ch);//执行请求
        curl_close($ch);//关闭curl，释放资源
        return $result;
    }	
}
