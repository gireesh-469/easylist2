var header = {};
var headerColums = [];
var form = "";
$(document).ready(function(){

	function getConfiguration(type=''){
		header = isJsonObject(decodeURIComponent($('#easylist-config').val()));
		if(header && header.autolist == true){
			getCoreData();
		}
	}
	function isHeaderObjectExist(objname){
		return (header.hasOwnProperty(objname)) ? true : false;
	}
	getConfiguration();

	function getCoreData(){
		if(isHeaderObjectExist("column")){
			var url = header.url;
			form = header.formid;
			$.ajax({
				type : 'POST',
				url : url,
				dataType : 'json',
				data : $('#'+form).serialize().replace(/%5B%5D/g,'[]'),
				success : function(response) {
					var table  	 = '<table class="table table-condensed table-hover tank-core-table">';
					table 		+= generateWidgetHeader(header.column);
					table   	+= generateWidgetTable(response);
					table  		+= "</table>";
					$('#div-list-render').html(table);
					widgetPagination(response);
				},
				error : function(response) {
					// $('html, body').animate({scrollTop : 0}, 400);
					// $('form').find('#response').empty().prepend(alert_error).fadeIn();
				}
			});
		}		
	}

	function generateWidgetTable(widgetData){

		var count = header.column.length;
		var hasAction = isHeaderObjectExist("action");
	
		var table	   = '';

		if(widgetData.return_data == 'JSON'){
			if(widgetData.data.length != 0){
				$.each(widgetData.data, function (i, witem) {
					var eachHtmlItems =  "";
					table +="<tr>";
					$.each(witem, function (key, val) {
						if($.inArray(key, headerColums)  !== -1){
							table +='<td>'+val+'</td>';
						}
					});
					if(hasAction){
						header.action.forEach(function (urlValue) {
							var mySubUrl = urlValue.match(/(?<=\{)(.*?)(?=\})/g);
							mySubUrl.forEach(function (urlEachItem) {

								if(witem.hasOwnProperty(urlEachItem)){
									urlValue = urlValue.replace('{'+urlEachItem+'}', witem[urlEachItem]);
								}else{
									urlValue = urlValue.replace('{'+urlEachItem+'}', 0);
								}
							});
							eachHtmlItems += urlValue;
						});
						table += '<td>'+eachHtmlItems+'</td>';
					}
					table +="</tr>";
				});
			}else{
				table += '<tr class="text-center"><td colspan="'+count+'"  style="text-align: center; vertical-align: middle;">No Record Found</td></tr>';
			}			
		}
			
		return table;
	}

	function generateWidgetHeader(tableheader){
		var table = '';
		$.each(tableheader, function (i, item) {
			if( item.hasOwnProperty('head') && item.hasOwnProperty('column') ){
				table += '<th ';
				if(item.hasOwnProperty('class')){ table += ' class="'+item.class+'" '; }
				if(item.hasOwnProperty('width')){ table += ' width="'+item.width+'" '; }
				table += '>';
				if(item.hasOwnProperty('sort') && item.sort != ""){
					table += '<a href="javascript:void(0)" class="sortClass" data-sort="'+item.sort+'" data-sort-type="asc" title="Sort">'+item.head+'</a>';
				}else{
					table += item.head;
				}
				headerColums.push(item.column)
				table += '</th>';
			}
		});
		table += (isHeaderObjectExist("action")) ? '<th class="text-center">Action</th>' : "";
		table += '';
		return table;
	}



// var config = JSON.parse(decodeURIComponent($('#easylist-config').val()));
// var globalColumn = config.
// var data = [{"name":"John", "age":30, "car":"BMW", "id":2},{"name":"Jibin", "age":40, "car":"OD"}];

// var hasAction = (config.hasOwnProperty("action")) ? true : false;
// data.forEach(function (eachItem) {
// 	var eachHtmlItems =  "";
// 	if(hasAction){
// 		config.action.forEach(function (urlValue) {
// 			var mySubUrl = urlValue.match(/(?<=\{)(.*?)(?=\})/g);
// 			mySubUrl.forEach(function (urlEachItem) {
// 				if(eachItem.hasOwnProperty(urlEachItem)){
// 					urlValue = urlValue.replace('{'+urlEachItem+'}', eachItem[urlEachItem]);
// 				}else{
// 					urlValue = urlValue.replace('{'+urlEachItem+'}', 0);
// 				}
// 			});
// 			eachHtmlItems += urlValue;
// 		});
// 		//console.log(1);
// 		//console.log(eachHtmlItems);
// 	}

// });

var form = 'address';
function widgetPagination(json_data){
	if(json_data){
		displayPaginationHTML(json_data.total_records, json_data.page_size, json_data.page);
	}
}

function isJsonObject(json_data) {
    try {
        return JSON.parse(json_data);
    } catch (e) {
        return false;
	}
}

function displayPaginationHTML(total_count, page_size, current_page){
	var total_pages = parseInt(Math.ceil(total_count/page_size));
	var start_page = total_count == 0 ? 0 : 1;
	var min = (current_page - 1) * page_size + start_page;
	var max = min + total_pages - start_page;
	var next_page = current_page === total_pages ? current_page : current_page + 1;
	var prev_page = current_page == 1 ? 1 : current_page - 1;

	var html = `<div class="custom-pagination">
					<a href="javascript:void(0)" class="first-page enabled" title="First" data-page="1">
      					<span class="glyphicon glyphicon-step-backward"></span>
    				</a>
    				<a href="javascript:void(0)" class="prev-page enabled" title="Previous" data-page="`+prev_page+`">
      					<span class="glyphicon glyphicon-backward"></span>
    				</a>
    				<div class="pagedisplay">
      					Records `+min+` to `+max+` (Total `+total_count+` Results) - Page `+current_page+` of `+total_pages+`
    				</div>
    				<a href="javascript:void(0)" class="next-page enabled" title="Next" data-page="`+next_page+`">
      					<span class="glyphicon glyphicon-forward"></span>
    				</a>
    				<a href="javascript:void(0)" class="last-page enabled" title="Last" data-page="`+total_pages+`">
      					<span class="glyphicon glyphicon-step-forward"></span>
    				</a>
					<select class="page-limit">
						<option value="10" `+(page_size == 10 ? "selected" : "")+`>10</option>
						<option value="25" `+(page_size == 25 ? "selected" : "")+`>25</option>
						<option value="50" `+(page_size == 50 ? "selected" : "")+`>50</option>
						<option value="100" `+(page_size == 100 ? "selected" : "")+`>100</option>
						<option value="250" `+(page_size == 250 ? "selected" : "")+`>250</option>
					</select>
  				</div>`;
	if(header.pager == 'TOP'){
		$('.custom-pagination').remove();
		$('#div-list-render').prepend(html);
	}else if(header.pager == 'BOTTOM'){
		$('.custom-pagination').remove();
		$('#div-list-render').append(html);
	}
	else{
		$('.custom-pagination').remove();
		$('#div-list-render').prepend(html);
		$('#div-list-render').append(html);
	}
}

// Pagination button handling 
$(document).on('click', '.first-page, .last-page, .next-page, .prev-page', function(e){
	e.preventDefault();
	var page_number = $(this).data('page');
	if($('#'+form).find('#page').length > 0){
		$('#'+form).find('#page').val(page_number);
	}
	else{
		addHiddenField('page', page_number, form)	
	}
	getCoreData();
});

//Pagination page size handling
$(document).on('change', '.page-limit', function(e){
	e.preventDefault();
	var page_size = $(this).val();
	if($('#'+form).find('#page-size').length > 0){
		$('#'+form).find('#page-size').val(page_size);
	}
	else{
		addHiddenField('page-size', page_size, form);
	}

	if($('#'+form).find('#page').length > 0){
		$('#'+form).find('#page').val(0);
	}
	else{
		addHiddenField('page', 0, form)
	}
	getCoreData();
});

function addHiddenField(name, value, form){
	$("<input>").attr({
		name: name,
		id: name,
		type: "hidden",
		value: value
	}).appendTo('#'+form);
}

$(document).on('click', '.sortClass', function(){
	// $('.center-cell').removeClass('sortClass-th');
	// $(this).parent('th').addClass('sortClass-th');
	var sort = $(this).attr('data-sort');
	var sort_type = $(this).attr('data-sort-type');
	if($('#'+form).find('#sort').length == 0){
		addHiddenField('sort', '', form);
	}
	if($('#'+form).find('#sort-type').length == 0){
		addHiddenField('sort-type', '', form);
	}

	if($('#'+form).find('#sort').val() == sort){
	  	if($('#'+form).find('#sort-type').val() == 'asc')
	  		$('#'+form).find('#sort-type').val('desc');
	  	else
	  		$('#'+form).find('#sort-type').val('asc');
	}else{
		$('#'+form).find('#sort').val(sort);
		$('#'+form).find('#sort-type').val(sort_type);
	}
	getCoreData();
});










});//end of document ready