/**
 * 标签上鼠标移动事件的处理函数
 * @return
 */
function mouseenter(event)
{
	if($(this).hasClass("tab-back"))
	{
		$(this).removeClass("tab-back").addClass('tab-hover');
	}
}
function mouseleave(event)
{
	if($(this).hasClass("tab-hover"))
	{
    	$(this).removeClass("tab-hover").addClass('tab-back');
	}
}
/**
 * 处理点击标签的事件的函数
 * @param : e  FireFox 事件句柄
 * @return
 */
function mouseclick(event)
{
	var obj = event.target;
	
	if (obj.className == "tab-front" || obj.className == '' || obj.tagName.toLowerCase() != 'span')
	{
		return;
	}
	else
	{
		objTable = "#" + obj.id.substring(0, obj.id.lastIndexOf("-")) + "-table";
		$("table[id$='-table']").hide();
		$(objTable).show();
		$(".tab-front").removeClass("tab-front").addClass('tab-back');
		obj.className = "tab-front";
	}
}

$(document).ready(function(){
	$("span[id$='-tab']").each(function(){
		$(this).hover(mouseenter,mouseleave);
		$(this).click(mouseclick);
	});
});