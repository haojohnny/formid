<?php
// +----------------------------------------------------------------------
// | 微信form-id管理工具
// +----------------------------------------------------------------------
// | @Author: Johnny   					  			
// +----------------------------------------------------------------------


namespace Haojohnny\Formid;

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
		$key = $this->getKey($uniqueId);

		$expire_time = strtotime('+'.$this->config['expire']);
		$this->redis->zAdd($key, $expire_time, $formId);

		if (($count = $this->count($uniqueId)) > $this->config['count']) {
			$this->redis->zRemRangeByRank($key, 0, $count - $this->config['count'] - 1);
		}

		$this->redis->expire($key, $expire_time);
	}

	/**
	 * @param $uniqueId
	 * @param $prepayId
	 */
	public function savePrepayId($uniqueId, $prepayId)
	{
		for ($i=0; $i<3; $i++) {
			$this->save($uniqueId, $prepayId);
		}
	}

	/**
	 * @param $uniqueId
	 * @return string
	 */
	public function get($uniqueId)
	{

		$formId = null;

		$this->rmInvalid($uniqueId);
		$formIds = $this->redis->zRange($this->getKey($uniqueId), 0, 0, false);
		if (!empty($formIds)) {
			$this->del($uniqueId, $formId = $formIds[0]);
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
		return $this->redis->zDelete($this->getKey($uniqueId), $formId);
	}

	/**
	 * @param $uniqueId
	 * @return mixed
	 */
	public function rmInvalid($uniqueId)
	{
		return $this->redis
			->zRemRangeByScore($this->getKey($uniqueId), 0, strtotime('-'.$this->config['expire']));
	}

	/**
	 * @param $uniqueId
	 * @return int
	 */
	public function count($uniqueId)
	{
		$this->rmInvalid($uniqueId);
		return $this->redis->zCard($this->getKey($uniqueId));
	}
}