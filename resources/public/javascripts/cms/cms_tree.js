$(document).ready(function() {
		tree = $('#php-file-tree');
		$('li', tree.get(0)).each(
			function()
			{
				subbranch = $('ul', this);
				if (subbranch.size() > 0) {
					if (subbranch.eq(0).css('display') == 'none') {
						$(this).prepend('<img src="/images/cms/bullet_toggle_plus.gif" width="9" height="9" class="expandImage" />');
					} else {
						$(this).prepend('<img src="/images/cms/bullet_toggle_minus.gif" width="9" height="9" class="expandImage" />');
					}
				} else {
					$(this).prepend('<img src="/images/cms/spacer.gif" width="9" height="9" class="expandImage" />');
				}
			}
		);
		$('img.expandImage', tree.get(0)).click(
			function()
			{
				if (this.src.indexOf('spacer') == -1) {
					subbranch = $('ul', this.parentNode).eq(0);
					if (subbranch.css('display') == 'none') {
						subbranch.show();
						this.src = '/images/cms/bullet_toggle_minus.gif';
					} else {
						subbranch.hide();
						this.src = '/images/cms/bullet_toggle_plus.gif';
					}
				}
			}
		);
		$('span.textHolder').Droppable(
			{
				accept			: 'treeItem',
				hoverclass		: 'dropOver',
				activeclass		: 'fakeClass',
				tollerance		: 'pointer',
				onhover			: function(dragged)
				{
					if (!this.expanded) {
						subbranches = $('ul', this.parentNode);
						if (subbranches.size() > 0) {
							subbranch = subbranches.eq(0);
							this.expanded = true;
							if (subbranch.css('display') == 'none') {
								var targetBranch = subbranch.get(0);
								this.expanderTime = window.setTimeout(
									function()
									{
										$(targetBranch).show();
										$('img.expandImage', targetBranch.parentNode).eq(0).attr('src', '/images/cms/bullet_toggle_minus.gif');
										$.recallDroppables();
									},
									500
								);
							}
						}
					}
				},
				onout			: function()
				{
					if (this.expanderTime){
						window.clearTimeout(this.expanderTime);
						this.expanded = false;
					}
				},
				ondrop			: function(dropped)
				{
					if(this.parentNode == dropped)
						return;
					if (this.expanderTime){
						window.clearTimeout(this.expanderTime);
						this.expanded = false;
					}
					subbranch = $('ul', this.parentNode);
					if (subbranch.size() == 0) {
						$(this).after('<ul></ul>');
						subbranch = $('ul', this.parentNode);
					}
					oldParent = dropped.parentNode;
					subbranch.eq(0).append(dropped);
					oldBranches = $('li', oldParent);
					if (oldBranches.size() == 0) {
						$('img.expandImage', oldParent.parentNode).src('/images/cms/spacer.gif');
						$(oldParent).remove();
					}
					expander = $('img.expandImage', this.parentNode);
					if (expander.get(0).src.indexOf('spacer') > -1)
						expander.get(0).src = '/images/cms/bullet_toggle_minus.gif';
				}
			}
		);
		$('li.treeItem').Draggable(
			{
				revert		: true,
				autoSize		: true,
				ghosting			: true/*,
				onStop		: function()
				{
					$('span.textHolder').each(
						function()
						{
							this.expanded = false;
						}
					);
				}*/
			}
		);

    $(".tree_folder").click(function(){ alert("Please load"+this.id) })
});