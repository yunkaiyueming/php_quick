<?php

define('CRLF', "\r\n");

class mredis {

	/**
	 * The address for the m_redis host.
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The port on which m_redis can be accessed on the host.
	 *
	 * @var int
	 */
	protected $port;

	/**
	 * The database password, if present.
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * The database number the connection selects on load.
	 *
	 * @var int
	 */
	protected $database;

	/**
	 * The connection to the m_redis database.
	 *
	 * @var resource
	 */
	protected $connection;

	/**
	 * The active m_redis database instances.
	 *
	 * @var array
	 */
	protected static $databases = array();

	/**
	 * Create a new m_redis connection instance.
	 *
	 * @param  string  $host
	 * @param  string  $port
	 * @param  int     $database
	 * @return void
	 */
	public function __construct($host, $port, $password = null, $database = 0)
	{
		$this->host = $host;
		$this->port = $port;
		$this->password = $password;
		$this->database = $database;
	}

	/**
	 * Get a m_redis database connection instance.
	 *
	 * The given name should correspond to a m_redis database in the configuration file.
	 *
	 * <code>
	 *		// Get the default m_redis database instance
	 *		$m_redis = m_redis::db();
	 *
	 *		// Get a specified m_redis database instance
	 *		$reids = m_redis::db('m_redis_2');
	 * </code>
	 *
	 * @param  string  $name
	 * @return m_redis
	 */
	public static function db($name = 'default',$config)
	{
		if ( ! isset(static::$databases[$name]))
		{
			extract($config);

			if ( ! isset($password))
			{
				$password = null;
			}

			static::$databases[$name] = new static($host, $port, $password, $database);
		}

		return static::$databases[$name];
	}

	/**
	 * Execute a command against the m_redis database.
	 *
	 * <code>
	 *		// Execute the GET command for the "name" key
	 *		$name = m_redis::db()->run('get', array('name'));
	 *
	 *		// Execute the LRANGE command for the "list" key
	 *		$list = m_redis::db()->run('lrange', array(0, 5));
	 * </code>
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function run($method, $parameters)
	{
		fwrite($this->connect(), $this->command($method, (array) $parameters));

		$response = trim(fgets($this->connection, 512));

		return $this->parse($response);
	}

	/**
	 * Parse and return the response from the m_redis database.
	 *
	 * @param  string  $response
	 * @return mixed
	 */
	protected function parse($response)
	{
		switch (substr($response, 0, 1))
		{
			case '-':
				throw new \Exception('m_redis error: '.substr(trim($response), 4));
			
			case '+':
			case ':':
				return $this->inline($response);
			
			case '$':
				return $this->bulk($response);
			
			case '*':
				return $this->multibulk($response);
			
			default:
				throw new \Exception("Unknown m_redis response: ".substr($response, 0, 1));
		}
	}

	/**
	 * Establish the connection to the m_redis database.
	 *
	 * @return resource
	 */
	protected function connect()
	{
		if ( ! is_null($this->connection)) return $this->connection;

		$this->connection = @fsockopen($this->host, $this->port, $error, $message);		

		if ($this->connection === false)
		{
			throw new \Exception("Error making m_redis connection: {$error} - {$message}");
		}

		if ( $this->password )
		{
			$this->auth($this->password);
		}

		$this->select($this->database);

		return $this->connection;
	}

	/**
	 * Build the m_redis command based from a given method and parameters.
	 *
	 * m_redis protocol states that a command should conform to the following format:
	 *
	 *     *<number of arguments> CR LF
	 *     $<number of bytes of argument 1> CR LF
	 *     <argument data> CR LF
	 *     ...
	 *     $<number of bytes of argument N> CR LF
	 *     <argument data> CR LF
	 *
	 * More information regarding the m_redis protocol: http://m_redis.io/topics/protocol
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return string
	 */
	protected function command($method, $parameters)
	{
		$command  = '*'.(count($parameters) + 1).CRLF;

		$command .= '$'.strlen($method).CRLF;

		$command .= strtoupper($method).CRLF;

		foreach ($parameters as $parameter)
		{
			$command .= '$'.strlen($parameter).CRLF.$parameter.CRLF;
		}

		return $command;
	}

	/**
	 * Parse and handle an inline response from the m_redis database.
	 *
	 * @param  string  $response
	 * @return string
	 */
	protected function inline($response)
	{
		return substr(trim($response), 1);
	}

	/**
	 * Parse and handle a bulk response from the m_redis database.
	 *
	 * @param  string  $head
	 * @return string
	 */
	protected function bulk($head)
	{
		if ($head == '$-1') return;

		list($read, $response, $size) = array(0, '', substr($head, 1));

		if ($size > 0)
		{
			do
			{
				// Calculate and read the appropriate bytes off of the m_redis response.
				// We'll read off the response in 1024 byte chunks until the entire
				// response has been read from the database.
				$block = (($remaining = $size - $read) < 1024) ? $remaining : 1024;

				$response .= fread($this->connection, $block);

				$read += $block;

			} while ($read < $size);
		}

		// The response ends with a trailing CRLF. So, we need to read that off
		// of the end of the file stream to get it out of the way of the next
		// command that is issued to the database.
		fread($this->connection, 2);

		return $response;
	}

	/**
	 * Parse and handle a multi-bulk reply from the m_redis database.
	 *
	 * @param  string  $head
	 * @return array
	 */
	protected function multibulk($head)
	{
		if (($count = substr($head, 1)) == '-1') return;

		$response = array();

		// Iterate through each bulk response in the multi-bulk and parse it out
		// using the "parse" method since a multi-bulk response is just a list
		// of plain old m_redis database responses.
		for ($i = 0; $i < $count; $i++)
		{
			$response[] = $this->parse(trim(fgets($this->connection, 512)));
		}

		return $response;
	}

	/**
	 * Dynamically make calls to the m_redis database.
	 */
	public function __call($method, $parameters)
	{
	    foreach ( ( array ) $parameters as $key => $val )
        {
            if (is_array( $val ))
            {
                foreach ( $val as $v )
                {
                    $parameters [] = $v;
                }
                unset( $parameters [$key] );
            }
        }
		return $this->run($method, $parameters);
	}

	/**
	 * Dynamically pass static method calls to the m_redis instance.
	 */
	public static function __callStatic($method, $parameters)
	{
	    foreach ( ( array ) $parameters as $key => $val )
        {
            if (is_array( $val ))
            {
                foreach ( $val as $v )
                {
                    $parameters [] = $v;
                }
                unset( $parameters [$key] );
            }
        }
		return static::db()->run($method, $parameters);
	}

	/**
	 * Close the connection to the m_redis database.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->connection)
		{
			fclose($this->connection);
		}
	}

}