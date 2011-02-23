<?php

class WildfireContent extends WaxTreeModel {


	public function setup(){
	  $this->define("status", "IntegerField", array('default'=>0, 'maxlength'=>2, "widget"=>"SelectInput", "choices"=>array(0=>"Draft/Revision",1=>"Live"), 'scaffold'=>true, 'editable'=>false));

		$this->define("title", "CharField", array('maxlength'=>255, 'scaffold'=>true, 'default'=>"enter title here") );
		$this->define("content", "TextField", array('widget'=>"TinymceTextareaInput"));

		$this->define("date_start", "DateTimeField", array('scaffold'=>true, 'default'=>date("j F Y H:i"), 'output_format'=>"j F Y H:i"));
		$this->define("date_end", "DateTimeField", array('scaffold'=>true, 'default'=>date("j F Y H:i",mktime(0,0,0, date("m"), date("j"), date("y")+10 )), 'output_format'=>"j F Y H:i" ));

		$this->define("files", "ManyToManyField", array('target_model'=>"WildfireFile", "eager_loading"=>true, "join_model_class"=>"WildfireOrderedTagJoin", "join_order"=>"join_order", 'group'=>'files'));
		$this->define("categories", "ManyToManyField", array('target_model'=>"WildfireCategory","eager_loading"=>true, "join_model_class"=>"WaxModelOrderedJoin", "join_order"=>"id", 'scaffold'=>true, 'group'=>'relationships'));

    $langs = array();
    foreach(CMSApplication::$languages as $i=>$l) $langs[$i] = $l['name'];
    $default = array_shift(array_keys(CMSApplication::$languages));
    $this->define("language", "IntegerField", array('choices'=>$langs, 'widget'=>"HiddenInput", 'default'=>$default, 'group'=>'all versions', 'editable'=>true, 'scaffold'=> (count(CMSApplication::$languages)>1)?true:false));

    //main grouping field
		$this->define("permalink", "CharField", array('group'=>'urls'));

		$this->define("excerpt", "TextField", array('group'=>'others', 'editable'=>false));
		$this->define("meta_description", "TextField", array('group'=>'others', 'editable'=>false));
		$this->define("meta_keywords", "TextField", array('group'=>'others', 'editable'=>false));

		//hidden extras
		$this->define("author", "ForeignKey", array('target_model'=>"WildfireUser", 'scaffold'=>true, 'widget'=>'HiddenInput'));
		$this->define("sort", "IntegerField", array('maxlength'=>3, 'default'=>0, 'widget'=>"HiddenInput", 'group'=>'parent'));
		$this->define("date_modified", "DateTimeField", array("editable"=>false));
		$this->define("date_created", "DateTimeField", array("editable"=>false));

		$this->define("revision", "IntegerField", array("default"=>0, 'widget'=>"HiddenInput", 'editable'=>false));
		$this->define("alt_language", "IntegerField", array("default"=>0, 'widget'=>"HiddenInput"));
		
		$this->define("view", "CharField", array('widget'=>'SelectInput', 'choices'=>$this->cms_views() ));
	}

	public function tree_setup(){
	  if(!$this->parent_column) $this->parent_column = "parent";
    if(!$this->children_column) $this->children_column = "children";
    if(!$this->parent_join_field) $this->parent_join_field = $this->parent_column."_".$this->primary_key;
	  $this->define($this->parent_column, "ForeignKey", array("col_name" => "parent_id", "target_model" => get_class($this), 'group'=>'parent', 'widget'=>'HiddenInput'));
    $this->define($this->children_column, "HasManyField", array("target_model" => get_class($this), "join_field" => $this->parent_join_field, "eager_loading" => true, 'associations_block'=>true));
	}

	public function scope_admin(){
	  return $this->order("status DESC");
	}

	public function scope_live(){
    return $this->filter("status", 1)->filter("TIMESTAMPDIFF(SECOND, `date_start`, NOW()) >= 0")->filter("(`date_end` <= `date_start` OR (`date_end` >= `date_start` AND `date_end` >= NOW()) )");
  }
  public function scope_preview(){
    return $this->filter("status", 0);
  }

  public function before_save(){
    if(!$this->date_start) $this->date_start = date("Y-m-d H:i:s");
    if(!$this->date_created) $this->date_created = date("Y-m-d H:i:s");
    if(!$this->parent_id) $this->parent_id = 0;
    $this->date_modified = date("Y-m-d H:i:s");
  }
  //after save, we need to update the url mapping
  public function after_save(){}
  /**
   * compare the url maps of this model to another and return the results (remove & add)
   */
  public function url_compare($alt){
    $map = new WildfireUrlMap;
    $current_urls = $alt_urls = array();
    //this models urls
    foreach($map->clear()->filter("destination_model", get_class($this))->filter('destination_id', $this->primval)->all() as $url) $current_urls[] = $url->origin_url;
    foreach($map->clear()->filter("destination_model", get_class($alt))->filter('destination_id', $alt->primval)->all() as $url) $alt_urls[] = $url->origin_url;
    return array('remove'=>array_reverse(array_unique(array_diff($alt_urls,$current_urls))), 'add'=> array_reverse(array_unique(array_diff($current_urls, $alt_urls))));
  }
  /**
   * making live mapping urls
   */
  public function map_live(){
    $map = new WildfireUrlMap;
    $class = get_class($this);
    $mod = new $class;
    $permalink = $this->language_permalink($this->language);
    //look for all urls linked to this model and put them live
    foreach($map->clear()->filter("destination_model", $class)->filter("destination_id", $this->primval)->all() as $row){
      $row->update_attributes(array('status'=>1, 'date_start'=>$this->date_start, 'date_end'=>$this->date_end));
      //for each of these models permalinks look for one from an alternative model
      foreach($map->clear()->filter("origin_url", $row->permalink)->filter("language", $this->language)->filter($row->primary_key,$row->primval, "!=")->all() as $alt) $alt->update_attributes(array('status'=>0));
    }
    //look for any urls that were linked to the master version of this content item
    if($master_id = $this->revision()){
      //if you find items linked to the master & turn them off
      foreach($map->clear()->filter("destination_id", $master_id)->filter("destination_model",$class)->all() as $row) $row->update_attributes(array('status'=>0));
    }
    //if have no existing maps then create one
    if(!$map->clear()->filter("destination_model", $class)->filter("origin_url", $permalink)->first()) $map->map_to($permalink, $this, $this->primval, 1);
    //tidy up duplicate permalinks

    return $this;
  }
  /**
   * turn off the urls for this model
   */
  public function map_hide(){
    $map = new WildfireUrlMap;
    $class = get_class($this);
    $permalink = $this->language_permalink($this->language);
    //look for all urls linked to this model and hide them
    foreach($map->filter("destination_id", $this->primval)->filter("destination_model",$class)->all() as $row) $row->update_attributes(array('status'=>0, 'date_start'=>$this->date_start, 'date_end'=>$this->date_end));
    //if have no existing maps then create one
    if(!$map->clear()->filter("destination_model", $class)->filter("origin_url", $permalink)->first()) $map->map_to($permalink, $this, $this->primval, 0);
    return $this;
  }
  /**
   * if this is a revision then we will copy over everything from the mapping table to this one
   */
  public function map_revision(){
    $map = new WildfireUrlMap;
    $class = get_class($this);
    if($id = $this->revision()){
      foreach($map->filter("destination_id", $id)->filter("destination_model", $class)->all() as $r) $r->copy()->update_attributes(array('status'=>0, 'destination_id'=>$this->primval));
    }elseif($this->revision == 0){
      $mod = new $class;
      //for all revisions of this content copy the url maps over for them with status of 0
      foreach($mod->clear()->filter($this->primary_key, $this->primval, "!=")->filter("permalink", $this->permalink)->filter("language", $this->language)->all() as $rev) $rev->map_revision();
    }
    return $this;
  }
  /**
   * when putting a revision item live need to grab its children and move them over
   * as this is disabled on this model to avoid children of live being moved to hidden
   * revisions!
   */
  public function children_move(){
    if($id = $this->revision()){
      $class = get_class($class);
      $model = new $class($id);
      foreach($model->children as $c){
        $model->children->unlink($c);
        $this->children = $c;
      }
    }
    return $this;
  }

  
  public function revision(){
    return $this->revision;
  }
  public function alt_language(){
    return $this->alt_language;
  }
  public function is_live(){
    return $this->status;
  }
  public function master(){
    return !$this->revision;
  }
  
  public function find_master(){
    if($this->revision){
      $class = get_class($this);
      return new $class($this->revision);
    }
  }

  public function has_revisions(){
    $class = get_class($this);
    $model = new $class;
    return $model->filter("revision", $this->primval)->all();
  }

  public function show(){
    $class = get_class($this);
    $model = new $class;
    //find all content with this language and permalink and update their revision values & status
    foreach($model->filter("permalink", $this->permalink)->filter("language", $this->language)->all() as $r) $r->update_attributes(array('status'=>0, 'revision'=>$this->primval));
    $this->status = 1;
    $this->revision = 0;
    return $this;
  }
  public function hide(){
    $this->status = 0;
    return $this;
  }


  public function url(){
    if($this->title != $this->columns['title'][1]['default']){
      $test = $base = Inflections::to_url($this->title);
      $class = get_class($this);
      $model = new $class;
      while($model->clear()->filter("permalink", "/".trim($test, "/")."/")->first()) $test = $base."-".rand(0,99);
      return $test;
    }
    else return false;
  }
  /**
   * find all the possible views for the cms in the default location
   */
  public function cms_views(){
    $dir = VIEW_DIR."page/";
    $return = array();    
    if(is_dir($dir) && ($files = glob($dir."cms_*.html"))){
      foreach($files as $f){
        $i = str_replace($dir, "", $f);
        $return[$i] = basename($f, ".html");
      }
    }
    return $return;
  }
  
  //this will need updating when the framework can handle manipulating join columns
  public function file_meta_set($fileid, $tag, $order=0){
    $model = new WaxModel;
    $model->table = $this->table."_wildfire_file";
    $col = $this->table."_".$this->primary_key;
    if(!$order) $order = 0;
    foreach($model->filter($col, $this->primval)->filter("wildfire_file_id", $fileid)->all() as $r){
      $sql = "UPDATE `".$model->table."` SET `join_order`=$order, `tag`='$tag' WHERE `id`=$r->primval";
      $model->query($sql);
    }
  }
  public function file_meta_get($fileid){
    $model = new WaxModel;
    $model->table = $this->table."_wildfire_file";
    $col = $this->table."_".$this->primary_key;
    return $model->filter($col, $this->primval)->filter("wildfire_file_id", $fileid)->first();
  }

	public function format_content() {
    return CmsTextFilter::filter("before_output", $this->content);
  }
  //ignore the language, as we are grouping by this field
  public function generate_permalink(){
    $class = get_class($this);
    if($this->permalink) return $this;
    else if($this->parent_id){
      $p = new $class($this->parent_id);
      $this->permalink = $p->permalink.$this->url()."/";
    }
    else if($url = $this->url()) $this->permalink = "/".$url."/";
    return $this;
  }
  
  protected function language_permalink($lang_id){
    $lang_url = "";
    if(CMSApplication::$languages[$lang_id] && ($url = CMSApplication::$languages[$lang_id]['url'])) $lang_url = "/".$url;
    return $lang_url.$this->generate_permalink()->permalink;
  }
  


}
