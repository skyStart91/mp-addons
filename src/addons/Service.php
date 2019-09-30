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
		if(!is_file($zipFilePath)) throw new Exception($name.'文件不存在');
		// 解压目录
		$dir = ADDON_PATH.$name.DS;
		if(class_exists('ZipArchive')){
			$zip = new \ZipArchive;
			// 打开文件异常抛错
			if($zip->open($zipFilePath) != true){
				throw new Exception('打开压缩文件错误');
			}
			exit;
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
	 * 查看插件配置是否完整
	 */
	public function checkAddonsFullConfig($name){
		// 检测插件是否存在
		if(!$name) throw new Exception('插件不存在');
		
		// 检查配置的完整性
		$sAddons = get_addon_class($name);
		$oAddons = new $sAddons;
		if(!$oAddons->checkInfo()){
			throw new Exception('插件配置不完整');
		}
		return true;
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
	public function installAddons($addonName){
		if(empty($addonName)) exit(returnJson('error', 0));
		// 1.检查插件配置是否完整[包括baseConfig和config]
		
		// self::checkAddonsFullConfig($addonName);
		
		// 2.判断插件是否已安装
		$addonsBaseConfig = getBaseConfig($addonName);
		
		// 3.将baseConfig中的status改为1
		if($addonsBaseConfig['status'] == 1) exit(returnJson('插件已安装', 0));

		// 4.查看是否需要有sql导入
		self::importSql();
		
		return true;
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
		// 当前插件对象
		$class = get_addon_class($addonName);
		
		$dirName = dirname((new $class)->config_file);
		
		// sql文件名必须和插件名相同
		$sqlPath = $dirName.DS	.$addonName.'.sql';
		
		if(file_exists($sqlPath)){
			$sql = file_get_contents($sqlPath);
			
			// 解析sql语句
			$vSql = explode(';', $sql);

			\app\common\model\Common::doQuery($vSql);

			return true;
		}
		return false;
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
