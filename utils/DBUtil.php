<?php

class DBUtil
{
	private $config = [
		'host' => '',
		'dbname' => '',
		'user'	=> '',
		'pass' => '',
	];
	protected $pdo;

	protected $table = '';
	protected $fields = [];

	public $debug = false;

	/**
	 * Constructor
	 * 
	 * @param mixed a db config array or PDO instance
	 */
	public function __construct($config)
	{
		if ($config instanceof PDO) {
			$this->pdo = $config;
		} elseif (is_array($config)) {
			$this->config = $cfg = array_merge($this->config, $config);

			try {
				$this->pdo = self::PDO($cfg);
			} catch (PDOException $e) {
				$this->db_exception($e);
			}
		} else {
			throw new Exception('Invalid config array!');
		}
	}

	public static function PDO($cfg)
	{
		$pdo = new PDO("mysql:host=$cfg[host];dbname=$cfg[dbname]", $cfg['user'], $cfg['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		return $pdo;
	}

	public function begin_transaction()
	{
		$this->pdo->beginTransaction();
	}

	public function commit()
	{
		$this->pdo->commit();
	}

	public function rollback()
	{
		$this->pdo->rollback();
	}

	public function db_exception($e)
	{
		if ($e instanceof PDOException) {
			return ['error' => ($this->debug) ? $e->getMessage() : 'DB Error.'];
		}
		throw $e;
	}

	public function get_connection()
	{
		return $this->pdo;
	}

	public function filter_fields(array $record, array $allowed = null)
	{
		$allowed ??= array_keys($this->fields);
		return array_filter(
			$record,
			fn ($k) => in_array($k, $allowed),
			ARRAY_FILTER_USE_KEY
		);
	}
}
