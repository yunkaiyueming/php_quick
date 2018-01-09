<?php
class run
{
    public $controller = NULL;
    public $action = NULL;
    private static $initial;
    
    public function __construct()
    {
        $this->execute();
    }
    
    private function init()
    {        
        $a = isset( $_REQUEST ['t'] ) ? $_REQUEST ['t'] : null;  
        
        if (!$a) {
            $url = url::detect_uri();
            $path_info = explode('/', $url);
            $a = end($path_info);
            unset($path_info,$url);
        }
        
        if (! $a || ! ($app = base::get_route( $a )))
        {
            throw new Exception( 'The requested URL ' . $_SERVER ['REQUEST_URI'] . ' was not found on this server.', 404 );
        }
        
        $this->controller = $app [0];
        $this->action = $app [1];
    }
    
    public static function i()
    {
        // If this is the initial request
        if (! self::$initial)
        {
            self::$initial = new run();
        }
        return self::$initial;
    }
    
    private function execute()
    {
        try
        {
            $this->init();
            
            $controller = 'c_' . $this->controller;
            $action = 'action_' . $this->action;
            
            if (! class_exists( $controller ))
            {
                throw new Exception( 'The requested URL ' . $_SERVER ['REQUEST_URI'] . ' was not found on this server.', 404 );
            }
            
            $class = new ReflectionClass( $controller );            
            if ($class->isAbstract())
            {
                throw new Exception( 'Cannot create instances of abstract ' . $controller );
            }
            
            $controller = $class->newInstance();            
            
            $controller->before();
            
            if (! $class->hasMethod( $action ))
            {
                throw new Exception( 'The requested URL ' . $_SERVER ['REQUEST_URI'] . ' was not found on this server.', 404 );
            }
            
            $method = $class->getMethod( $action );
            
            $ret = $method->invoke( $controller );
            
            $controller->after();
            
            echo $ret;
        
        }
        catch( Exception $e )
        {
            throw $e;
        }
    }
}