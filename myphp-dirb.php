#!/usr/bin/php
<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

function __autoload( $c ) {
	include( dirname(__FILE__).'/'.$c.'.php' );
}


set_time_limit( 0 );


// parse command line
{
	$myphpdirb = new MyPhpDirb();

	$argc = $_SERVER['argc'] - 1;

	for( $i=1; $i<=$argc; $i++ ) {
		switch( $_SERVER['argv'][$i] ) {
			case '-h':
			case '--help':
				Utils::help();
				break;

			case '-c':
				$myphpdirb->setDisplayColors();
				break;

			case '-d':
				$myphpdirb->setDisplayHttpCode( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-f':
				$myphpdirb->setDisplayFullUrl();
				break;

			case '-i':
				$myphpdirb->setDisplayIgnoreHttpCode( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-r':
				$myphpdirb->setFollowRedirection();
				$i++;
				break;

			case '-u':
				$myphpdirb->setUrl( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-t':
				$myphpdirb->setMaxChild( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-v':
				$myphpdirb->setVerbosity( (int)$_SERVER['argv'][$i+1] );
				$i++;
				break;

			case '-w':
				$myphpdirb->setWordlist( $_SERVER['argv'][$i+1] );
				$i++;
				break;

			default:
				Utils::help( 'Unknown option: '.$_SERVER['argv'][$i] );
		}
	}

	if( !$myphpdirb->getUrl() ) {
		Utils::help( 'Target not found' );
	}
	if( !$myphpdirb->getWordlist() ) {
		Utils::help( 'Wordlist not found' );
	}
}
// ---


// main loop
{
	$cnt = $myphpdirb->run();
	echo "\nFinished.\n\n";
}
// ---


exit();
