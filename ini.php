<?
/**
 * The first file loaded in for each plugin, should do very little and only manipulate simple values.
 * DO NOT DO ANYTHING COMPLICATED HERE!
 */

 AutoLoader::add_asset_type("help", "help");
 AutoLoader::add_asset_type('tinymce', 'tinymce');
 
 AutoLoader::register_view_path("plugin", __DIR__."/view/");
 AutoLoader::register_controller_path("plugin", __DIR__."/lib/controller/");
 AutoLoader::register_controller_path("plugin", __DIR__."/resources/app/controller/");
 AutoLoader::register_assets("wildfire",__DIR__."/resources/public/stylesheets/wildfire/", "stylesheets");
 AutoLoader::register_assets("wildfire",__DIR__."/resources/public/javascripts/wildfire/", "javascripts");
 AutoLoader::register_assets("wildfire",__DIR__."/resources/public/images/wildfire/", "images");
 AutoLoader::$plugin_array[] = array("name"=>"wildfire","dir"=>__DIR__);
 AutoLoader::register_assets("tinymce",__DIR__."/resources/public/tinymce/");
 