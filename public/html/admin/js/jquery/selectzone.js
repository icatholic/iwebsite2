/* *
 * SelectZone 类
 */
var selectZone = new Object;
selectZone.id = 1;// 1 商品关联 2 组合、赠品（带价格）
selectZone.filters = new Object;// 过滤条件
selectZone.sourceSel = null;// 源   select 对象
selectZone.targetSel = null;// 目标 select 对象
selectZone.priceObj  = '';  // 价格

/**
 * 载入源select对象的options
 * @param   string      funcName    ajax函数名称
 * @param   function    response    处理函数
 */
selectZone.loadOptions = function(act, filters)
{
	var params = {"filter":JSON.stringify(filters)};
	$.ajax({
		type: 'GET',
		url: selectZone.url + "/" + act,
		//contentType: "json",
		data:  params,
		success: selectZone.loadOptionsResponse,
		dataType: "json"
	});
};
  
/**
 * 为select元素创建options
*/
selectZone.createOptions = function(obj,arr)
{
    obj.length = 0;

    for (var i=0; i < arr.length; i++)
    {
    	var opt   = document.createElement("OPTION");
    	opt.value = arr[i].value;
    	opt.text  = arr[i].text;
    	opt.id    = arr[i].data;

    	obj.options.add(opt);
    }
};
  
/**
 * 将返回的数据解析为options的形式
 * @param   result      返回的数据
*/
selectZone.loadOptionsResponse = function(result)
{
	if (!result.error)
	{
		selectZone.createOptions(selectZone.sourceSel,result.content);
	}

	if (result.message.length > 0)
	{
		alert(result.message);
	}
	return;
};

/**
* 检查对象
* @return boolean
*/
selectZone.check = function()
{
    /* source select */
    if (!selectZone.sourceSel)
    {
    	alert('source select undefined');
    	return false;
    }
    else
    {
    	if (selectZone.sourceSel.nodeName != 'SELECT')
    	{
    		alert('source select is not SELECT');
    		return false;
    	}
    }

    /* target select */
    if (!selectZone.targetSel)
    {
    	alert('target select undefined');
    	return false;
    }
    else
    {
    	if (selectZone.targetSel.nodeName != 'SELECT')
    	{
    		alert('target select is not SELECT');
    		return false;
    	}
    }

    /* price object */
    if (selectZone.id == 2 && ! selectZone.priceObj)
    {
    	alert('price obj undefined');
    	return false;
    }

    return true;
};

/**
 * 添加选中项
 * @param   boolean  all
 * @param   string   act
 * @param   mix      arguments   其他参数，下标从[2]开始
 */
selectZone.addItem = function(all, act)
{
  if (!selectZone.check())
  {
    return;
  }

  var selOpt  = new Array();

  for (var i = 0; i < selectZone.sourceSel.length; i ++ )
  {
    if (!selectZone.sourceSel.options[i].selected && all == false) continue;

    if (selectZone.targetSel.length > 0)
    {
      var exsits = false;
      for (var j = 0; j < selectZone.targetSel.length; j ++ )
      {
        if (selectZone.targetSel.options[j].value == selectZone.sourceSel.options[i].value)
        {
          exsits = true;

          break;
        }
      }

      if (!exsits)
      {
        selOpt[selOpt.length] = selectZone.sourceSel.options[i].value;
      }
    }
    else
    {
      selOpt[selOpt.length] = selectZone.sourceSel.options[i].value;
    }
  }

  if (selOpt.length > 0)
  {
	  var args = new Array();

      for (var i=2; i<arguments.length; i++)
      {
        args[args.length] = arguments[i];
      }
      
	  var params = {"add_ids":JSON.stringify(selOpt),"JSON":JSON.stringify(args)};
		$.ajax({
			type: 'GET',
			url: selectZone.url + "/" + act,
			//contentType: "json",
			data:  params,
			success: selectZone.addRemoveItemResponse,
			dataType: "json"
		}); 
    //Ajax.call(this.filename + "&act="+act+"&add_ids=" +selOpt.toJSONString(), args, this.addRemoveItemResponse, "GET", "JSON");
  }
};

/**
 * 删除选中项
 * @param   boolean    all
 * @param   string     act
 */
selectZone.dropItem = function(all, act)
{
  if (!selectZone.check())
  {
    return;
  }

  var arr = new Array();

  for (var i = selectZone.targetSel.length - 1; i >= 0 ; i -- )
  {
    if (selectZone.targetSel.options[i].selected || all)
    {
      arr[arr.length] = selectZone.targetSel.options[i].value;
    }
  }

  if (arr.length > 0)
  {
    var args = new Array();

    for (var i=2; i<arguments.length; i++)
    {
      args[args.length] = arguments[i];
    }
    var params = {"drop_ids":JSON.stringify(arr),"JSON":JSON.stringify(args)};
	$.ajax({
		type: 'GET',
		url: selectZone.url + "/" + act,
		//contentType: "json",
		data:  params,
		success: selectZone.addRemoveItemResponse,
		dataType: "json"
	}); 
	
    //Ajax.call(this.filename + "&act="+act+"&drop_ids=" + arr.toJSONString(), args, this.addRemoveItemResponse, 'GET', 'JSON');
  }
};

/**
 * 处理添加项返回的函数
*/
selectZone.addRemoveItemResponse = function(result)
{
    if (!result.error)
    {
    	selectZone.createOptions(selectZone.targetSel, result.content);
    }

    if (result.message.length > 0)
    {
    	alert(result.message);
    }
};
