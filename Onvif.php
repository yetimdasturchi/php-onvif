<?php

class Onvif {
    
    private $host;
    private $username;
    private $password;
    private $media_uri;
    private $device_uri;
    private $ptz_uri;
    private $base_uri;
    private $deltatime = 0;
    private $onvifversion;
    private $sources;

    function __construct( $host, $config = [] ) {
        $this->host = $host;

        if ( !empty( $config ) ) {
            foreach ( (array)$config as $k => $v) {
              $this->$k = $v;
            }
        }

        if ( is_null( $this->media_uri ) ) {
            $this->media_uri = 'http://' . $this->host . '/onvif/device_service';   
        }

        $this->_get_datetime();
        $this->_get_capabilities();
    }

    public function getSources(){
        return $this->sources;
    }

    public function getOnvifVersion(){
        return $this->onvifversion;
    }

    public function getMediaUri(){
        return $this->media_uri;
    }

    public function getPtzUri(){
        return $this->ptz_uri;
    }

    public function getBaseUri(){
        return $this->base_uri;
    }

    public function getDeviceUri(){
        return $this->device_uri;
    }

    public function getStreamUri( $profileToken, $auth = FALSE, $stream="RTP-Unicast", $protocol="RTSP" ){
        return $this->_getStreamUri( $profileToken, $auth, $stream, $protocol);
    }

    public function getStreamUris( $auth = FALSE ){
        $uris = [];

        foreach( $this->sources as $source ){
            $uris[] = $this->getStreamUri( $source[0]['profiletoken'], $auth );
        }

        return $uris;
    }

    public function getSnapshotUri( $profileToken ){
        return $this->_getSnapshotUri( $profileToken );
    }

    public function getSnapshotUris(){
        $uris = [];

        foreach( $this->sources as $source ){
            $uris[] = $this->_getSnapshotUri( $source[0]['profiletoken'] );
        }

        return $uris;
    }

    public function move( $profileToken, $x = 'default', $y = 'default', $zoom = FALSE ){
        $this->_move( $profileToken, $x, $y, $zoom );
    }

    public function step( $profileToken, $step, $x = 'default', $y = 'default' ){
        $this->_move( $profileToken, $x, $y, FALSE );
        usleep($step * 100000);
        $this->stop( $profileToken, TRUE );
    }

    public function stop( $profileToken, $pt = FALSE, $zoom = FALSE ){
        $this->_stop( $profileToken, $pt, $zoom );
    }

    private function _get_datetime() {
        $res = $this->_request( $this->media_uri, $this->_template( 'datetime' ) );
        
        if ( !empty( $res['Envelope']['Body']['GetSystemDateAndTimeResponse']['SystemDateAndTime']['UTCDateTime'] ) ) {
            $time = $res['Envelope']['Body']['GetSystemDateAndTimeResponse']['SystemDateAndTime']['UTCDateTime'];
            
            $timestamp = mktime(
                $time['Time']['Hour'],
                $time['Time']['Minute'],
                $time['Time']['Second'],
                $time['Date']['Month'],
                $time['Date']['Day'],
                $time['Date']['Year']
            );

            $this->deltatime = ( time() - $timestamp - 5 );
        
        }else{
            throw new Exception( "Connection error while getting the system time!" );
        }
    }

    private function _get_capabilities() {
        $res = $this->_request( $this->media_uri, $this->_template( 'capabilities', TRUE ) );
        
        if ( !empty( $res['Envelope']['Body']['GetCapabilitiesResponse']['Capabilities'] ) ) {
            $capabilities = $res['Envelope']['Body']['GetCapabilitiesResponse']['Capabilities'];
            
            $this->media_uri = $capabilities['Media']['XAddr'];
            $this->device_uri = $capabilities['Device']['XAddr'];
            $this->ptz_uri = $capabilities['Events']['XAddr'];

            preg_match( "/^http(.*)onvif\//", $this->media_uri, $matches );

            if ( !empty( $matches[0] ) ) {
                $this->base_uri = $matches[0];
            }


            $this->onvifversion = [
                'major' => $capabilities['Device']['System']['SupportedVersions']['Major'],
                'minor' => $capabilities['Device']['System']['SupportedVersions']['Minor']
            ];

            $videosources = $this->_get_videoSources();
            $profiles = $this->_get_profiles();

            $this->sources = $this->_get_sources( $videosources, $profiles );
        }else{
            throw new Exception( "Connection error while getting the system capabilities!" );
        }
    }

    private function _get_videoSources(){
        $res = $this->_request( $this->media_uri, $this->_template( 'videosources', TRUE ) );
        
        if ( !empty( $res['Envelope']['Body']['GetVideoSourcesResponse']['VideoSources'] ) ) {
            return $res['Envelope']['Body']['GetVideoSourcesResponse']['VideoSources'];
        }

        return [];
    }

    private function _get_profiles(){
        $res = $this->_request( $this->media_uri, $this->_template( 'profiles', TRUE ) );
        
        if ( !empty( $res['Envelope']['Body']['GetProfilesResponse']['Profiles'] ) ) {
            return $res['Envelope']['Body']['GetProfilesResponse']['Profiles'];
        }

        return [];
    }

    private function _get_sources( $videosources, $profiles ) {
        $sources = [];
        
        if ( !empty( $videosources['@attributes'] ) ) {
            $sources[0]['sourcetoken'] = $videosources['@attributes']['token'];
            $this->_getProfileData( $sources, 0, $profiles );
        } else {
            for ( $i=0; $i < count( $videosources ); $i++) {
                if ( strtolower( $videosources[$i]['@attributes']['SignalActive']) == 'true' ) {
                    $sources[$i]['sourcetoken'] = $videosources[$i]['@attributes']['token'];
                    $this->_getProfileData( $sources, $i, $profiles );
                }
            }
        }
        
        return $sources;
    }

    private function _getProfileData( &$sources, $i, $profiles ){
        $inprofile = 0;
        for ($j=0; $j < count( $profiles ); $j++) {
            if ($profiles[$j]['VideoSourceConfiguration']['SourceToken'] == $sources[$i]['sourcetoken']) {
                $sources[$i][$inprofile]['profilename'] = $profiles[$j]['Name'];
                $sources[$i][$inprofile]['profiletoken'] = $profiles[$j]['@attributes']['token'];
                
                if ( !empty( $profiles[$j]['VideoEncoderConfiguration'] ) ) {
                    $sources[$i][$inprofile]['encodername'] = $profiles[$j]['VideoEncoderConfiguration']['Name'];
                    $sources[$i][$inprofile]['encoding'] = $profiles[$j]['VideoEncoderConfiguration']['Encoding'];
                    $sources[$i][$inprofile]['width'] = $profiles[$j]['VideoEncoderConfiguration']['Resolution']['Width'];
                    $sources[$i][$inprofile]['height'] = $profiles[$j]['VideoEncoderConfiguration']['Resolution']['Height'];
                    $sources[$i][$inprofile]['fps'] = $profiles[$j]['VideoEncoderConfiguration']['RateControl']['FrameRateLimit'];
                    $sources[$i][$inprofile]['bitrate'] = $profiles[$j]['VideoEncoderConfiguration']['RateControl']['BitrateLimit'];
                }

                if ( !empty( $profiles[$j]['PTZConfiguration'] ) ) {
                    $sources[$i][$inprofile]['ptz']['name'] = $profiles[$j]['PTZConfiguration']['Name'];
                    $sources[$i][$inprofile]['ptz']['nodetoken'] = $profiles[$j]['PTZConfiguration']['NodeToken'];
                }

                $inprofile++;
            }
        }
    }

    private function _getStreamUri( $profileToken, $auth, $stream, $protocol ){
        $post_string = $this->_template( 'streamuri', TRUE );
        
        $post_string = str_replace([
            "%%PROFILETOKEN%%",
            "%%STREAM%%",
            "%%PROTOCOL%%"
        ],[
            $profileToken,
            $stream,
            $protocol
        ], $post_string );

        $res = $this->_request( $this->media_uri, $post_string );
        
        if ( !empty( $res['Envelope']['Body']['GetStreamUriResponse']['MediaUri']['Uri'] ) ) {
            $uri = $res['Envelope']['Body']['GetStreamUriResponse']['MediaUri']['Uri'];
            
            if ( $auth ) {
                $uri_data = parse_url( $uri );    
                $uri  = $uri_data['scheme'] . '://' . $this->username . ':' . $this->password . '@' . $uri_data['host'] . ( !empty( $uri_data['port'] ) ? ':' . $uri_data['port'] : '' ) . $uri_data['path'];
            }

            return $uri;
        }

        return NULL;
    }

    private function _getSnapshotUri( $profileToken ){
        $post_string = $this->_template( 'snapshot', TRUE );
        
        $post_string = str_replace([
            "%%PROFILETOKEN%%"
        ],[
            $profileToken
        ], $post_string );

        $res = $this->_request( $this->media_uri, $post_string );
        if ( !empty( $res['Envelope']['Body']['GetSnapshotUriResponse']['MediaUri']['Uri'] ) ) {
            return $res['Envelope']['Body']['GetSnapshotUriResponse']['MediaUri']['Uri'];
        }

        return NULL;
    }

    private function _move( $profileToken, $x, $y, $zoom ){
        $post_string = $this->_template( 'move', TRUE );
        
        $x = match ($x) {
            'right' => '1',
            'left' => '-1',
            default => '0',
        };

        $y = match ($y) {
            'down' => '-1',
            'up' => '1',
            default => '0',
        };

        $post_string = str_replace([
            "%%PROFILETOKEN%%",
            "%%VELOCITYPANTILTX%%",
            "%%VELOCITYPANTILTY%%"
        ],[
            $profileToken,
            $x,
            $y
        ], $post_string );

        $this->_request( $this->media_uri, $post_string );
    }

    private function _stop( $profileToken, $pt, $zoom ) {
        $post_string = $this->_template( 'stop', TRUE );
        
        $post_string = str_replace([
            "%%PROFILETOKEN%%",
            "%%PANTILT%%",
            "%%ZOOM%%"
        ],[
            $profileToken,
            ( $pt ? 'true' : 'false' ),
            ( $zoom ? 'true' : 'false' )
        ], $post_string );

        $this->_request( $this->media_uri, $post_string );
    }

    private function _template( $name, $meta = FALSE ){
        $content = @file_get_contents( __DIR__ . '/templates/'. $name .'.xmlt' );

        if ( $meta ) {
            
            $timestamp = date('Y-m-d\TH:i:s.000\Z', ( time() - $this->deltatime ) );
            $nonce = mt_rand();

            $passdigest = base64_encode(
                pack( 'H*', sha1(
                    pack( 'H*', $nonce ) .
                    pack( 'a*', $timestamp ) .
                    pack( 'a*', $this->password )
                ))
            );
            
            $content = str_replace( [
                "%%USERNAME%%",
                "%%PASSWORD%%",
                "%%NONCE%%",
                "%%CREATED%%"
            ],[
                $this->username,
                $passdigest,
                base64_encode( pack( 'H*', $nonce ) ),
                $timestamp
            ], $content );
        }

        return $content;
    }

    public function isXMLContentValid( $content, $version = '1.0', $encoding = 'utf-8' ){
        if ( trim( $content ) == '' ) return FALSE;

        libxml_use_internal_errors( TRUE );

        $doc = new DOMDocument( $version, $encoding );
        $doc->loadXML( $content );

        $errors = libxml_get_errors();
        libxml_clear_errors();

        return empty( $errors );
    }

    private function _xml2array( $response ) {
        $sxe = new SimpleXMLElement( $response );
        $dom_sxe = dom_import_simplexml( $sxe );
        $dom = new DOMDocument( '1.0' );
        $dom_sxe = $dom->importNode( $dom_sxe, TRUE );
        $dom_sxe = $dom->appendChild( $dom_sxe );
        $element = $dom->childNodes->item( 0 );
        
        foreach ( $sxe->getDocNamespaces() as $name => $uri ) {
            $element->removeAttributeNS($uri, $name);
        }
        
        $xmldata = $dom->saveXML();
        
        $xmldata = substr( $xmldata, strpos( $xmldata, "<Envelope>" ) );
        $xmldata = substr( $xmldata, 0, strpos( $xmldata,"</Envelope>" ) + strlen( "</Envelope>" ) );
        
        $xml = simplexml_load_string( $xmldata );
        $data = json_decode(json_encode((array)$xml),1);
        $data = array( $xml->getName() => $data );

        return $data;
    }

    private function _request( $url, $post_string ){
        $ch = curl_init();
        
        curl_setopt( $ch, CURLOPT_URL, $url );
        
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_string);
        
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen( $post_string )
        ]);
        
        //curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
        
        if ( ( $result = curl_exec( $ch ) ) === FALSE ) {
            throw new Exception( curl_error( $ch ) );
        }else{
            $this->isXMLContentValid( $result ) OR throw new Exception( "Response not valid!" );
        }

        return $this->_xml2array( $result );
    }
}