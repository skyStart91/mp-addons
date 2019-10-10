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
		if(!$name) throw new Exception('插件'.$name.'不存在');
		
		// 检查配置的完整性
		$sAddons = get_addon_class($name);
		$oAddons = new $sAddons;
		if(!$oAddons->checkInfo()){
			throw new Exception('插件'.$name.'配置不完整');
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
	 * @param string $addonName 插件名称
	 */
	public static function installAddons($addonName = ''){
		if(empty($addonName)) exit(returnJson('error', 0));
		
		// 1.检查插件配置是否完整[包括baseConfig和config]
		self::checkAddonsFullConfig($addonName);
		
		self::installOrUninstall($addonName, 1);
		return true;
	}
	 
	/**
	 * 卸载插件
	 */
	public static function uninstallAddons($addonName){
		self::installOrUninstall($addonName, 0);
		return true;
	}

	/**
	 * 插件安装/卸载公共方法
	 * $fg int 1:安装 0:卸载
	 */
	private static function installOrUninstall($addonName, $fg){
		// 1.判断插件是否已安装/卸载
		$addonsBaseConfig = getBaseConfig($addonName);
		
		if($fg == $addonsBaseConfig['status']) exit(returnJson('插件'.$addonName.'已'.($fg? '安装': '卸载'), 0));

		// 2.将baseConfig中的status改为0即可
		$class = get_addon_class($addonName);
		
		$dirName = dirname((new $class)->config_file);

		$addonsBaseConfig['status'] = $fg;

		file_put_contents($dirName.DS.'baseConfig.php', "<?php \n return ".var_export($addonsBaseConfig, true).';');

		// 3.如果有sql导入了需要卸载
		self::importSql($addonName, $fg);
		return true;
	}

	/**
	 * 导入Sql
	 */
	public function importSql($addonName){
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
	 * 启用/禁用插件
	 * @param int $fg 1启用，0禁用
	 */
	public static function resumeOrForbidAddon($addonName, $fg){
		$adResult = self::isReFb($addonName, $fg);
		switch($adResult){
			case -1: exit(returnJson('请先安装'.$addonName.'插件再操作', 0));
			case -2: exit(returnJson('插件'.$addonName.'已'.($fg? '启用': '禁用').',无需重复操作', 0));
		}
		return true;
	}

	/**
	 * 插件是否安装
	 */
	public static function alreadyInstall($addonName){
		// 查看该插件是否已安装
		$baseAddonConfig = getBaseConfig($addonName);
		
		if(0 == $baseAddonConfig['status']) return false;
		
		return true;
	}

	/**
	 * 插件是否启用
	 */
	private static function isReFb($addonName, $resultFlag){
		// 查看是否已安装
		$bResult = self::alreadyInstall($addonName);
		
		if(!$bResult) return -1; // 未安装

		// 使用已启/禁用
		if($resultFlag == get_addon_config($addonName)['display']) return -2;

		$class = get_addon_class($addonName);
		
		$addonConfigPath = (new $class)->config_file;

		$addonConfig = include $addonConfigPath;

		$addonConfig['display']['value'] = $resultFlag;

		file_put_contents($addonConfigPath, "<?php \n return ".var_export($addonConfig, true).";");

		return 0;
	}
}
