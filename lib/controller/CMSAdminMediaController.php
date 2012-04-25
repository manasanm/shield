<?
class CMSAdminMediaController extends AdminComponent{
  public $uploads = true;
  public $dashboard = false;
  public $per_page = 15;
  public $preview_hover = true;
  public $module_name = "media";
  public $model_class="WildfireMedia";
  public $model_scope = "admin";
  public $display_name = "Media";

  public $sync_partial = "_media_class_list";
  public $sync_class = false;
  public $sync_locations = array();

  public $filter_fields=array(
                          'text' => array('columns'=>array('hash', 'title', 'content'), 'partial'=>'_filters_text', 'fuzzy'=>true),
                          'media_type' => array('columns'=>array('media_type'), 'partial'=>'_filters_grouped_column'),
                          'categories' => array('columns'=>array('categories'), 'partial'=>'_filters_select', 'opposite_join_column'=>'media')
                        );

  public $operation_actions = array('edit', 'download');
  public function events(){
    WaxEvent::add("cms.model.columns", function(){
      $obj = WaxEvent::data();
      $obj->scaffold_columns['preview'] = true;
    });
    parent::events();

    WaxEvent::add("cms.layout.sublinks", function(){
      $obj = WaxEvent::data();
      $mods = CMSApplication::get_modules();
      $obj->quick_links = array('sync Media'=>"/".trim($obj->controller, "/")."/sync/");
    });

    WaxEvent::add('cms.sync.class', function(){
      $obj = WaxEvent::data();
      if($class = Request::param('sync_class')){
        $obj->sync_partial = "_media_location_list";
        $obj->sync_class = $class;
      }
    });

    WaxEvent::add("cms.sync.location", function(){
      $obj = WaxEvent::data();
      if($locations = Request::param('sync_locations')){
        $class = new $obj->sync_class;
        $obj->sync_partial = "_media_sync_in_progress";
        $sync_locations = $class->sync_locations();
        foreach($locations as $k=>$i) $obj->sync_locations[$k] = $sync_locations[$k];
      }
    });

    WaxEvent::add("cms.sync.run", function(){
      $obj = WaxEvent::data();
      if($location = Request::param('sync_location')){
        $class = new $obj->sync_class;
        $obj->use_layout = false;
        $obj->use_view = "_media_sync_progress";
        $obj->sync_partial = "";
        $obj->synced = $class->sync($location);
      }
    });

  }

  //file system sync tool
  public function sync(){
    WaxEvent::run("cms.sync.class", $this);
    WaxEvent::run("cms.sync.location", $this);
    WaxEvent::run("cms.sync.run", $this);
  }
  //to loop over all media records and run a render on them, 1 every 5 seconds on the sizes set in config
  public function pre_render(){
    set_time_limit(0);
    ini_set('memory_limit','512M');
    $sizes = array_merge(array(40,200), (array) Config::get("media_sizes"));
    $model = new $model_class;
    foreach($model->filter("pre_rendered",0)->limit(5)->order("date_created DESC")->all() as $media){
      foreach($sizes as $size){
        echo "rendering $media->title @ $size<br>\r\n";
        echo $media->render($size) ."<hr>\r\n";
        ob_flush();
        flush();
        ob_end_flush();
        $media->update_attributes(array('pre_rendered'=>1));
        sleep(5);
      }
    }
    exit;
  }

}
?>