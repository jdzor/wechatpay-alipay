<?php
/**
 * @note: 常用公共函数
 * @author: jdzor <895947580@qq.com>
 * @date: 2019/1/17
 */

/**
 * api 接口正确输出
 * @param string $data 返回数据
 * @param string $message 提示信息
 */
function json_success($data = '', $message = 'success')
{
    header('Content-Type:application/json; charset=utf-8');
    $result['status'] = 1;
    $result['message'] = $message;
    $result['data'] = empty($data) ? [] : $data;

    exit(json_encode($result));
}

/**
 * api 接口错误输出
 * @param int $status 状态码： -1参数错误（开发提示） -2用户提示（用户输入错误、商品不存在等） -9token过期
 * @param string $message 提示信息
 */
function json_error($message = 'error', $status = -1)
{
    header('Content-Type:application/json; charset=utf-8');
    $result['status'] = $status;
    $result['message'] = $message;

    exit(json_encode($result));
}

/**
 * 获取客户端IP地址
 * @param int $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0)
{
    $type = $type ? 1 : 0;
    static $ip = null;
    if ($ip !== null) return $ip[$type];

    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        //nginx 代理模式下，获取客户端真实IP
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        //客户端的ip
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //浏览当前页面的用户计算机的网关
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } else {
        //浏览当前页面的用户计算机的ip地址
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    //IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];

    return $ip[$type];
}

/**
 * 字符转码
 * @param $data
 * @return array|string
 */
function gbk2utf8($data)
{
    if (is_array($data)) {
        return array_map('gbk2utf8', $data);
    }

    return iconv('gbk', 'utf-8//IGNORE', $data); //IGNORE 会忽略掉不能转化的字符，而默认效果是从第一个非法字符截断。
}