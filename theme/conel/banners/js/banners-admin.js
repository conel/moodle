var jQ = jQuery.noConflict();
jQ(document).ready(function(){
	jQ(".add_banner").colorbox({
		onOpen:function() { jQ('#banner_add_form').fadeIn(); },
		onCleanup:function() { jQ('#banner_add_form').hide(); },
			width:"600px", 
			inline:true, 
			href:"#banner_add_form"
	});
	jQ(".delete").click(function(event) {
		event.preventDefault();
		var clicked_link = jQ(this).attr('href');
		var clicked_no = jQ(this).find('span');
		var banner_no = clicked_no.attr('class');
		var answer = confirm('Are you sure you want to delete banner #' + banner_no + '?');
		if (answer) {
			// delete
			window.location.href = clicked_link;
			return false;
		}
	});
	jQ('.edit').colorbox();
});
