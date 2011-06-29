jQuery(document).ready(function(){
  
  jQuery('.tree_list .view_children_link').live("click", function(e){
    var link = jQuery(this),
        row = jQuery(link.closest("li")),
        table = jQuery(link.closest("ul")),
        id = row.attr("data-row-id");
    
    if(link.hasClass("ajax_tree_load")){
      row.addClass('loading');
      jQuery.ajax({
        url:table.attr("data-action")+".ajax",
        data:{"filters[parent]":id},
        type:"post",
        success:function(res){
          link
            .toggleClass("ajax_tree_load open ui-icon-circle-triangle-e ui-icon-circle-triangle-s")
            .addClass("open");
          
          jQuery(res)
            .wrap("<li>")
            .parent()
            .css("display", "none")
            .insertAfter(row.removeClass('loading'))
            .addClass("children-of-"+id)
            .slideDown("fast", function(){
              var r = jQuery(this);
              r.css("height", r.css("height"));
            });
        },
        error:function(){}
      });
    }else if(link.hasClass('open')){
      link.toggleClass("open ui-icon-circle-triangle-e ui-icon-circle-triangle-s");
      jQuery('.children-of-'+id).slideUp("fast");
    }else{
      link.toggleClass("open ui-icon-circle-triangle-e ui-icon-circle-triangle-s");
      jQuery('.children-of-'+id).slideDown("fast");
    }
    e.preventDefault();
  });
  
});