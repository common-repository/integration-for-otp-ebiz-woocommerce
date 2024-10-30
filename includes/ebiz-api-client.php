<?php
/**
 * @service WC_eBiz_Motor_soapv2SoapClient
 */
class WC_eBiz_Motor_soapv2SoapClient {
	/**
	 * The WSDL URI
	 *
	 * @var string
	 */
	public static $_WsdlUri='https://manage.otpebiz.hu:4465/motor_soapv2/?WSDL';
	public static $_WsdlUriDev='https://teszt.otpebiz.hu:4447/motor_soapv2/?WSDL';

	/**
	 * The PHP SoapClient object
	 *
	 * @var object
	 */
	public static $_Server=null;

	/**
	 * Send a SOAP request to the server
	 *
	 * @param string $method The method name
	 * @param array $param The parameters
	 * @return mixed The server response
	 */
	public static function _Call($method,$param,$auth){
		if($auth['dev_environment'] == 'yes') {
			$url = self::$_WsdlUriDev;
			$context = stream_context_create(array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				)
			));
		} else {
			$url = self::$_WsdlUri;
			$context = stream_context_create(array());
		}

		if(is_null(self::$_Server)) {
			self::$_Server=new SoapClient($url, array('stream_context' => $context, 'login' => $auth['username'], 'password' => $auth['password']));
		}
		return self::$_Server->__soapCall($method,$param);
	}

	/**
	 * EbizApi
	 * ebiz api methods via soap
	 *
	 * @param string $apikey apikey of techuser
	 * @param integer $identity id of relation
	 * @param string $method method
	 * @param string $function function
	 * @param integer $version version
	 * @param string $params input params
	 * @param boolean $debug true/false for debug infos
	 * @return EbizApiResponse Return structure
	 */
	public function EbizApi($apikey,$identity,$method,$function,$version,$params,$debug){
		return self::_Call('EbizApi',Array(
			$apikey,
			$identity,
			$method,
			$function,
			$version,
			$params,
			$debug
		));
	}

	/**
	 * DocProcessor
	 *
	 * @param string $apikey ApiKey of technical user
	 * @param integer $identity Identity of A and B party
	 * @param string $doctype Document Type
	 * @param string $method Document processing method
	 * @param boolean $debug Debug on/off
	 * @param string $postparam1_name POST parameter name  1
	 * @param string $postparam1_value POST parameter value 1
	 * @param string $postparam2_name POST parameter name  2
	 * @param string $postparam2_value POST parameter value 2
	 * @param string $postparam3_name POST parameter name  3
	 * @param string $postparam3_value POST parameter value 3
	 * @param string $postparam4_name POST parameter name  4
	 * @param string $postparam4_value POST parameter value 4
	 * @param string $postparam5_name POST parameter name  5
	 * @param string $postparam5_value POST parameter value 5
	 * @return DocProcessorResponse Return structure
	 */
	public function DocProcessor($auth,$doctype,$method,$debug,$postparam1_name,$postparam1_value,$postparam2_name,$postparam2_value,$postparam3_name = '',$postparam3_value = '',$postparam4_name = '',$postparam4_value = '',$postparam5_name = '',$postparam5_value = ''){
		return self::_Call('DocProcessor',Array(
			$auth['apikey'],
			$auth['identity'],
			$doctype,
			$method,
			$debug,
			$postparam1_name,
			$postparam1_value,
			$postparam2_name,
			$postparam2_value,
			$postparam3_name,
			$postparam3_value,
			$postparam4_name,
			$postparam4_value,
			$postparam5_name,
			$postparam5_value
		), $auth);
	}
}

/**
 * Return type for EbizApi - Return structure
 *
 * @pw_element boolean $success - true is function is successful, otherwise false
 * @pw_element string $code - code of method's result
 * @pw_element string $alpha_code - alphanumeric code of method's result
 * @pw_element string $message - message of method's result
 * @pw_element string $result - result of method in case of success
 * @pw_element string $request - original request
 * @pw_element string $request_time - time of request
 * @pw_element string $run_time - time of run (msec)
 * @pw_element string $debug - debug message in case of enabled debug
 * @pw_complex EbizApiResponse
 */
class EbizApiResponse {
	/**
	 * - true is function is successful, otherwise false
	 *
	 * @var boolean
	 */
	public $success;
	/**
	 * - code of method's result
	 *
	 * @var string
	 */
	public $code;
	/**
	 * - alphanumeric code of method's result
	 *
	 * @var string
	 */
	public $alpha_code;
	/**
	 * - message of method's result
	 *
	 * @var string
	 */
	public $message;
	/**
	 * - result of method in case of success
	 *
	 * @var string
	 */
	public $result;
	/**
	 * - original request
	 *
	 * @var string
	 */
	public $request;
	/**
	 * - time of request
	 *
	 * @var string
	 */
	public $request_time;
	/**
	 * - time of run (msec)
	 *
	 * @var string
	 */
	public $run_time;
	/**
	 * - debug message in case of enabled debug
	 *
	 * @var string
	 */
	public $debug;
}

/**
 * Return type for DocProcessor - Return structure
 *
 * @pw_element boolean $success - True is function is successful
 * @pw_element string $message - Debug purpose description
 * @pw_element string $result - Result of call (BatchId on asynchronous call)
 * @pw_complex DocProcessorResponse
 */
class DocProcessorResponse {
	/**
	 * - True is function is successful
	 *
	 * @var boolean
	 */
	public $success;
	/**
	 * - Debug purpose description
	 *
	 * @var string
	 */
	public $message;
	/**
	 * - Result of call (BatchId on asynchronous call)
	 *
	 * @var string
	 */
	public $result;
}
