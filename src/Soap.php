<?php
namespace booosta\soap;

use \booosta\Framework as b;
b::init_module('soap');

class SOAP extends \booosta\base\Module
{ 
  use moduletrait_soap;

  protected $client;
  protected $wsdl;
  protected $cache_wsdl = true;
  protected $error;
  protected $valid;
  protected $disabled = false;

  public function __construct($wsdl = null, $cache_wsdl = null)
  {
    parent::__construct();

    if($this->disabled) return;

    $options = [];
    if($wsdl !== null) $this->wsdl = $wsdl;
    if($cache_wsdl !== null) $this->cache_wsdl = $cache_wsdl;
    if(!$this->cache_wsdl) $options['cache_wsdl'] = WSDL_CACHE_NONE;
    $this->valid = true;

    try { $this->client = new \SoapClient($this->wsdl, $options); }
    catch(\Exception $e)
    {
      $this->error = 'ERROR constructing SOAP client: ' . $e->getMessage();
      $this->valid = false;
    }
  }

  public function __call($func, $params)
  {
    if($this->disabled) return true;
    if(!$this->valid) return $this->error;

    $this->before_call($func, $params);

    try { $result = call_user_func_array([$this->client, $func], $params); }
    catch(\Exception $e)
    {
      $this->error = $e->getMessage();
      return $this->error;
    }

    if(is_object($result)):
      $resultstr = serialize($result);
      if(strstr($resultstr, 'Error')) $this->error .= $resultstr;;
    elseif(strstr($result, 'Error')):
      $this->error .= $result;
    endif;

    // Hook after_call
    $this->after_call($result);

    return $result;
  }

  protected function after_call(&$result) {}
  protected function before_call($func, $params) {}
  public function get_error() { return $this->error; }
}
