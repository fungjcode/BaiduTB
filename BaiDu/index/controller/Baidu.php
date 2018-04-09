<?php
/*
 * 百度贴吧相关采集
 */
namespace app\index\controller;
use think\Controller;

class Baidu extends Controller {
	// 抓取URL HTML,  get_html 方法
	public function get_html($url) {
		while (true) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)");
			curl_setopt($ch, CURLOPT_COOKIE, "TIEBAPB=OLDPB");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //SSL
			$html = curl_exec($ch);
			curl_close($ch);
			if (!empty($html)) {
				return $html;
			}
			sleep(3);
		}
	}

	//检查是否下载过
	public function get_log($id) {
		$log_dir = '/public/imgupload/'; //图片的下载路径
		if (file_exists($log_dir . $id)) {
			return true;
		} else {
			return false;
		}
	}

	//获取列表
	public function getlist() {
		date_default_timezone_set("Asia/Shanghai");
		//获取要采集的贴吧数据
		$tbs = \think\Db::name('bdconfig')->where("str = 0")->order('score asc')->limit(1)->select();
		foreach ($tbs as $tb) {
			\think\Log::record($tb['name'] . '目前正在更新该栏目，该栏目目前采集了次数为：' . $tb['score'], 'info');
			//执行页数循环
			for ($p = $tb['p_start']; $p <= $tb['p_end']; $p++) {
				// 帖吧列表地址，带页数。
				$tb_list = 'https://tieba.baidu.com/f?kw=' . urlencode($tb['name']) . '&ie=utf-8' . '&pn=' . (($p - 1) * 50) . '&red_tag=c' . rand(2000000000, 299999999);
				// 抓取帖吧列表页面HTML，使用上面的方法
				//$html_list = $this->get_html($tb_list);
				$html_list = file_get_contents($tb_list);
				// 匹配帖子,获取ID，标题，作者
				preg_match_all("/j_threadlist_li_right.+?href=\"\/p\/(\d+?)\" title=\"(.+?)\" .+?title=\"(.+?)\".+?<\"title\">(.+?)/ms", $html_list, $preg_list);
				//preg_match_all,字符串整体比对解析.语法: int preg_match_all(string pattern, string subject, array matches, int [order]);
				// 整理数组
				$ts = array();
				foreach ($preg_list[1] as $key => $val) {
					$column = $tb['name'];
					$ts[] = array(
						'id' => $val, //文章ID
						'title' => $preg_list[2][$key], //标题
						'author' => substr($preg_list[3][$key], 14), //作者，去掉前面莫名其妙的东西
						'column' => $tb['name'], //所属栏目
					);
					//过滤掉Emoji表情
					$title = $preg_list[2][$key];
					$cetitle = preg_match('/[\xf0-\xf7].{3}/', $title);
					if ($cetitle == 0) {
						//查询重复，IMG表中有数据说明采集成功,TMP表中存在说明已经获取过列表,两个表都为空则说明是没采集过的
						$cdb = \think\Db::name('imginfo')->where("baiduid = '$val'")->find();
						$ddb = \think\Db::name('tmpinfo')->where("baiduid = '$val'")->find();
						if (empty($cdb)) {
							if (empty($ddb)) {
								//写入到数据中
								$data['icolumn'] = $tb['name'];
								$data['title'] = $preg_list[2][$key];
								$data['author'] = base64_encode(substr($preg_list[3][$key], 14));
								$data['baiduid'] = $val;
								$data['uptime'] = time();
								$data['getstr'] = '0'; //初始化，未采集
								$indb = \think\Db::name('tmpinfo')->insert($data);
								if (!$indb) {
									\think\Log::record($tb['name'] . '栏目下ID为：' . $val . '的帖子,' . $preg_list[2][$key] . ',数据库写入失败', 'info');
									continue;
								}
							} else {
								//如果tmp里面存在这个内容，那么也不采集
								//\think\Db::name('tmpinfo')->where("baiduid = '$val'")->update(['getstr' => '1']);
								\think\Log::record($tb['name'] . '栏目下ID为：' . $val . '的在帖子,' . $preg_list[2][$key] . '已经在tmpinfo存在，不采集', 'info');
								continue;
							}
						} else {
							//如果帖子存在，就说明TMP里面不需要在采集，更新状态为1已经采集
							\think\Db::name('tmpinfo')->where("baiduid = '$val'")->update(['getstr' => '1']);
							\think\Log::record($tb['name'] . '栏目下ID为：' . $val . '的在帖子,' . $preg_list[2][$key] . '已经在imginfo存在，不采集', 'info');
							continue;
						}
					} else {
						\think\Log::record($tb['name'] . '栏目下ID为：' . $val . '的在帖子,' . $preg_list[2][$key] . '帖子存在Emoji表情，不采集', 'info');
						continue;
					}
				}
			}
			//更新采集次数
			\think\Db::name('bdconfig')->where('name', $column)->setInc('score');
			echo "\n";
			exit();
		}
	}

	//采集图片的方法
	public function getimg() {
		$img_l = 4; // 抛弃少于这个数量的图集
		$img_dir = './public/imgupload/'; //图片的下载路径
		date_default_timezone_set("Asia/Shanghai");
		$ts = \think\Db::name('tmpinfo')->where("getstr = 0")->limit(1)->select(); //单条采集
		if (!empty($ts)) {
			// 遍历帖子
			foreach ($ts as $t) {
				//获取采集参数
				$t_id = $t['baiduid'];
				$t_title = $t['title'];
				$t_author = $t['author'];
				$icolumn = $t['icolumn'];
				//判断是否采集
				$cedb = \think\Db::name('imginfo')->where("baiduid = '$t_id'")->select();
				if (empty($cedb)) {
					//更新目前已采集的状态为1
					\think\Db::name('tmpinfo')->where("baiduid = '$t_id'")->update(['getstr' => '1']);
					//获取英文标题等
					$tb = \think\Db::name('bdconfig')->where("name = '$icolumn'")->find();
					// 抓取第一页HTML
					$t_url = "https://tieba.baidu.com/p/{$t_id}?pn=1";
					//$t_html = $this->get_html($t_url);
					$t_html = file_get_contents($t_url);
					// 获取最大页数
					preg_match("/pn=(\d*?)\"\>尾页\<\/a\>.\<\/li\>/ms", $t_html, $preg_page);
					$page_max = isset($preg_page[1]) ? $preg_page[1] : 1;
					// 路过高楼层,20层
					if ($page_max > 20) {
						\think\Log::record($t_id . '的帖子,' . $t_title . ',楼层数大于20层，跳过', 'info');
						$this->getimg();
					}
					// 合并所有页面HTML
					$t_all_html = $t_html;
					for ($i = 2; $i <= $page_max; $i++) {
						$t_url_page = "{$t_url}?pn={$i}";
						$t_all_html .= $this->get_html($t_url_page);
						//$t_all_html .= file_get_contents($t_url_page);
					}
					preg_match_all("/\<div id=\"post_content_.+?\">(.*?)<\/div\>/ms", $t_all_html, $preg_t);
					$post = implode($preg_t[1]);
					//少判断的排除PIC等其他的
					$img_num = preg_match_all("/\<img.*?src=\"(https:\/\/imgsa.baidu.com\/forum\/.*?.+?)\".*?>/ms", $post, $preg_img);
					//数组去重
					$arrc = array_unique($preg_img[1]);
					//计算图片数量
					$imga_num = count($arrc);
					// 下载图片
					if ($imga_num >= $img_l) {
						$t_dir = $img_dir . $tb['en'] . date("/Ym/d/") . $t_id . '/'; // 本帖图片存储目录
						@mkdir($t_dir, 0755, true);
						//file_put_contents($t_dir . 'title.txt', $t_title); // 写入标题到文本文件
						foreach ($preg_img[1] as $img) {
							$content = file_get_contents($img);
							if ($content) {
								//把图片地址切成数组，获取文件名
								$flinename = explode('/', $img);
								/** 图片保存到本地图片路径 */
								$imgurl = $t_dir . $flinename['6']; //文件名
								$dbimgurl[] = $imgurl; //数组图片地址
								$saveimg = file_put_contents($imgurl, $content); //获取路劲+获取得图片
							} else {
								//更新目前已采集的状态为1
								\think\Db::name('tmpinfo')->where("baiduid = '$t_id'")->update(['getstr' => '1']);
								\think\Log::record($t_id . '的帖子,' . $t_title . ',图片获取失败，跳过', 'info');
								continue;
							}
						}
						//写入到图片信息数据库
						$idata['baiduid'] = $t_id;
						$idata['title'] = $t_title;
						$idata['author'] = $t_author;
						$idata['uptime'] = time();
						$idata['icolumn'] = $icolumn;
						$idata['str'] = '0';
						\think\Db::name('imginfo')->insert($idata);
						//数组去重
						$arrtr = array_unique($dbimgurl);
						//把图片更新到表里面
						$imgdb['imgurl'] = implode("|", $arrtr);
						\think\Db::name('imginfo')->where("baiduid = '$t_id' and title = '$t_title'")->update($imgdb);
						//采集完成
						exit();
					} else {
						//更新目前已采集的状态为1
						\think\Db::name('tmpinfo')->where("baiduid = '$t_id'")->update(['getstr' => '1']);
						\think\Log::record($t_id . '的帖子,' . $t_title . ',该帖子小于4张图片，跳过', 'info');
						$this->getimg();
					}
				} else {
					//更新目前已采集的状态为1
					\think\Db::name('tmpinfo')->where("baiduid = '$t_id'")->update(['getstr' => '1']);
					\think\Log::record($t_id . '的帖子,' . $t_title . ',已经存在，不重复采集', 'info');
					$this->getimg();
				}
			}
		} else {
			//没有需要采集的
			\think\Log::record('当前没有需要采集的数据', 'info');
			exit();
		}
	}

}

?>