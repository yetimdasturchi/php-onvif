<?php

include 'config.php';

$stdin = fopen( 'php://stdin', 'r' );
stream_set_blocking( $stdin, 0 );
system( 'stty cbreak -echo' );

function translateKeypress( $string ) {
  	
  	switch ($string) {
  		case "\033[A":
      		return "UP";
    	case "\033[B":
      		return "DOWN";
    	case "\033[C":
      		return "RIGHT";
    	case "\033[D":
      		return "LEFT";
    	case "\n":
      		return "ENTER";
    	case " ":
      		return "SPACE";
    	case "\010":
    	case "\177":
      		return "BACKSPACE";
    	case "\t":
      		return "TAB";
    	case "\e":
      		return "ESC";
   	}

  	return $string;
}


$sources = $onvif->getSources();
$profileToken = $sources[0][0]['profiletoken'];

while ( 1 ) {
	$keypress = fgets( $stdin );
  	
  	if ( $keypress ) {
    	$key = translateKeypress( $keypress );

    	if ( in_array( $key, [ 'UP', 'DOWN', 'RIGHT', 'LEFT' ] ) ) {

    		$x = ( $key == 'RIGHT' || $key == 'LEFT' ) ? strtolower( $key ) : 'DEFAULT';
    		$y = ( $key == 'UP' || $key == 'DOWN' ) ? strtolower( $key ) : 'DEFAULT';

    		echo $x, ' - ' , $y . PHP_EOL;

    		$onvif->step( $profileToken, 1, $x, $y );
    	}
  	}
}