var collapse = true;
/**
 * 标签上鼠标移动事件的处理函数
 * @return
 */
function tabbar_div_mouseenter(e)
{
	var obj = e.target;

	if (obj.className == "tab-back")
	{
		obj.className = "tab-hover";
	}
}
function tabbar_div_mouseleave(event)
{
	var obj = e.target;

	if (obj.className == "tab-hover")
	{
		obj.className = "tab-back";
	}
}
/**
 * 处理点击标签的事件的函数
 * @param : e  FireFox 事件句柄
 * @return
 */
function tabbar_div_mouseclick(e)
{
	var obj = e.target;

	// var mnuTab = document.getElementById('menu-tab');
	var hlpTab = document.getElementById('help-tab');
	var mnuDiv = document.getElementById('menu-list');
	var hlpDiv = document.getElementById('help-div');

	//if (obj.id == 'menu-tab')
	//{
	//mnuTab.className = 'tab-front';
	//hlpTab.className = 'tab-back';
	//mnuDiv.style.display = "block";
	//hlpDiv.style.display = "none";
	//}

	if (obj.id == 'help-tab')
	{
		mnuTab.className = 'tab-back';
		hlpTab.className = 'tab-front';
		mnuDiv.style.display = "none";
		hlpDiv.style.display = "block";
		
		loc = parent.frames['main-frame'].location.href;
		pos1 = loc.lastIndexOf("/");
		pos2 = loc.lastIndexOf("?");
		pos3 = loc.indexOf("act=");
		pos4 = loc.indexOf("&", pos3);
	
		filename = loc.substring(pos1 + 1, pos2 - 4);
		act = pos4 < 0 ? loc.substring(pos3 + 4) : loc.substring(pos3 + 4, pos4);
		loadHelp(filename, act);
	}
}

function menu_list_mouseclick(e)
{
	var obj = e.target;
	toggleCollapseExpand(obj);
}

function toggleCollapse()
{
	var items = document.getElementsByTagName('LI');
	for (i = 0; i < items.length; i++)
	{
		if (collapse)
		{
			if (items[i].className == "explode")
			{
				toggleCollapseExpand(items[i], "collapse");
			}
		}
		else
		{
			if ( items[i].className == "collapse")
			{
				toggleCollapseExpand(items[i], "explode");
				ToggleHanlder.Reset();
			}
		}
	}

	collapse = !collapse;
	document.getElementById('toggleImg').src = collapse ? menuminusImage : menuplusImage;
	document.getElementById('toggleImg').alt = collapse ? collapse_all : expand_all;
}

function toggleCollapseExpand(obj, status)
{
	if (obj.tagName.toLowerCase() == 'li' && obj.className != 'menu-item')
	{
		for (i = 0; i < obj.childNodes.length; i++)
		{
			if (obj.childNodes[i].tagName == "UL")
			{
				if (status == null)
				{
					if (obj.childNodes[1].style.display != "none")
					{
						obj.childNodes[1].style.display = "none";
						ToggleHanlder.RecordState(obj.getAttribute("key"), "collapse");
						obj.className = "collapse";
					}
					else
					{
						obj.childNodes[1].style.display = "block";
						ToggleHanlder.RecordState(obj.getAttribute("key"), "explode");
						obj.className = "explode";
					}
					break;
				}
				else
				{
					if( status == "collapse")
					{
						ToggleHanlder.RecordState(obj.getAttribute("key"), "collapse");
						obj.className = "collapse";
					}
					else
					{
						ToggleHanlder.RecordState(obj.getAttribute("key"), "explode");
						obj.className = "explode";
					}
					obj.childNodes[1].style.display = (status == "explode") ? "block" : "none";
				}
			}
		}
	}
}

//菜单展合状态处理器
var ToggleHanlder = new Object();

ToggleHanlder.SourceObject = new Object();
ToggleHanlder.CookieName = 'Toggle_State';
ToggleHanlder.RecordState = function(name,state)
{
	if(state == "collapse")
	{
		this.SourceObject[name] = state;
	}
	else
	{
		if(this.SourceObject[name])
		{
			delete(this.SourceObject[name]);
		}
	}
	var date = new Date();
	date.setTime(date.getTime() + 99999999);
	document.setCookie(this.CookieName, JSON.stringify(this.SourceObject), date.toGMTString());
};

ToggleHanlder.Reset = function()
{
	var date = new Date();
	date.setTime(date.getTime() + 99999999);
	document.setCookie(this.CookieName, "{}" , date.toGMTString());
};

ToggleHanlder.Load = function()
{
	if (document.getCookie(this.CookieName) != null)
	{
		this.SourceObject = eval("("+ document.getCookie(this.CookieName) +")");
		var items = document.getElementsByTagName('LI');
		for (var i = 0; i < items.length; i++)
		{
			if ( items[0].getAttribute("name") == "menu")
			{
				for (var k in this.SourceObject)
				{
					if ( typeof(items[i]) == "object")
					{
						if (items[i].getAttribute('key') == k)
						{
							toggleCollapseExpand(items[i], this.SourceObject[k]);
							collapse = false;
						}
					}
				}
			}
		}
	}
	document.getElementById('toggleImg').src = collapse ? menuminusImage : menuplusImage;
	document.getElementById('toggleImg').alt = collapse ? collapse_all : expand_all;
};

$(document).ready(function(){
	$("#tabbar-div").hover(tabbar_div_mouseenter,tabbar_div_mouseleave);
	$("#tabbar-div").click(tabbar_div_mouseclick);
	$("#menu-list").click(menu_list_mouseclick);
	ToggleHanlder.CookieName += cookieName;
	//初始化菜单状态
	ToggleHanlder.Load();
});