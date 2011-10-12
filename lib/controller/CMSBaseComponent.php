<?php
/**
* Class defining basic building blocks of a CMS component
* Uses database to provide authentication
* @package PHP-WAX CMS
*/

class CMSBaseComponent extends WaxController {

	public $allowed_modules = array(); //all available modules for this user (ie this is just top level name)
	public $module_name = null;	//the name of this module
	public $model = false;	//the actuall database model to use
	public $model_class; //the class name - ie WildfireContent
	public $model_scope = false;
	public $user_model_class = "WildfireUser";
	public $redirects = array('unauthorised'=> "/admin/login",
	                          'authorised' => "/admin/home/",
	                          'install'=> "/admin/install/",
	                          'logout'=>"/admin/logout"
	                          );
  public $use_plugin = "cms";
	public $display_name = 'CMS'; //display name of the module
  public $use_layout = "admin"; //the default layout to use	
	public $per_page = 20; //the limit to use in lists
	public $this_page = 1;
	
  public $session; //session object
  public $user_session_name = "wf_v6_user";
  public $user_session_var_name = "user_id";
  public static $logged_in_user = false;
  public $filter_fields=array();
  public $model_filters=array();
  
  public $operation_actions = array('edit');
  public $quick_links = array();

  public $file_system_model = "WildfireFile";
  public $file_system_base = "files/";
  
  public $dashboard = false;
  
  public $search_results = array();
  public $use_cache = false;
  
  public $messages = array();

	function __construct($application = false, $init=true) {
	  parent::__construct($application);
	  if($application) $this->events();
    WaxEvent::run("cms.session.setup", $this);
	  if($init) $this->initialise();
	}
	
	public function controller_global(){
	  parent::controller_global();
	  WaxEvent::run("cms.layout.set", $this);
	  WaxEvent::run("cms.format.set", $this);
	}

	public function __destruct(){
	  $log = new WildfireLog;
	  $log->update_attributes(array('controller'=>$this->controller,
	                          'action'=>$this->action,
	                          'page'=>Request::get("id"),
	                          'param_string'=>serialize($_REQUEST),
	                          'language'=>Request::param('lang'),
	                          'wildfire_user_id'=>($this->current_user)?$this->current_user->primval:""
	                          ));

	}

  public function user_from_session($session_name="wf_v6_user"){
    if($id = $this->session->get($session_name)){
      if(self::$logged_in_user) return self::$logged_in_user;
      if(($model = new $this->user_model_class($id)) && $model->primval == $id) return self::$logged_in_user = $model;
    }
    return false;
  }
  
  protected function events(){
    WaxEvent::add("cms.session.setup", function(){
      $controller = WaxEvent::data();
      $controller->session = new WaxSession(array("name"=>$controller->user_session_name,"lifetime"=>60*60*24*30));
    });
    WaxEvent::add("cms.layout.set", function(){
      $obj = WaxEvent::data();
  	  $obj->use_layout = "login";
    });
    WaxEvent::add("cms.format.set", function(){
      $obj = WaxEvent::data();
  	  if($obj->use_format == "ajax" || $obj->use_format == "json") $obj->use_layout = false;
  	  elseif($obj->use_format == "nochrome"){
  	    $obj->use_format = "html";
  	    $obj->use_layout = "nochrome";
	    }elseif($obj->use_format == "nolayout"){
	      $obj->use_format = "html";
  	    $obj->use_layout = "nolayout";
	    }
    });
    WaxEvent::add("cms.layout.sublinks", function(){});    
    WaxEvent::add('cms.search.'.$this->module_name, function(){});
  }
	/**
	 * initialises authentication, default model and menu items
	 **/
	protected function initialise(){}

  public function sync($path){
    $model = new $this->file_system_model;
    //check existing db entries
    foreach($model->filter("rpath", $path)->all() as $file){
      $full_path = PUBLIC_DIR.$file->rpath.$file->filename;
      if(!file_exists($full_path)) $file->update_attributes(array('status'=>'lost'));
      elseif($file->status == "lost") $file->update_attributes(array('status'=>'found'));
    }
    //check filesystem files
    foreach(new RegexIterator(new DirectoryIterator(PUBLIC_DIR.$path), "#^[^\.]#i") as $file){
      $file = $file->getPathName();
      if(!is_dir($file)){
        if(is_readable($file)) exec("chmod -Rf 0777 ".$file);
        $stats = stat($file);
        $fileid = $stats[9];
        while((($found = $model->clear()->filter("id", $fileid)->filter("filename", basename($file), "!=")->all()) && $found->count() > 0 )){
          $ts = time() - rand(3600, 9000);
          
          touch($file, $ts);
          exec('touch -t '+date("YmdHis",$ts)+' '+$file);
          
          clearstatcache();
          $stats = stat($file);
          $fileid = $stats[9];
        }
        if(is_file($file)) $this->add_file($path, basename($file), $path, $fileid);
      }
    }
  }

  protected function add_file($folderpath,$filename,$rpath,$fileid){
    $folderpath = rtrim($folderpath, "/");
    if(function_exists('finfo_file')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
      $type = finfo_file($finfo, "$folderpath/$filename");
      finfo_close($finfo);
    }elseif(function_exists('mime_content_type') ){
  		$type = mime_content_type("$folderpath/$filename");
  	}else{
  		$type = exec("file --mime -b ".escapeshellarg("$folderpath/$filename"));
  	}
  	$size = filesize($folderpath."/".$filename);
  	$model = new WaxModel;
  	$query = "INSERT INTO wildfire_file (id,filename,path,rpath,type,size,status) VALUES ($fileid,'".mysql_escape_string($filename)."','$folderpath','$rpath','$type','$size','found')";
    try{
      if($type != "directory") $res = $model->query($query);
    }catch (Exception $e){}
  }
  
  public function add_message($message, $class){
    $messages = $this->session->get("messages");
    $messages[] = array('message'=>$message, 'class'=>$class);
    $this->session->set("messages", $messages);
  }
}
?>
