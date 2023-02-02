<?php

include 'config.php';

$sources = $onvif->getSources();
$profileToken = $sources[0][0]['profiletoken'];

echo str_repeat('-', 15) . ' Version ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getOnvifVersion() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Media Uri ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getMediaUri() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Ptz Uri ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getPtzUri() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Base Uri ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getBaseUri() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Device Uri ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getDeviceUri() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Sources ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getSources() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Streams ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getStreamUris() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Single stream ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
echo $onvif->getStreamUri( $profileToken ) . PHP_EOL;
echo $onvif->getStreamUri( $profileToken, TRUE );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Snapshots ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
print_r( $onvif->getSnapshotUris() );
echo PHP_EOL;

echo str_repeat('-', 15) . ' Single snapshot ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;
echo $onvif->getSnapshotUri( $profileToken );
echo PHP_EOL;


echo str_repeat('-', 15) . ' Moving test ' . str_repeat('-', 15).PHP_EOL.PHP_EOL;

$onvif->move( $profileToken, 'left', 'down' );
sleep(8);

echo "UP...".PHP_EOL;
$onvif->move( $profileToken, 'default', 'up' );
sleep(2);

echo "DOWN...".PHP_EOL;
$onvif->move( $profileToken, 'default', 'down' );
sleep(2);

echo "RIGHT...".PHP_EOL;
$onvif->move( $profileToken, 'right', 'default' );
sleep(5);

echo "LEFT...".PHP_EOL;
$onvif->move( $profileToken, 'left', 'default' );
sleep(3);

echo "COMBINED...".PHP_EOL;
$onvif->move( $profileToken, 'right', 'up' );
sleep(2);