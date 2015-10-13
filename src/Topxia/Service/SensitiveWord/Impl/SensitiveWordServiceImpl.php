<?php
namespace Topxia\Service\SensitiveWord\Impl;

use Topxia\Service\Common\BaseService;
use Topxia\Service\SensitiveWord\SensitiveWordService;
use Topxia\Service\SensitiveWord\Type\QuestionTypeFactory;
use Topxia\Common\ArrayToolkit;

class SensitiveWordServiceImpl extends BaseService implements SensitiveWordService
{
	public function filter($str)
	{
		$originStr = $str;
		$sensitiveWordSetting = $this->getSettingService()->get("sensitiveWord", array());
		if(isset($sensitiveWordSetting["wordReplace"]) && !empty($sensitiveWordSetting["wordReplace"])) {
			$str = $this->replace($str, $sensitiveWordSetting["wordReplace"]);
		}

		if(isset($sensitiveWordSetting["ignoreWord"]) && !empty($sensitiveWordSetting["ignoreWord"])) {
			$str = $this->ignoreWord($str, $sensitiveWordSetting["ignoreWord"]);
		}

		if(isset($sensitiveWordSetting["firstLevel"]) && !empty($sensitiveWordSetting["firstLevel"])) {
			$this->findFirstLevel($str, $sensitiveWordSetting["firstLevel"]);
		}

		if(isset($sensitiveWordSetting["secondLevel"]) && !empty($sensitiveWordSetting["secondLevel"])) {
			$str = $this->findSecondLevel($str, $sensitiveWordSetting["secondLevel"]);
		}

		return $str;
	}

	protected function replace($str, $wordReplace)
	{
		$array = json_decode($wordReplace, true);
		return strtr($str, $array);

	}

	protected function ignoreWord($str, $ignoreWord)
	{
		return preg_replace('['.$ignoreWord.']', '', $str);
	}

	protected function findFirstLevel($str, $firstLevel)
	{
		preg_match('['.$firstLevel.']', $str, $result);
		if(empty($result)){
			return $str;
		} else {
			throw $this->createServiceException("内容中包含敏感词");
		}
	}

	protected function findSecondLevel($str, $secondLevel)
	{
		preg_match_all('['.$secondLevel.']', $str, $result, PREG_OFFSET_CAPTURE);
		if(empty($result)){
			return $str;
		} else {
			$keywordFilter = new KeywordFilter();
			$keywordFilter->addKeywords(array('中国','中华人民共和国','美国','美国人','美国黑人'));
			var_dump($keywordFilter->find('我们都是中国人'));
		}
	}

	protected function getSettingService()
    {
        return $this->createService('System.SettingService');
    }

}