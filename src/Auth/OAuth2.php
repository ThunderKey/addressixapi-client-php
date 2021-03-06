<?php
namespace AddressixAPI\Auth;

use AddressixAPI\Exception\Exception AS APIException;
use AddressixAPI\Exception\AuthException;
use AddressixAPI\Exception\NotFoundException;

class OAuth2
{
  const OAUTH2_REVOKE_URI = 'https://www.addressix.com/oauth2/v1/revoke';
  const OAUTH2_TOKEN_URI = 'https://www.addressix.com/oauth2/v1/token';
  const OAUTH2_DELEGATED_TOKEN_URI = 'https://www.addressix.com/oauth2/v1/delegated_token';
  const OAUTH2_AUTH_URL = 'https://www.addressix.com/oauth2/v1/authorize';
  const OAUTH2_AUTH_BASEURL = 'https://www.addressix.com/oauth2/v1/';
  
  private $clientid;
  private $secret;
  	
  public function __construct($client)
  {
    $this->client = $client;
    $this->client_id = $client->getConfig('clientid');
    $this->client_secret = $client->getConfig('secret');
    $this->redirect_url = $client->getConfig('redirect_uri');     
  }
  
  public function setClientId($clientid)
  {
    $this->client_id = $clientid;    
  }
  
  public function setClientSecret($secret)
  {
    $this->client_secret = $secret;
  }
	
  public function setRedirectUri($url)
  {
    $this->redirect_url = $url;
  }
  
  public function forceDomain($domain)
  {
    $this->domain = $domain;
  }
  
  public function getAuthUrl()
  {
    $this->scope = '*';
    $params = array(
        'response_type' => 'code',
        'redirect_uri' => $this->redirect_url,
        'client_id' => $this->client_id,
        'scope' => $this->scope
    );
    
    if (isset($this->state)) {
      $params['state'] = $this->state;      
    }
    
    if (!isset($this->domain)) {
      return self::OAUTH2_AUTH_URL . "?" . http_build_query($params, '', '&');
    } else {
      return self::OAUTH2_AUTH_BASEURL . $this->domain . "/authorize?" . http_build_query($params, '', '&');
    }
  }

  public function getLogoutUrl($redirect=false)
  {
    $params = array();
    if ($redirect) {
      $params['redirect'] = $redirect;
    }
    if (!isset($this->domain)) {
      $url = 'https://www.addressix.com/account/logout';
    } else {
      $url = 'https://www.addressix.com/account/'.$this->domain.'/logout';
    }
    if ($params) {
      $url .= "?" . http_build_query($params, '', '&');
    }
    return $url;
  }
  
  public function fetchAccessToken($grant_type, array $parameters, array $extra_headers = array()) 
  {       
    $parameters['grant_type'] = $grant_type;
    $http_headers = $extra_headers;
    $http_headers['Accept'] = 'application/json';
    
    $parameters['client_id'] = $this->client_id;
    $parameters['client_secret'] = $this->client_secret;

    if ($grant_type=='client_credentials') {
      // authenticate with Basic auth
      $http_headers['Authorization'] = 'Basic '. base64_encode($this->client_id . ':' . $this->client_secret); 
    }
    
    if (!isset($parameters['delegation'])) {
      $response = $this->client->getRequest()->request(self::OAUTH2_TOKEN_URI, 'POST', $parameters, $http_headers, 1);
    } else {
      $response = $this->client->getRequest()->request(self::OAUTH2_DELEGATED_TOKEN_URI, 'POST', $parameters, $http_headers, 1);      
    }

    if ($response->code==200) {
      $accesstoken = $response->body;
    } else {
      if ($response->code==401) {
	throw new AuthException('Authorization failed: ' . $response->code . '.', 401);
      }
      else if ($response->code==404) {
	throw new NotFoundException('User not found: ' . $response->code . '.', 404);
      }
      else {
	throw new APIException('Request to resource failed: ' . $response->code . '.', $response->code);
      }
    }
    return $accesstoken;
  }

  public function setAccessToken($token)
  {
    $this->access_token = $token;
  }
  
  public function getAccessToken()
  {
    if (isset($this->access_token))
      return $this->access_token;
    else 
      return false;
  }

  public function getAuthHeader() {
    if (isset($this->access_token))
      return 'Bearer ' . $this->access_token;
    else 
      return false;    
  }
  
  public function authenticate($code)
  {
    $params = array(
      'code' => $_GET['code'], 
      'redirect_uri' => $this->redirect_url);
    $response = $this->fetchAccessToken('authorization_code', $params);
    if ($response->code==200) {	
      $this->access_token = $response->body->access_token;
      return $this->access_token;
    }
    else {
      throw new APIException('Authentication failed: ' .$response->code);
    }
  }
}