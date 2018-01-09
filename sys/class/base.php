<?php
class base {
    
    static $config;
    static $routes;
    
    public static function get_path($path) {
        $p = array (APPPATH, SYSPATH );
        foreach ( $p as $v ) {
            if (is_file( ($filepath = $v . $path) )) {
                return $filepath;
            }
        }
        return NULL;
    }
    
    /**
     * 加载配置文件
     * 
     * @param string $group 配制文件的路径及数组的键拼成的串
     * @return mixed
     */
    public static function get_config($group,$default=null) {
        if (strpos( $group, '.' ) !== FALSE) {
            list ( $group, $path ) = explode( '.', $group, 2 );
        }
        if (! isset( self::$config [$group] )) {
            if (!$file = self::get_path( "config/{$group}.php" )){
                return $default;
            }            
            self::$config [$group] = self::load( $file );
        }
        if (isset( $path )) {
            $path = explode( '.', $path );
            $v = self::$config [$group];
            for($i = 0; $i < count( $path ); $i ++) {
                if (isset( $v [$path [$i]] )) {
                    $v = $v [$path [$i]];
                }
                else {
                    $v = NULL;
                    break;
                }
            }
            return $v;
        }
        else if (isset(self::$config [$group])) {
            return self::$config [$group];
        }
        else{
            return $default;
        }
    }
    
    public static function get_route($route){
       if (! isset( self::$routes )) {
            if (!$file = self::get_path( "routes.php" )){
                return null;
            }
            self::$routes = self::load( $file );
        }
        
        if (isset(self::$routes)) {
            return self::$routes [$route];
        }
        else{
            return null;
        }
    }
    
    public static function load($file) {
        return include $file;
    }
    
    public static function auto_load($class) {        
        if (($p = strstr( $class, '_', TRUE ))) {
            $inc = array ( 'c' => 'controller/', 'm' => 'model/');
            if (isset( $inc [$p] )) {
                $class = $inc [$p] . $class;
            }
        }
        
        $class = strtolower( $class );
        $path = self::get_path( "class/{$class}.php" );
        
        if (is_file( $path )) {
            require $path;
            return TRUE;
        }
        return FALSE;
    }
    
public static function exception_handler(Exception $e) {
        if(SYSDEBUG){
            $msg = '<pre>';
            $msg .= $e->getMessage()."\r\n";
            $msg .= $e->getTraceAsString();
            $msg .= '</pre>';
        } else {            
            $file = $e->getFile();
            if($file){
                $b = strrpos($file, '/');
                $file = $b>0?substr($file,$b):$file;
                $file = strstr($file, '.',true);
            }
            $msg .= sprintf('Error[ %s ]: %s ~ %s [ %d ]',$e->getCode(), strip_tags($e->getMessage()), $file, $e->getLine());
        }
        
        $response = array ('ret' => - 1, 'msg' =>$msg, 'data' => array (), 'cmd' => "msg.pay" );
        echo json_encode($response);        
        func::log($msg,'error');
    }

    public static function error_handler($code, $error, $file = NULL, $line = NULL) {
        if (error_reporting() & $code) {
            self::exception_handler(new ErrorException($error, $code, 0, $file, $line));
            exit;
        }
        return TRUE;
    }

}