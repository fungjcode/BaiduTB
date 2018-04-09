<?php

// 帖吧图集采集程序

// 二次开发 风之翼灵 www.myhioo.info

//  ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ [ 配置开始 ] ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓

$tbs = array( //创建数组开始，语法array(key => value) ，key可选，value必填

	array( //创建数组开始

		'name' => '美女', // 帖吧名

		'en' => 'meinv', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '黑丝', // 帖吧名

		'en' => 'heisi', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '美人', // 帖吧名

		'en' => 'meiren', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '腿', // 帖吧名

		'en' => 'tui', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '皮裤', // 帖吧名

		'en' => 'piku', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '高跟', // 帖吧名

		'en' => 'gaogen', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '内衣照', // 帖吧名

		'en' => 'neyizhao', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '高跟', // 帖吧名

		'en' => 'gaogen', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '胸怀天下', // 帖吧名

		'en' => 'xionghuatianxias', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '制服', // 帖吧名

		'en' => 'zhifu', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => 'beautyleg', // 帖吧名

		'en' => 'beautyleg', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

	array(

		'name' => '模特', // 帖吧名

		'en' => 'mote', // 拼音

		'p_start' => 1, // 开始页数

		'p_end' => 5, // 结束页数

	),

);

$img_l = 3; // 抛弃少于这个数量的图集

$img_dir = '/data/tbimg/'; // 图片存储目录

//  ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ [ 配置结束 ] ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑

$red = "\33[31m";

$green = "\33[32m";

$blue = "\33[34m";

$end = "\33[0m";

date_default_timezone_set("Asia/Shanghai"); //函数设置用在脚本中所有日期/时间函数的默认时区。语法 date_default_timezone_set(timezone)，参考http://php.net/manual/zh/timezones.php

foreach ($tbs as $tb) {
	//PHP循环语句，此处foreach和for两个循环
	//语法
	//foreach ($array as $key=>$value)  或者为  foreach ($array as $value)
	//{
	//……
	//}

	for ($p = $tb['p_start']; $p <= $tb['p_end']; $p++) {
		//for 也是循环函数，功能和foreach差不多，语法没查到

		// 帖吧列表地址，带页数。

		$tb_list = "http://tieba.baidu.com/f?kw=" . urlencode($tb['name']) . "&pn=" . ($p - 1) * 50 . "&cid=0";
		//. urlencode   此函数便于将字符串编码并将其用于 URL 的请求部分，同时它还便于将变量传递给下一页。语法 string urlencode ( string $str )
		//http://tieba.baidu.com/f?ie=utf-8&kw=%E7%BE%8E%E5%A5%B3
		//http://tieba.baidu.com/f?ie=utf-8&kw=%E7%BE%8E%E8%85%BF#/pn=50

		// 抓取帖吧列表页面HTML

		$html_list = get_html($tb_list);

		// 匹配帖子

		preg_match_all("/j_threadlist_li_right.+?href=\"\/p\/(\d+?)\" title=\"(.+?)\".+?tb_icon_author \"\>\<a.*?\>(.+?)\<\/a\>/ms",
			$html_list, $preg_list);
		//preg_match_all,字符串整体比对解析.语法: int preg_match_all(string pattern, string subject, array matches, int [order]);

		// 整理数组

		$ts = array();

		foreach ($preg_list[1] as $key => $val) {

			$ts[] = array(

				'id' => $val,

				'title' => $preg_list[2][$key],

				'author' => $preg_list[3][$key],

			);

		}

		// 遍历帖子

		foreach ($ts as $t) {

			$t_id = $t['id'];

			$t_title = $t['title'];

			$t_author = $t['author'];

			echo "{$green}{$tb['en']} P{$p}: $t_title{$end}\n";

			// 检查是否已经下载过

			if (get_log($t_id)) {

				echo "{$red}fetched. skip.{$end}\n\n";

				continue;

			}

			// 抓取第一页HTML

			$t_url = "http://tieba.baidu.com/p/{$t_id}?see_lz=1";

			echo "get page: $t_url\n";

			$t_html = get_html($t_url);

			// 获取最大页数

			preg_match("/pn=(\d*?)\"\>尾页\<\/a\>.\<\/li\>/ms", $t_html, $preg_page);

			$page_max = isset($preg_page[1]) ? $preg_page[1] : 1;

			// 路过高楼层

			if ($page_max > 10) {

				echo "{$red}max page: {$page_max}. skip.{$end}\n\n";

				continue;

			}

			// 合并所有页面HTML

			$t_all_html = $t_html;

			for ($i = 2; $i <= $page_max; $i++) {

				$t_url_page = "{$t_url}&pn={$i}";

				echo "get page: $t_url_page\n";

				$t_all_html .= get_html($t_url_page);

			}

			preg_match_all("/\<cc id=\"post_content_.+?\">(.*?)<\/cc\>/ms",

				$t_all_html, $preg_t);

			$post = implode($preg_t[1]);

			// 匹配图片

			$img_num = preg_match_all("/\<img.*?src=\"(https:\/\/imgsa.baidu.com\/forum\/.+?\/sign=\/.+?\/.+?)\".*?>/ms", $post, $preg_img);

			// 下载图片

			if ($img_num >= $img_l) {

				$t_dir = $img_dir . $tb['en'] . date("/Ym/d/") . $t_id . '/'; // 本帖图片存储目录

				echo "dir: $t_dir\n";

				@mkdir($t_dir, 0755, TRUE);

				file_put_contents($t_dir . 'title.txt', $t_title); // 写入标题到文本文件

				foreach ($preg_img[1] as $img) {

					get_img($img, $t_dir);

					echo "$img\n";

				}

			} else {

				echo "{$red}$img_num img. skip.{$end}\n";

			}

			set_log($t_id);

			echo "\n";

		}

	}

}

// 抓取URL HTML

function get_html($url) {

	while (true) {

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_TIMEOUT, 3);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($ch, CURLOPT_USERAGENT,
			"Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");

		curl_setopt($ch, CURLOPT_COOKIE, "TIEBAPB=OLDPB");

		$html = curl_exec($ch);

		curl_close($ch);

		if (!empty($html)) {

			return $html;

		}

		sleep(1);

	}

}

function get_img($img, $dir) {

	$cmd = "/usr/bin/wget -c -t 3 -T 3 {$img} -P '$dir' > /dev/null 2>&1";

	shell_exec($cmd);

}

function set_log($id) {

	$log_dir = '/var/log/tbimg/';

	!file_exists($log_dir) && mkdir($log_dir);

	touch($log_dir . $id);

}

function get_log($id) {

	$log_dir = '/var/log/tbimg/';

	if (file_exists($log_dir . $id)) {

		return TRUE;

	} else {

		return FALSE;

	}

}