<?php

namespace NpsSDK;

use \SoapClient;
use NpsSDK\ApiException;
use NpsSDK\Configuration;

/**
 * Description of PspSoapClient
 *
 * @author NPS
 */
class SoapClientSdk extends SoapClient {
	private $_execution_timeout;
	private $_connection_timeout;
	private $_certFile;
	private $_threads;
	private $_logger;

	const ERROR_CONNECT = 1000;
	const ERROR_TIMEOUT = 1001;

	public function __construct( $options = [] ) {
		$this->_logger = Configuration::logger();

		if ( !is_array( $options ) )
			$options = [];

		$_defaultOptions = [
			"trace"				 => (Configuration::debug() ? 1 : 0),
			"exceptions"		 => 1,
			"connection_timeout" => Configuration::connectionTimeout(),
			"execution_timeout"	 => Configuration::executionTimeout()];
		$options = array_merge( $_defaultOptions, $options );

		parent::__construct( Configuration::url(), $options );
	}

	private function log( $data )
	{
		if ( Configuration::debug() && $this->_logger )
		{
			if ( Configuration::logLevel() == Constants::INFO )
			{
				$this->_logger->info( Utils::mask_data( $data ) );
			}
			else
			{
				$this->_logger->debug( $data );
			}
		}
	}

	/**
	 *
	 * @param type $request
	 * @param type $location
	 * @param type $action
	 * @param type $version
	 * @param type $one_way
	 * @return type
	 * @throws Exception
	 */
	public function __doRequest( $request, $location, $action, $version, $one_way = FALSE )
	{
		$this->log( $request );
		$ch = curl_init( $location );
		curl_setopt( $ch, CURLOPT_VERBOSE, FALSE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $request );
		curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array("SOAPAction: " . $action, "Content-Type: text/xml") );
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, Configuration::executionTimeout() );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT_MS, Configuration::connectionTimeout() );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, (Configuration::verifyPeer() ? '1' : '0' ) );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_PROXY, Configuration::proxyUrl() );
		if ( Configuration::proxyUser() != null )
		{
			$proxyauth = Configuration::proxyUser() . ':' . Configuration::proxyPass();
			curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $proxyauth );
		}


		$response = curl_exec( $ch );
		$curl_info = curl_getinfo( $ch );

		$this->log( $response );

		if ( curl_errno( $ch ) )
		{
			if ( $curl_info['connect_time'] <= 0 )
			{
				throw new ApiException( "Timeout Ocurred", self::ERROR_CONNECT );
			}
			else
			{
				throw new \Exception( curl_errno( $ch ) . " - " . curl_error( $ch ) );
			}
		}

		curl_close( $ch );

		// Return? noyes
		if ( !$one_way )
		{
			return ($response);
		}
	}

	function addExtraInf( $params )
	{
		$params["psp_MerchantAdditionalDetails"] = array("SdkInfo" => "colocar aquio el lnar la version");
		return $params;
	}

	function addSecureHash( $params, $key )
	{
		ksort( $params );
		$concatenated_data = $this->__concat_values( $params );
		$concat_data_w_key = $concatenated_data . $key;
		$s_hash = md5( $concat_data_w_key );
		$params["psp_SecureHash"] = $s_hash;
		return $params;
	}

	function __concat_values( $params )
	{
		$concated_data = "";
		foreach ( $params as $k => $v )
		{
			if ( gettype( $v ) == 'array' )
			{
				continue;
			}
			$concated_data = $concated_data . $v;
		}
		return $concated_data;
	}

}
