<?php
/**
 * MpdClient
 * Socket connection to the Music Player Daemon (MPD)
 * 
 * @date 2009-07-01
 * @author Alex Walker (alex.p.walker@gmail.com)
 * @project http://code.google.com/p/php5-mpd-api/
 * @license New BSD
 **/ 
class MpdClient
{
	/**
	 * @var resource The connection to MPD
	 */
	private $_conn;
	/**
	 * @var bool
	 */
	private $_connected;
	/**
	 * @var string The result of the request.
	 */
	private $_response;
	/**
	 * @var string The error if the request failed.
	 */
	private $_error;
	/**
	 * @var bool For debugging
	 */
	private $_verbose;
	/**
	 * @var string To format the debug
	 */
	private $_verbose_eol;
	
	/**
     * Constructor
	 *
     * @param bool Turn on/off debugging
     * @param string The debug EOL character (<br />, PHP_EOL, etc).
     * @return void
     */
	public function __construct($verbose = false, $eol = '')
	{
		$this->_conn = null;
		$this->_connected = false;
		$this->_verbose = $verbose;
		$this->_verbose_eol = $eol;
	}
	
	/**
     * Destructor
	 * Tries to disconnect.
     * @return void
     */
	public function __destruct()
	{
		$this->disconnect();
	}
	
	/**
     * Connect
	 * Tries to connect.
	 * @param string $host An ip4 ip or localhost
	 * @param int $port The port mpd is running or 6600
	 * @param string|null $pwd A valid password or null
     * @return bool
     */
	public function connect($host='localhost', $port=6600, $pwd=null)
	{
		if( $this->_verbose ){
			echo __CLASS__.': opening connection.'.$this->_verbose_eol;
		}
		
		$this->_conn = fsockopen($host, $port, $errno, $errstr, $to = 15);
		
		if( false === $this->_conn )
		{
			$this->_error = __CLASS__.': could not open connection ('.$errno.': '.$errstr.').';
			
			if( $this->_verbose ){
				echo $this->_error.$this->_verbose_eol;
			}
			return $this->_connected = false;
		}
		
		if( false === $this->response() )
		{
			if( $this->_verbose ){
				echo $this->_error.$this->_verbose_eol;
			}
			
			return $this->_connected = false;
		}
		
		if( null !== $pwd )
		{
			$resp = $this->exec('password "'.$pwd.'"'.PHP_EOL);
		
			if( false === $resp )
			{
				if( $this->_verbose ){
					echo $this->_error.$this->_verbose_eol;
				}
				
				throw new Exception($this->_error);
			}
		}
		
		if( $this->_verbose ){
			echo __CLASS__.': connected.'.$this->_verbose_eol;
		}
		
		return $this->_connected = true;
	}
	
	/**
     * Destructor
	 * If connected, then close the connection.
     * @return bool
     */
	public function disconnect()
	{
		if( $this->_connected )
		{			
			fputs($this->_conn, 'close'.PHP_EOL);
			fclose($this->_conn);
			
			if( $this->_verbose ){
				echo __CLASS__.': disconnected.'.$this->_verbose_eol;
			}
			
			$this->_connected = false;
			return true;
		}
		return false;
	}
	
	/**
     * Connected
	 * If connected, then returns true.
     * @return bool
     */
	public function connected()
	{
		return $this->_connected;
	}
	
	/**
     * Disconnected
	 * If disconnected, then returns true.
     * @return bool
     */
	public function disconnected()
	{
		return $this->_connected === false;
	}
	
	/**
     * Exec
	 * Make a request and return the response.
	 * @params string $cmd
     * @return string|bool The string response or true or false.
     */
	public function exec($cmd)
	{
		if( !$this->_connected || !$cmd ){
			return false;
		}
		
		$this->request($cmd);
		
		return $this->response();
	}
	
	/**
     * Request
	 * Send a request to the mpd.
	 * @params string $cmd
     * @return int|bool The bytes sent or false.
     */
	private function request($cmd)
	{
		if( PHP_EOL !== substr($cmd, -1) )
			$cmd .= PHP_EOL;
	
		if( $this->_verbose ){
			echo __CLASS__.': request: '.$cmd.$this->_verbose_eol;
		}
		
		return fputs($this->_conn, $cmd);
	}
	
	/**
     * Response
	 * Retrieve the reply from mpd.  If response indicates success
	 * but there is no content, will return true; otherwise, false.
     * @return string|bool The string response or true/false.
     */
	private function response()
	{		
		$this->_response = '';
		$this->_error    = '';
		
		while( !feof($this->_conn) )
		{
			$buf = fgets($this->_conn);
			
			if( 0 === strncmp($buf, 'OK', 2) )
			{				
				if( $this->_response === '' ){
					$this->_response = true;
				}
				
				if( $this->_verbose ){
					echo __CLASS__.': response: '.((string)$this->_response).$this->_verbose_eol;
				}
				
				return $this->_response;
			} 
			else if( 0 === strncmp($buf, 'ACK', 3) )
			{
				$this->_error = $buf;
				
				if( $this->_verbose ){
					echo __CLASS__.': response: '.$this->_error.$this->_verbose_eol;
				}
				
				return false;
			}
			
			$this->_response .= $buf;
		}
		
		$this->_error = __CLASS__.': no response from server.';
		
		if( $this->_verbose ){
			echo $this->_error.$this->_verbose_eol;
		}
		
		return false;
	}
	
	/**
     * Error
     * @return string The error message, if present.
     */
	public function error()
	{
		return $this->_error.$this->_verbose_eol;
	}
}
