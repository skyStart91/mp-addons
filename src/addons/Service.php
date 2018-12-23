<?php
namespace think\addons;
use think\Exception;
class Service{
	/**
	 * 解压插件
	 */
	public function getUnZipAddons($name = '', $conflict = false){
		if(empty($name)) throw new Exception('解压文件不能为空');
		$zipFilePath = RUNTIME_PATH.'addons'.DS.$name.'.zip';
		// 解压目录
		$dir = ADDON_PATH.$name.DS;
		if(class_exists('ZipArchive')){
			$zip = new \ZipArchive;
			// 打开文件异常抛错
			if($zip->open($zipFilePath) != true){
				throw new Exception('打开压缩文件错误');
			}
			// 冲突检测
			if($conflict && (self::conflictAddons($name) === false)) throw new Exception('该文件已冲突,请重新修改名称');
			// 解压文件
			if(!$zip->extractTo($dir)){
				// 关闭文件抛错
				$zip->close();
				throw new Exception('解压文件移动异常');
			}
			$zip->close();
			return $dir;
		}
		exit(returnJson('无法执行解压操作, 请确认是否安装ZIP扩展', 0, '', true));
	}

	/**
	 * 备份插件
	 */
	public function backUpAddons(){

	}

	/**
	 * 检查冲突
	 */
	public static function conflictAddons($name){
		$list = new \FilesystemIterator(ADDON_PATH);
		$dirArr = [];
		foreach($list as $val){
			array_push($dirArr, $val->getFilename());
		}
		if(in_array($name, $dirArr)) return false;
		return true;
	}
	
	 /**
	 * 安装插件
	 */
	public function installAddons(){

	}

	 
	/**
	 * 卸载插件
	 */
	public function uninstallAddons(){

	}

	/**
	 * 导入Sql
	 */
	public function importSql(){

	}

	/**
	  * 启用插件
	  */
	public function resumeAddons(){

	}

	/**
	 * 禁用插件
	 */
	public function forbidAddons(){

	}
}