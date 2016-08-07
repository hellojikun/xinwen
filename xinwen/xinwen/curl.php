<?php
/**
 * @Author: huhuaquan
 * @Date:   2015-08-10 18:08:43
 * @Last Modified by:   huhuaquan
 * @Last Modified time: 2016-05-27 18:21:16
 */
require_once './function.php';
class Curl {

	

	private static function genCookie() {
		$cookie = 'd_c0="AJCA6a_fAQqPTudycUfLgbjlGWHesNvKXas=|1464704438"; _za=d53f12df-cf0a-446d-a06a-46fc87bf9a9f; _zap=b4c8f3e7-8697-4894-9a10-280da9f4729e; _xsrf=7d99195e69c1d427c0f1a2e09e2fe59b; q_c1=ed5d59f8e4f74599af74794882ecf2c7|1469248432000|1469248432000; cap_id="Yzk4NjM0MDcxMWI3NDBmMzkzZTA5Nzk3NTI0Y2NkY2M=|1469377129|161d5ecbaa622d18a9a6bf3da15cc80afcdd3a3c"; l_cap_id="MjE4YzE3YWRiYmIxNDAxNWExYWNkMjc4YTA5NDRmYTI=|1469377129|4dc2b118e5a26cdaa0133241833d6c22b5aac026"; login="MjAwZjYxMDhiZDgyNDE4NGE2OTJlYjlmNzhlMmFiODU=|1469377144|cb1bfea1a948f908d0a2c5f4f6ae4a3909ed4536"; z_c0=Mi4wQUJBTVBJalI5QWdBa0lEcHI5OEJDaGNBQUFCaEFsVk5lSGU4VndCZjlMQ08yM0twVGRNQnhJUThqNmlRS3FZY25R|1469377144|a295675923de03357088edd4dc99a7ec10bbeb96; a_t="2.0ABAMPIjR9AgXAAAA_abCVwAQDDyI0fQIAJCA6a_fAQoXAAAAYQJVTXh3vFcAX_SwjttyqU3TAcSEPI-okCqmHJ3WdKwFSsh4HRfuo-_BZ8PN4ziDhg=="; __utmt=1; __utma=51854390.1297137047.1469854033.1469854033.1469854033.1; __utmb=51854390.2.10.1469854033; __utmc=51854390; __utmz=51854390.1469854033.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmv=51854390.100--|2=registration_date=20151104=1^3=entry_date=20151104=1';
		
		return $cookie;
	}

	/**
	 * [request 执行一次curl请求]
	 * @param  [string] $method     [请求方法]
	 * @param  [string] $url        [请求的URL]
	 * @param  array  $fields     [执行POST请求时的数据]
	 * @return [stirng]             [请求结果]
	 */
	public static function request($method, $url, $fields = array())
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_COOKIE, self::genCookie());
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if ($method === 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, true );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		}
		$result = curl_exec($ch);
		return $result;
	}

	/**
	 * [getMultiUser 多进程获取用户数据]
	 * @param  [type] $user_list [description]
	 * @return [type]            [description]
	 */
	public static function getMultiUser($user_list)
	{
		$ch_arr = array();
		$text = array();
		$len = count($user_list);
		$max_size = ($len > 5) ? 5 : $len;
		$requestMap = array();

		$mh = curl_multi_init();
		for ($i = 0; $i < $max_size; $i++)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_URL, 'http://www.zhihu.com/people/' . $user_list[$i] . '/about');
			curl_setopt($ch, CURLOPT_COOKIE, self::genCookie());
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$requestMap[$i] = $ch;
			curl_multi_add_handle($mh, $ch);
		}

		$user_arr = array();
		do {
			while (($cme = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM);
			
			if ($cme != CURLM_OK) {break;}

			while ($done = curl_multi_info_read($mh))
			{
				$info = curl_getinfo($done['handle']);
				$tmp_result = curl_multi_getcontent($done['handle']);
				$error = curl_error($done['handle']);

				$user_arr[] = array_values(getUserInfo($tmp_result));

				//保证同时有$max_size个请求在处理
				if ($i < sizeof($user_list) && isset($user_list[$i]) && $i < count($user_list))
                {
                	$ch = curl_init();
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_URL, 'http://www.zhihu.com/people/' . $user_list[$i] . '/about');
					curl_setopt($ch, CURLOPT_COOKIE, self::genCookie());
					curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					$requestMap[$i] = $ch;
					curl_multi_add_handle($mh, $ch);

                    $i++;
                }

                curl_multi_remove_handle($mh, $done['handle']);
			}

			if ($active)
                curl_multi_select($mh, 10);
		} while ($active);

		curl_multi_close($mh);
		return $user_arr;
	}

}