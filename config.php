<?php

include 'Onvif.php';

$onvif = new Onvif('192.168.200.1:80', [
    'username' => 'admin',
    'password' => '1234',
]);