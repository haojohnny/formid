<?php
// +----------------------------------------------------------------------
// | 微信form-id管理工具
// +----------------------------------------------------------------------
// | @Author: Johnny   					  			
// +----------------------------------------------------------------------


namespace Miniprogram\Formid;

class FormId
{
	// formId的缓存配置
	protected $config = [
		'prefix' => 'form_id_',
		'count' => 50,
		'expire' => '7 days'
	];

	protected $redis = null;

	/**
	 * FormId constructor.
	 * @param \redis|null $redis
	 * @param array $config
	 * @throws \Exception
	 */
	public function __construct(\redis $redis = null, array $config = [])
	{
		if (empty($redis)) {
			$redis = (new Redis())->getHandler();
		}
		$this->redis = $redis;

		if (!empty($config)) {
			$this->config = array_merge($this->config, $config);
		}
	}

	/**
	 * @return \redis
	 */
	public function getRedis()
	{
		return $this->redis;
	}

	/**
	 * @param $uniqueId
	 * @return string
	 */
	public function getKey($uniqueId)
	{
		return $this->config['prefix'].$uniqueId;
	}

	/**
	 * @param $uniqueId
	 * @param $formId
	 */
	public function save($uniqueId, $formId)
	{
		$key = self::getKey($uniqueId);

		$expire_time = strtotime('+'.$this->config['expire']);
		$this->getRedis()->zAdd($key, $expire_time, $formId);

		if (($count = self::count($uniqueId)) > $this->config['count']) {
			$this->getRedis()->zRemRangeByRank($key, 0, $count - $this->config['count'] - 1);
		}

		$this->getRedis()->expire($key, $expire_time);
	}

	/**
	 * @param $uniqueId
	 * @param $prepayId
	 */
	public function savePrepayId($uniqueId, $prepayId)
	{
		for ($i=0; $i<3; $i++) {
			self::save($uniqueId, $prepayId);
		}
	}

	/**
	 * @param $uniqueId
	 * @return string
	 */
	public function get($uniqueId)
	{

		$formId = null;

		self::rmInvalid($uniqueId);
		$formIds = $this->getRedis()->zRange(self::getKey($uniqueId), 0, 0, false);
		if (!empty($formIds)) {
			self::del($uniqueId, $formId = $formIds[0]);
		}

		return $formId;
	}

	/**
	 * @param $uniqueId
	 * @param $formId
	 * @return mixed
	 */
	public function del($uniqueId, $formId)
	{
		return $this->getRedis()->zDelete(self::getKey($uniqueId), $formId);
	}

	/**
	 * @param $uniqueId
	 * @return mixed
	 */
	public function rmInvalid($uniqueId)
	{
		return $this->getRedis()
			->zRemRangeByScore(self::getKey($uniqueId), 0, strtotime('-'.$this->config['expire']));
	}

	/**
	 * @param $uniqueId
	 * @return int
	 */
	public function count($uniqueId)
	{
		self::rmInvalid($uniqueId);
		return $this->getRedis()->zCard(self::getKey($uniqueId));
	}
}