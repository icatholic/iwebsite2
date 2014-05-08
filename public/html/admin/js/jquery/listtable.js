var listTable = new Object;
listTable.query = "query";
listTable.filter = new Object;
listTable.imagepath = "";

/**
 * 创建一个可编辑区
 */
listTable.edit = function(obj, act, id, fieldname)
{
	var tag = obj.firstChild.tagName;
	if (typeof(tag) != "undefined" && tag.toLowerCase() == "input")
	{
		return;
	}
	
	/* 保存原始的内容 */
	var org = $(obj).html();//obj.innerHTML;
	var val = $(obj).text();//Browser.isIE ? obj.innerText : obj.textContent;

//	var txt = document.createElement("INPUT");
//	txt.value = (val == 'N/A') ? '' : val;
//	txt.style.width = (obj.offsetWidth + 12) + "px" ;
//	/* 隐藏对象中的内容，并将输入框加入到对象中 */
//	obj.innerHTML = "";
//	obj.appendChild(txt);
//	txt.focus();
	
	/* 创建一个输入框 */
	var txt = $('<input type = "text" />');
	txt.val(val);
	txt.css("width", (obj.offsetWidth + 12) + "px");
	/* 编辑区输入事件处理函数 */
	txt.keypress(function(event){
		if (event.which == 13)//enter
		{
			$(event.target).blur();
			return false;
		}
		if (event.which == 27)//esc
		{
			$(event.target).parent().html(org);
		}
	});
	/* 编辑区失去焦点的处理函数 */
	txt.blur(function(event){
		var newval = $.trim($(event.target).val());
		if (newval.length > 0 && newval != val )
		{
			if(fieldname == undefined)
			{
				fieldname = "val";
			}
			var data = new Object; 
			data[fieldname] = encodeURIComponent(newval);
			data.id = encodeURIComponent(id);
			
			//data = (fieldname + "=" + encodeURIComponent(newval));
			//data += ("&id=" + encodeURIComponent(id));

			$.ajax({
				type: 'POST',
				url: listTable.url + "/" + act,
				data:  data,
				success: function(res){
							if (res.message)
							{
								alert(res.message);
							}
							//
							if(res.id && (res.act == 'goods_auto' || res.act == 'article_auto'))
							{
								document.getElementById('del'+res.id).innerHTML = "<a href=\""+ res.url +"\" onclick=\"return confirm('"+deleteck+"');\">"+deleteid+"</a>";
							}
							$(event.target).parent().html((res.error == 0) ? res.content : org);
						 },
				dataType: "json"
			});
		}
		else
		{
			$(event.target).parent().html(org);
		}
	});
	/* 隐藏对象中的内容，并将输入框加入到对象中 */
	$(obj).empty();
	$(obj).append(txt);
	txt.focus();
};

/**
 * 切换状态
 */
listTable.toggle = function(obj, act, id, fieldname)
{
	var val = (obj.src.match(/yes.gif/i)) ? 0 : 1;
	
	if(fieldname == undefined)
	{
		fieldname = "val";
	}
	
	var data = new Object; 
	data[fieldname] = encodeURIComponent(val);
	data.id = encodeURIComponent(id);
	
	$.ajax({
		type: 'POST',
		url: listTable.url + "/" + act,
		data:  data,
		success: function(result){
					if (result.message)
					{
						alert(result.message);
					}
					if (result.error == 0)
					{
						obj.src = listTable.imagepath + ((result.content > 0) ? 'images/yes.gif' : 'images/no.gif');
					}
				 },
		dataType: "json"
	});
};

/**
 * 切换排序方式
 */
listTable.sort = function(sort_by, sort_order)
{
	//对同一个字段进行排序，原来的排序方式进行相反的操作
	if (listTable.filter["sort_by"] == sort_by)
	{
		listTable.filter["sort_order"] = listTable.filter["sort_order"] == "DESC" ? "ASC" : "DESC";
	}
	else
	{
		listTable.filter["sort_order"] = "DESC";
		listTable.filter["sort_by"] = sort_by;
	}
	
	listTable.filter['page_size'] = listTable.getPageSize();

	var data = listTable.compileFilter();
	
	$.ajax({
		type: 'POST',
		url: listTable.url + "/" + "query",
		data:  data,
		success: listTable.listCallback,
		dataType: "json"
	});
};

/**
 * 翻页
 */
listTable.gotoPage = function(page)
{
	if (page != null) listTable.filter['page'] = page;

	if (listTable.filter['page'] > listTable.pageCount) listTable.filter['page'] = 1;

	listTable.filter['page_size'] = listTable.getPageSize();

	listTable.loadList();
};

/**
 * 载入列表
 */
listTable.loadList = function()
{
	var data = listTable.compileFilter();
	
	$.ajax({
		type: 'POST',
		url: listTable.url + "/" + "query",
		data:  data,
		success: listTable.listCallback,
		dataType: "json"
	});
};

/**
 * 删除列表中的一个记录
 */
listTable.remove = function(id, cfm, opt)
{
	if (opt == null)
	{
		opt = "remove";
	}

	if (confirm(cfm))
	{
		var data = listTable.compileFilter();
		data.id = encodeURIComponent(id);
		
		$.ajax({
			type: 'POST',
			url: listTable.url + "/" + opt,
			data:  data,
			success: listTable.listCallback,
			dataType: "json"
		});
	}
};

listTable.gotoPageFirst = function()
{
	if (listTable.filter.page > 1)
	{
		listTable.gotoPage(1);
	}
};

listTable.gotoPagePrev = function()
{
	if (listTable.filter.page > 1)
	{
		listTable.gotoPage(listTable.filter.page - 1);
	}
};

listTable.gotoPageNext = function()
{
	if (listTable.filter.page < listTable.pageCount)
	{
		listTable.gotoPage(parseInt(listTable.filter.page) + 1);
	}
};

listTable.gotoPageLast = function()
{
	if (listTable.filter.page < listTable.pageCount)
	{
		listTable.gotoPage(listTable.pageCount);
	}
};

listTable.changePageSize = function(e)
{
	if (e.which == 13)
	{
		listTable.gotoPage();
		return false;
	};
};

listTable.compileFilter = function()
{
	var data = new Object;
	for (var i in listTable.filter)
	{
		if (typeof(listTable.filter[i]) != "function" && typeof(listTable.filter[i]) != "undefined")
		{
			data[i] = encodeURIComponent(listTable.filter[i]);
		}
	}
	return data;
};

listTable.getPageSize = function()
{
	var ps = 15;

	//var pageSize = $("#pageSize");
	//var pageSize = document.getElementById("pageSize");
	
	//if (pageSize)
	{
		ps = Utils.isInt($("#pageSize").val()) ? $("#pageSize").val() : 15;
		document.cookie = "ECSCP[page_size]=" + ps + ";";
	}
};

listTable.listCallback = function(result)
{
	if (result.error > 0)
	{
		alert(result.message);
	}
	else
	{
		try
		{
			$('#listDiv').html(result.content);
			
			if (typeof result.filter == "object")
			{
				listTable.filter = result.filter;
			}
			
			listTable.pageCount = result.page_count;
		}
		catch (e)
		{
			alert(e.message);
		}
	}
};

listTable.selectAll = function(obj, chk)
{
	if (chk == null)
	{
		chk = 'ids';
	}

	var elems = obj.form.getElementsByTagName("INPUT");

	for (var i=0; i < elems.length; i++)
	{
		if (elems[i].name == chk || elems[i].name == chk + "[]")
		{
			elems[i].checked = obj.checked;
		}
	}
	//$("input[name='"+chk+"[]']").each(function(){$(this).attr("checked", obj.checked);}); 

};

listTable.addRow = function(checkFunc)
{
	cleanWhitespace(document.getElementById("listDiv"));
	var table = document.getElementById("listDiv").childNodes[0];
	var firstRow = table.rows[0];
	var newRow = table.insertRow(-1);
	newRow.align = "center";
	var items = new Object();
	for(var i=0; i < firstRow.cells.length;i++) {
		var cel = firstRow.cells[i];
		var celName = cel.getAttribute("name");
		var newCel = newRow.insertCell(-1);
		if (!cel.getAttribute("ReadOnly") && cel.getAttribute("Type")=="TextBox")
		{
			items[celName] = document.createElement("input");
			items[celName].type= "text";
			items[celName].style.width = "50px";
			items[celName].onkeypress = function(e){
				var evt = Utils.fixEvent(e);
				var obj = Utils.srcElement(e);
			
				if (evt.keyCode == 13)
				{
					listTable.saveFunc();
				}
			};
			newCel.appendChild(items[celName]);
		}
		if (cel.getAttribute("Type") == "Button")
		{
			var saveBtn = document.createElement("input");
			saveBtn.type= "image";
			saveBtn.src = "./images/icon_add.gif";
			saveBtn.value = save;
			newCel.appendChild(saveBtn);
			this.saveFunc = function(){
				if (checkFunc)
				{
					if (!checkFunc(items))
					{
						return false;
					}
				}
				var str = "add";
				for(var key in items)
				{
					if (typeof(items[key]) != "function")
					{
						str += "/" + key + "/" + items[key].value;
					}
				}
				res = Ajax.call(listTable.url, str, null, "POST", "JSON", false);
				if (res.error)
				{
					alert(res.message);
					table.deleteRow(table.rows.length-1);
					items = null;
				}
				else
				{
					document.getElementById("listDiv").innerHTML = res.content;
					if (document.getElementById("listDiv").childNodes[0].rows.length < 6)
					{
						listTable.addRow(checkFunc);
					}
					items = null;
				}
			};
		saveBtn.onclick = this.saveFunc;
		
		//var delBtn = document.createElement("input");
		//delBtn.type= "image";
		//delBtn.src = "./images/no.gif";
		//delBtn.value = cancel;
		//newCel.appendChild(delBtn);
		}
	}
};
