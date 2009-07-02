<?php
/**
 * MpdProperty
 *
 * A container for the properties returned by
 * the mpd commands: "status" and "stats".
 *
 * It defaults to retrieving both, but can easily be
 * used to retrieve only one through the refresh method.
 *
 * @date 2009-07-01
 * @author Alex Walker (alex.p.walker@gmail.com)
 * @project http://code.google.com/p/php5-mpd-api/
 * @license New BSD
 **/ 
class MpdProperty
{
	/**
	 * COMMAND: status
	 *
	 * volume: 0-100
	 * repeat: 0 or 1
	 * random: 0 or 1
     * single: 0 or 1
     * consume: 0 or 1 [NOTE: documented, but not returned]
     * playlist: 31-bit unsigned integer, the playlist version number
     * playlistlength: integer, the length of the playlist
     * state: play, stop, or pause
	 * xfade: crossfade in seconds
	 * -- IF SONG LOADED
     * song: playlist song number of the current song stopped on or playing
     * songid: playlist songid of the current song stopped on or playing
     * time: total time elapsed (of current playing/paused song)
     * bitrate: instantaneous bitrate in kbps
	 * audio: sampleRate:bits:channels
     * updatings_db: job id
	 * --- IF ERROR
     * error: returns message here 
	 */
	 
	/**
	 * COMMAND: stats
	 *
	 * artists: number of artists
     * songs: number of albums
     * uptime: daemon uptime in seconds
     * db_playtime: sum of all song times in the db
     * db_update: last db update in UNIX time
     * playtime: time length of music played 
	 */
	
	/**
	 * Settable commands (read and write)
	 * @var array
	 */
	private static $_set_cmds = array(
		'setvol' => 'setvol',
		'volume' => 'setvol',
		'repeat' => 'repeat',
		'random' => 'random',
		'single' => 'single',
		'xfade'  => 'crossfade',
		//'consume' => 'consume' // documented, but invalid in 
	);
	
	/**
	 * @var string 
	 */
	private static $_cmd;

	/**
	 * @var array
	 */
	private $_prop;
	
	/**
	 * @var MpdClient instance
	 */
	private $_mpd;

	/**
     * Construct
	 * @param MpdClient
     */
	public function __construct(MpdClient $mpd)
	{
		$this->_mpd = $mpd;
		$this->_prop = array();
		
		if( self::$_cmd === null )
		{
			self::$_cmd = MpdCmd::begin()
				->add('stats')
				->add('status')
				->end();
		}
	}

	/**
     * Init
	 * Creates an MpdProperty instance.
	 * @param MpdClient
     * @return MpdProperty instance
     */
	public static function init(MpdClient $mpd)
	{
		$cn = __CLASS__;
		
		return new $cn($mpd);
	}
	
	/**
     * Refresh
	 * Retrieves and sets mpd properties.
     * @return MpdProperty $this
     */
	public function refresh($cmd = null)
	{
		if( null === $cmd )
			$cmd = self::$_cmd;
		else
			$cmd = MpdCmd::make($cmd);
			
		$r = $this->_mpd->exec($cmd);
		
		if( !$r ) return false;
		
		$rlines = explode(PHP_EOL,$r);
		
		foreach($rlines as $line)
		{
			if( false === strpos($line, ': ') ) continue;
			
			list($k, $v) = explode(': ',$line);
			
			$this->_prop[$k] = $v;
		}
		
		return $this;
	}
	
	/**
     * Clear	
     * @return MpdProperty $this
     */
	public function clear()
	{
		$this->_prop = array();
		
		return $this;
	}
	
	/**
     * Has
	 * @param string A property name
     * @return bool
     */
	public function has($k)
	{
		return isset($this->_prop[$k]);
	}
	
	/**
     * Get
	 * @param string A property name
	 * @param mixed|false A default if not found.
     * @return array
     */
	public function get($k, $default = false)
	{		
		if( isset($this->_prop[$k]) ){
			return $this->_prop[$k];
		}
		return $default;
	}
	
	/**
     * Get All
     * @return array
     */
	public function getAll()
	{
		return $this->_prop;
	}
	
	 /**
     * Set
     * Updates mpd if possible or throws an exception.
     * @param  string $name 
     * @param  mixed $value 
     * @return void
     */
    public function set($k, $v)
    {
		if( isset(self::$_set_cmds[$k]) )
		{			
			$cmd = MpdCmd::make(self::$_set_cmds[$k], $v);
			
			if( true === $this->_mpd->exec($cmd) )
			{
				$this->_prop[$k] = $v;
				return true;
			}
			return false;
		}
		
		if( isset($this->_prop[$k]) )
		{
			throw new Exception(__CLASS__.': "'.$k.'" is read only.');
		}
		
		throw new Exception(__CLASS__.': "'.$k.'" is an invalid property.');
    }
}
