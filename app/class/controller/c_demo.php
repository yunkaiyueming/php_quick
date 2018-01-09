<?php

class c_demo {
    
    /**
     * 预返回值的格式
     * @var array
     */
    public $response = array ('ret' => - 1, 'msg' => 'error', 'data' => array ());
    
    public function __construct(){}
    
    public function before(){}
	
	public function after() {}
    
	//http://127.0.0.1:93/php_quick/index.php?t=get&ctype=1
    public function action_getlist() {
		//func使用
        $ctype = func::input_get("ctype");
		
		//model使用
        $m_demo = new m_demo();
		$demo_info = $m_demo->getall();
//      $demos = $m_demo->create($ctype,$rtype,$num,$st,$et,$rewards,$title,$content,$giftnum);
//		$demo_info = $m_demo->getuseuser($uid,$cdkey);
//		$m_demo->getlist($page,$s);
		
		//redis使用
		$mredis = new mredis('127.0.0.1', '6379'); //或 $mredis = mredis::db('cache', array('host'=>'127.0.0.1', 'port'=>'6379'));
		$mredis->run('set', array('bbk',"bbv2"));
		$ret = $mredis->run('get', array('bbk'));
		
		//返回
		$this->response['data']['bbk'] = $ret;
        $this->response['data']['demos'] = $demo_info;
        $this->response['ret'] = 0;
        $this->response['msg'] = 'Success';
                
        return $this->rtn();
    }
 
    protected function rtn() {
        if(is_array( $this->response )) {
            return json_encode( $this->response );
        }
        return $this->response;
    }
}
