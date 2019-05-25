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
		'count'  => 50,
		'expire' => '7 days'
	];

	/**
	 * @var \Redis|null
	 */
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
	 * 获取缓存key
	 * @param $uniqueId
	 * @return string
	 */
	public function getKey($uniqueId)
	{
		return $this->config['prefix'].$uniqueId;
	}

	/**
	 * 保存form-id
	 * @param $uniqueId
	 * @param $formId
	 * @return bool
	 */
	public function save($uniqueId, $formId)
	{
		$key = $this->getKey($uniqueId);

		$expire_time = strtotime('+'.$this->config['expire']);
		$this->redis->zAdd($key, $expire_time, $formId);

		if (($count = $this->count($uniqueId)) > $this->config['count']) {
			$this->redis->zRemRangeByRank($key, 0, $count - $this->config['count'] - 1);
		}

		return $this->redis->expire($key, $expire_time);
	}

	/**
	 * 保存prepay-id
	 * @param $uniqueId
	 * @param $prepayId
	 * @return bool
	 */
	public function savePrepayId($uniqueId, $prepayId)
	{
		for ($i=0; $i<3; $i++) {
			$this->save($uniqueId, $prepayId);
		}

		return true;
	}

	/**
	 * 获取form-id
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
	 * 删除指定form-id
	 * @param $uniqueId
	 * @param $formId
	 * @return mixed
	 */
	public function del($uniqueId, $formId)
	{
		return $this->redis->zDelete($this->getKey($uniqueId), $formId);
	}

	/**
	 * 移除无效form-id
	 * @param $uniqueId
	 * @return mixed
	 */
	public function rmInvalid($uniqueId)
	{
		return $this->redis
			->zRemRangeByScore($this->getKey($uniqueId), 0, strtotime('-'.$this->config['expire']));
	}

	/**
	 * 获取form-id个数
	 * @param $uniqueId
	 * @return int
	 */
	public function count($uniqueId)
	{
		$this->rmInvalid($uniqueId);
		return $this->redis->zCard($this->getKey($uniqueId));
	}

	/**
	 * 清除缓存
	 * @param $uniqueId
	 * @return int
	 */
	public function clear($uniqueId)
	{
		return $this->redis->del($this->getKey($uniqueId));
	}
}