<?php
// +----------------------------------------------------------------------
// | Redis连接
// +----------------------------------------------------------------------
// | @Author: Johnny   					  			
// +----------------------------------------------------------------------


namespace Haojohnny\Formid;

class Redis
{
	/**
	 * @var \Redis|null
	 */
	protected $handler = null;

	/**
	 * @var array
	 */
	protected $options = [
		'host'       => 'redis',
		'port'       => 6379,
		'password'   => '',
		'select'     => 0,
		'timeout'    => 0,
		'expire'     => 0,
		'persistent' => false,
	];

	/**
	 * Redis constructor.
	 * @param array $options
	 * @throws \Exception
	 */
	public function __construct($options = [])
	{
		if (!extension_loaded('redis')) {
			throw new \Exception('not support: redis');
		}

		if (!empty($options)) {
			$this->options = array_merge($this->options, $options);
		}

		$func = $this->options['persistent'] ? 'pconnect' : 'connect';
		$this->handler = new \Redis;
		$this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

		if ('' != $this->options['password']) {
			$this->handler->auth($this->options['password']);
		}

		if (0 != $this->options['select']) {
			$this->handler->select($this->options['select']);
		}
	}

	/**
	 * @return \Redis|null
	 */
	public function getHandler()
	{
		return $this->handler;
	}
}