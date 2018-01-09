<?php
class func{
    private static $logpath = array();
    
    public static function log($msg, $filename='')
    {
        $logf = self::get_log_path($filename);
        $logf = INCPATH . $logf;
        
        if (is_array($msg) || is_object($msg))
        {
            ob_start();
            var_dump($msg);
            $msg = ob_get_clean();
        }
        $msg.="\n";
        $msg = date('Ymd His') .'|'. $msg;
        file_put_contents($logf, $msg, FILE_APPEND | LOCK_EX);
        chmod($logf, 0766);
        chown($logf, 'nobody');
    }
    
    public static function get_log_path($filename='', $d=false)
    {
        $d = $d ? $d : (int) date('Ymd');
        $fn = $filename . $d;
        ($logf = self::array_get(self::$logpath, $fn)) || (($logf = self::get_upload_filename($filename == 'client' ? 'client' : 'logs', $d, 'log', $fn)) && (self::$logpath[$fn] = $logf));

        return $logf;
    }
    
    public static function get_upload_filename($cate, $id, $file_type="gif", $filename=false)
    {
        $upload_dir[] = LOGPATH;
        $dir_size = 256;

        switch ($cate)
        {       
            case "client":
                $upload_dir[] = $cate;
                $level = 0;
                break;

            default:
                $level = 1;
                $upload_dir[] = $cate;
                break;
        }

        //		根据层数递归产生最终目录
        for ($idx = $level; $idx > 0; $idx--)
            $upload_dir[] = floor($id / pow($dir_size, $idx));
                    
        $final_dir = implode("/", $upload_dir);
        self::mkdirs($final_dir);
                
        if (!$filename)
        {
            return $final_dir . "/" . $id . "." . $file_type;
        }
        else
        {
            return $final_dir . "/" . $filename . "." . $file_type;
        }
    }
    
    public static function array_get($array, $key, $default = null)
    {
    	if (is_null($key)) return $array;
    
    	// To retrieve the array item using dot syntax, we'll iterate through
    	// each segment in the key and look for that value. If it exists, we
    	// will return it, otherwise we will set the depth of the array and
    	// look for the next segment.
    	foreach (explode('.', $key) as $segment)
    	{
    		if ( ! is_array($array) or ! array_key_exists($segment, $array))
    		{
    			return $default;
    		}
    
    		$array = $array[$segment];
    	}
    
    	return $array;
    }
    
    public static function input_get($key = null, $default = null)
	{
		($value = self::array_get($_POST, $key)) || ($value = self::array_get($_GET, $key)) || ($value = self::array_get($_REQUEST, $key, $default));

		return $value;
	}
    
	/**
     * 创建目录
     * 
     * @param string $dir
     */
    public static function mkdirs($dir)
    {
        return is_dir( $dir ) or (self::mkdirs( dirname( $dir ) ) and mkdir( $dir, 0777 ));
    }
    
    public static function http_request($url, $post_data=null)
    {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
        );
        if (stripos($url, 'https') === 0)
        {
            $options[CURLOPT_SSL_VERIFYHOST] = 1;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }

        if ($post_data)
        {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $post_data;
        }

        curl_setopt_array($ch, $options);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }
    
   public function send_gameserver($host, $port, $request)
    {
        try{
            $fp = fsockopen( $host, $port );
        }catch (ErrorException $e){
            $fp = NULL;
            throw $e;
        }
        
        if (! $fp){
            self::log('host:'.$host.'|port:'.$port.'php_network_getaddresses: getaddrinfo failed','error');
            return false;
        }
        
        $nwrite = fputs( $fp, "1 $request\r\n" );
        $len = 1024;
        $result = fread( $fp, $len );
        $binary = substr($result,1,3);
        $header = unpack("v",$binary);
        
        $result_len = strlen($result);
        
        if (isset($header[1])){
            do{                
                $next_len = $header[1] - $result_len;                
                if ($next_len > 0){
                    $next_len = $next_len > $len ? $len : $next_len;
                    $result .= fread( $fp, $next_len);
                    $result_len += $next_len;
                }                
            }
            while ( $header[1] - $result_len > 0 );
        }
        
        if (strlen( $result ) > 5)
        {
            $result = substr( $result, 5 );
        }
        
        fclose( $fp );
        
        return $result;
    } 
    
    public static function array_ksort_recursion(&$a,$sort_flags=NULL)
    {
    	if(is_array($a)) 
    	{
    		ksort($a,$sort_flags);
    		
    	    foreach($a as &$val){
        		if(is_array($val) && !empty($a))  
        		{
        			self::array_ksort_recursion($val,$sort_flags);
        		}
    	    }
    	}    	
    }
    
    // $str = "+ +：:/.-——_~`@!#$%^&*()[]{}=,。?？！;;";
    public static function myUrlEncode($string) {
        return str_replace(array('+','_','.','-'),array('%20','%5F','%2E','%2D'),urlencode($string));
    }
}