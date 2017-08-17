<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class MyPhpDirb
{
	CONST MAGIC_WORD = 'FUZZ';
	CONST DEFAULT_MAX_CHILD = 5;
	
	private $url = null;

	private $wordlist = null;
	private $t_words = [];
	private $n_words = 0;

	private $follow_redirection = false;

	/**
	 * output stuff
	 */
	private $display_full_url = false;
	private $display_http_code = null;
	private $display_ignore_http_code = null;
	
	private $n_user_agent = 0;
	private $t_user_agent = [
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0',
		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
		'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
		'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
		'Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
	];

	/**
	 * daemon stuff
	 *
	 * @var mixed
	 */
	private $n_child = 0;
	private $max_child = self::DEFAULT_MAX_CHILD;
	private $loop_sleep = 10;
	private $t_process = [];
	private $t_signal_queue = [];


	public function __construct() {
		$this->destination = dirname(__FILE__);
	}


	public function getUrl() {
		return $this->url;
	}
	public function setUrl( $v ) {
		$v = trim( $v );
		if( !strstr($v,self::MAGIC_WORD) ) {
			Utils::help( '"'.self::MAGIC_WORD.'" term not found in url' );
		}
		$this->url = $v;
		return true;
	}

	public function getMaxChild() {
		return $this->threads;
	}
	public function setMaxChild( $v ) {
		$this->max_child = abs( (int)$v );
		return true;
	}


	public function getWordlist() {
		return $this->wordlist;
	}
	public function setWordlist( $v ) {
		if( !is_file($v) ) {
			Utils::help( 'Wordlist file not found' );
		}
		$this->wordlist = $v;
		return true;
	}

	
	public function getFollowRedirection() {
		return $this->follow_redirection;
	}
	public function setFollowRedirection() {
		$this->follow_redirection = true;
		return true;
	}

	
	public function getDisplayFullUrl() {
		return $this->display_full_url;
	}
	public function setDisplayFullUrl() {
		$this->display_full_url = true;
		return true;
	}

	
	public function getDisplayHttpCode() {
		return $this->display_http_code;
	}
	public function setDisplayHttpCode( $v ) {
		$this->display_http_code = explode( ',', trim($v) );
		return true;
	}

	
	public function getDisplayIgnoreHttpCode() {
		return $this->display_ignore_http_code;
	}
	public function setDisplayIgnoreHttpCode( $v ) {
		$this->display_ignore_http_code = explode( ',', trim($v) );
		return true;
	}

	
	private function init()
	{
		posix_setsid();
		declare( ticks=1 );
		pcntl_signal( SIGCHLD, array($this,'signal_handler') );

		$this->n_user_agent = count($this->t_user_agent) - 1;
		$this->t_words = file( $this->wordlist, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES );
		$this->n_words = count( $this->t_words );

		echo "\n";
		echo str_pad( '', 80, '-' )."\n";
		echo "Url:\t\t".$this->url."\n";
		echo "Wordlist:\t".$this->wordlist."\n";
		echo "Words:\t\t".$this->n_words."\n";
		if( $this->display_http_code ) {
			echo "Display:\t".implode(',',$this->display_http_code)."\n";
		} else {
			echo "Display:\tall\n";
		}
		if( $this->display_ignore_http_code ) {
			echo "Ignore:\t\t".implode(',',$this->display_ignore_http_code)."\n";
		}
		echo "Threads:\t".$this->max_child."\n";
		echo str_pad( '', 80, '-' )."\n";
		echo "\n";
	}

	
	public function run()
	{
		$this->init();

		for( $w_index=0 ; $w_index<$this->n_words ; )
		{
			if( $this->n_child < $this->max_child )
			{
				$pid = pcntl_fork();
				
				if( $pid == -1 ) {
					// fork error
				} elseif( $pid ) {
					// father
					$this->n_child++;
					$w_index++;
					$this->t_process[$pid] = uniqid();
			        if( isset($this->t_signal_queue[$pid]) ){
			        	$this->signal_handler( SIGCHLD, $pid, $this->t_signal_queue[$pid] );
			        	unset( $this->t_signal_queue[$pid] );
			        }
				} else {
					// child process
					$this->request( $w_index );
					exit( 0 );
				}
			}

			usleep( $this->loop_sleep );
		}
		
		while( $this->n_child ) {
			// surely leave the loop please :)
			sleep( 1 );
		}
	}
	
	
	private function request( $w_index )
	{
		$word = $this->t_words[ $w_index ];
		$url = str_replace( self::MAGIC_WORD, $word, $this->url );
		//var_dump( $url );
		
		$c = curl_init();
		curl_setopt( $c, CURLOPT_URL, $url );
		curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 3 );
		if( stristr($this->url,'https://') ) {
			curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $c, CURLOPT_USERAGENT, $this->t_user_agent[rand(0,$this->n_user_agent)] );
		//curl_setopt( $c, CURLOPT_NOBODY, true );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $c, CURLOPT_FOLLOWLOCATION, $this->follow_redirection );
		$datas = curl_exec( $c );
		$t_infos = curl_getinfo( $c );
		//var_dump( $datas );
		//var_dump($t_infos);
		
		$this->result( $w_index, $t_infos );
	}

	
	private function result( $w_index, $t_infos )
	{
		if( !$this->display_http_code || in_array($t_infos['http_code'],$this->display_http_code) )
		{
			if( !$this->display_ignore_http_code || !in_array($t_infos['http_code'],$this->display_ignore_http_code) )
			{
				$word = $this->t_words[ $w_index ];
				//$url = str_replace( self::MAGIC_WORD, $word, $this->url );
				//var_dump( $url );
				
				if( $this->display_full_url ) {
					$url = str_replace( self::MAGIC_WORD, $word, $this->url );
					$str = $url;
				} else {
					$str = $word;
				}
				
				$l = strlen( $str );
				$str = str_pad($str,50,' ')."  C=".$t_infos['http_code'];
				$str .= str_pad('',10,' ')."L=".$t_infos['size_download'];
				
				echo $str."\n";
			}
		}
	}
	
	
	// http://stackoverflow.com/questions/16238510/pcntl-fork-results-in-defunct-parent-process
	// Thousand Thanks!
	public function signal_handler( $signal, $pid=null, $status=null )
	{
		// If no pid is provided, Let's wait to figure out which child process ended
		if( !$pid ){
			$pid = pcntl_waitpid( -1, $status, WNOHANG );
		}
		
		// Get all exited children
		while( $pid > 0 )
		{
			if( $pid && isset($this->t_process[$pid]) ) {
				// I don't care about exit status right now.
				//  $exitCode = pcntl_wexitstatus($status);
				//  if($exitCode != 0){
				//      echo "$pid exited with status ".$exitCode."\n";
				//  }
				// Process is finished, so remove it from the list.
				$this->n_child--;
				unset( $this->t_process[$pid] );
			}
			elseif( $pid ) {
				// Job finished before the parent process could record it as launched.
				// Store it to handle when the parent process is ready
				$this->t_signal_queue[$pid] = $status;
			}
			
			$pid = pcntl_waitpid( -1, $status, WNOHANG );
		}
		
		return true;
	}
}
