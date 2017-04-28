<?php
namespace AddressixAPI\App\Files;

class File extends \AddressixAPI\App\Resource
{
  protected $resource_uri = '/files';
  public $mime;
  public $revision;
  public $filename;
  public $size;
  public $created;
  public $modified;
  public $owner;
  
  public function __construct($app)
  {
    parent::__construct($app);
    $this->functions['get'] = 
      array(
        'method' => 'GET',
        'uri' => '/files/:id'
      );
    $this->functions['get_path'] = 
      array(
        'method' => 'GET',
        'uri' => '/folders/:parent/files/:path'
      );
    $this->functions['create_upload'] = 
      array(
        'method' => 'POST',
        'uri' => '/folders/:parentid/files/:filename/upload'
      );
    $this->functions['upload'] = 
      array(
        'method' => 'PUT',
        'uri' => '/upload/:sessionid'
      );
    $this->functions['add_permission'] = 
      array(
	'method' => 'POST',
	'uri' => '/files/:id/acls'
	);    
  }
  
  function set($data) {
    
    $this->id = $data->itemid;
    $this->mime = $data->mime;
    $this->revision = $data->revision;
    $this->filename = $data->filename;
    $this->size = $data->size;
    $this->created = $data->created;
    $this->modified = $data->modified;
    $this->owner = $data->owner;
  }

  function get() {
    $this->request('get', array('id' => $this->id));
    $this->set($this->data);
  }

  function create($parentid, $filename, $content, $params = array()) {
    // create the upload link
    $params['parentid'] = $parentid;
    $params['filename'] = $filename;
    $this->request('create_upload', $params);

    $sessionid = $this->data->sessionid;
    $headers = array();
    if (isset($params['mime'])) {
      $headers['Content-Type'] = $params['mime'];
    }
    $this->request('upload', array('sessionid'=>$sessionid, 'data' => $content), $headers, 3);
    return $this->get($this->data->itemid);
  }

  function addPermission($addressixid,$organisationid,$role,$type='user', array $params = array())
  {
    $params['id'] = $this->id;
    $params['addressixid'] = $addressixid;
    $params['role'] = $role;
    $params['type'] = $type;
    if ($organisationid>0) {
      $params['organisationid'] = $organisationid;
    }
    $this->request('add_permission', $params);
  }
}