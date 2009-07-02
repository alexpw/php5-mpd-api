<?php
/**
 * MpdCmd
 * Formats and assemblies mpd request commands.
 * 
 * @date 2009-07-01
 * @author Alex Walker (alex.p.walker@gmail.com)
 * @project http://code.google.com/p/php5-mpd-api/
 * @license New BSD
 **/ 
class MpdCmd
{
	/**
	 * @var array
	 */
	private $_queue;
	
	/**
     * Constructor
     * Generally used indirectly through begin.
	 * Or not at all if using ::make()
	 *
     * @return void
     */
	public function __construct()
	{
		$this->_queue = array();
		
		$this->add('command_list_begin');
	}
	
	/**
     * Begin
     * Instantiates an instance of MpdCmd
	 * Symbolizes the beginning of the command queue.
	 *
     * @return MpdClient
     */
	public static function begin()
	{
		$cn = __CLASS__;
		return new $cn;
	}
	
	/**
     * End
     * Assemblies the formatted command queue.
	 *
     * @return string
     */
	public function end()
	{
		if( count($this->_queue) > 2 )
		{
			$this->add('command_list_end');
		
			return implode('', $this->_queue);
		}
		
		return array_pop($this->_queue);
	}
	
	/**
	 * Make
	 * Format a command and any arguments.
	 *
	 * Params are either a series a strings, e.g.:
	 *   case 1: cmd [, arg1, arg2, ...]
	 * or wrapped in an array, e.g.:
	 *   case 2: array(cmd [, arg1, arg2, ...])
	 *
	 * @params string|array 
	 * @return string
	 */

	public static function make()
	{
		$args = func_get_args();
		
		if( func_num_args() === 1 && is_array($args[0]) )
		{
			$args = $args[0];
		}
		
		$cmd = array_shift($args);
		
		if( !empty($args) )
			$cmd .= ' "'.implode('" "',$args).'"';
		
		return $cmd . PHP_EOL;
	}

	/**
	 * Prepend
	 * Add a command to the beginning of the queue.
	 * @param variable length (see make)
	 * @return MpdCmd $this
	 */
	public function prepend()
	{		
		$cmd = self::make( func_get_args() );
		
		array_unshift($this->_queue, $cmd);
		
		return $this;
	}
	
	/**
	 * Append
	 * Add a command to the end of the queue.
	 * @param variable length (see make)
	 * @return MpdCmd $this
	 */
	public function append()
	{
		$this->_queue[] = self::make( func_get_args() );
		return $this;
	}
	
	/**
	 * Add
	 * Add a command to the end of the queue.
	 * @param variable length (see make)
	 * @return MpdCmd $this
	 */
	public function add()
	{		
		$this->_queue[] = self::make( func_get_args() );
		return $this;
	}
	
	/**
	 * Remove
	 * Remove a command by value.
	 * @param string $cmd
	 * @return MpdCmd $this
	 */
	public function remove($cmd)
	{
		foreach($this->_queue as $k => $v)
		{
			if( 0 === strncmp($v, $cmd, strlen($cmd)) )
			{
				unset($this->_queue[$k]);
				break;
			}
		}
		return $this;
	}
	
	/**
	 * Overloaded toString
	 * The formatted command.
	 * @return string 
	 */
	public function __toString()
	{
		return $this->end();
	}
}
