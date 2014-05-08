/**
 * 标签上鼠标移动事件的处理函数
 * @return
 */
function listdiv_mouseenter(event)
{
	obj = event.target;
	
	if (obj)
	{
		
		if (obj.parentNode.tagName.toLowerCase() == "tr")
		{
			row = obj.parentNode;
		}
		else if (obj.parentNode.parentNode.tagName.toLowerCase() == "tr") 
		{
			row = obj.parentNode.parentNode;
		}
		else 
		{
			return;
		}		
		for (i = 0; i < row.cells.length; i++)
		{
			if (row.cells[i].tagName != "TH") 
			{
				row.cells[i].style.backgroundColor = '#F4FAFB';
			}
		}
	}
}
function listdiv_mouseleave(event)
{
	obj = event.target;

	if (obj)
	{
		if (obj.parentNode.tagName.toLowerCase() == "tr")
		{
			row = obj.parentNode;
		}
		else if (obj.parentNode.parentNode.tagName.toLowerCase() == "tr")
		{
			row = obj.parentNode.parentNode;
		}
		else 
		{
			return;
		}

		for (i = 0; i < row.cells.length; i++)
		{
			if (row.cells[i].tagName != "TH")
			{
				row.cells[i].style.backgroundColor = '#FFF';
			}
		}
	}
}
/**
 * 处理点击标签的事件的函数
 * @param : e  FireFox 事件句柄
 * @return
 */
function listdiv_mouseclick(event)
{
	var obj = event.target;

	if (obj.tagName.toLowerCase() == "input" && obj.type.toLowerCase() == "checkbox")
	{
		if (!document.forms['listForm'])
		{
			return;
		}
		
		var nodes = document.forms['listForm'].elements;
		var checked= false;

		for (i = 0; i < nodes.length; i++)
		{
			if (nodes[i].checked )
			{
				checked= true;
				break;
			}
		}

		if(document.getElementById("btnSubmit"))
		{
			document.getElementById("btnSubmit").disabled = ! checked ;
		}
		
		for (i = 1; i <= 10; i++)
		{
			if (document.getElementById("btnSubmit" + i))
			{
				document.getElementById("btnSubmit" + i).disabled = ! checked ;
			}
		}
	}
}

$(document).ready(function(){
	$("#listDiv").find("*").each(function(){
		//$(this).mouseover(mouseenter1);
		//$(this).mouseout(mouseleave1);
		$(this).hover(listdiv_mouseenter,listdiv_mouseleave);
		
	});
	$("#listDiv").click(listdiv_mouseclick);
});