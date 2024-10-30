/*
* Plugin Name: Hover Pin-It
* Plugin URI: http://www.revolutiontilt.com
* Description: Places a Pinterest Pin It button directly over your images.
* Author: Michelle MacPhearson
* Author URI: http://www.fromideatoempire.com
* Version: 1.1
*/

(function($){
    var methods = {
        settings : {
            align: "topLeft",
            selector: ".pinit",
            offsetTop: "10",
            offsetRight: "10",
            offsetBottom: "10",
            offsetLeft: "10",
            minSize: "150",
            fadeSpeed: "200",
            textSource: "alt",
            opacity: "1",
            pinCount: "none",
            pinText: "doctitle"

        },
        init : function(settings){
            if(settings)
                $().extend(methods.settings, settings);
            setTimeout(function(){
                $(methods.settings.selector).each(function(){
                    var position = methods.getPosition(this);
                    var zindex = parseInt( $(this).css("z-index") ) + 1;
                    if( $(this).width() < methods.settings.minSize || $(this).height() < methods.settings.minSize )
                    	return;
                    var btn = $("<span>").attr("class", "pinit-wrapper").css({
                        top: position.top,
                        left: position.left,
                        'z-index': zindex
                    });
                    var lnk = methods.pinImage(this);
										btn.append(lnk)
                    $(this).after(btn);
                    $.ajaxSetup({async: false});
										$.getScript('//assets.pinterest.com/js/pinit.js');
										$.ajaxSetup({async: true});
                });
            }, 100);
            $("body").append("<style type='text/css'>.pinit-wrapper{width:43px;height:73px;background:transparent;position:absolute;display:none;cursor:pointer;}</style>");
            $("body").delegate(methods.settings.selector, "mouseenter", function(){
                var position = methods.getPosition(this);
                $(this).next("span.pinit-wrapper").css({
                    top: position.top,
                    left: position.left
                });
                $(this).next(".pinit-wrapper").stop(true, true).fadeTo(parseInt(methods.settings.fadeSpeed),parseFloat(methods.settings.opacity));
            }).delegate(methods.settings.selector, "mouseleave", function(){
                $(this).next(".pinit-wrapper").stop(true, true).fadeOut(parseInt(methods.settings.fadeSpeed));
            });
            $("body").delegate(".pinit-wrapper", "mouseenter", function(){
                $(this).stop(true, true).show();
            });
            $("body").delegate(".pinit-wrapper", "click", function(e){
                methods.pinImage(this);
                e.stopPropagation();
                return false;
            });
        },
        getPosition : function(obj){
            var position = {};
            var objLeft =(isNaN(parseInt($(obj).css("marginLeft")))?0:parseInt($(obj).css("marginLeft")));
            var objTop =(isNaN(parseInt($(obj).css("marginTop")))?0:parseInt($(obj).css("marginTop")));

            if(methods.settings.align === "topLeft"){
                position.top = $(obj).position().top + objTop + parseInt(methods.settings.offsetTop);
                position.left = $(obj).position().left + objLeft + parseInt(methods.settings.offsetLeft);
            }
            if(methods.settings.align === "topRight"){
                position.top = $(obj).position().top + objTop + parseInt(methods.settings.offsetTop);
                position.left = ($(obj).position().left + $(obj).width() - 43) + objLeft - parseInt(methods.settings.offsetRight);
            }
            if(methods.settings.align === "bottomLeft"){
                position.top = ($(obj).position().top + $(obj).height() - 20) + objTop - parseInt(methods.settings.offsetBottom);
                position.left = $(obj).position().left + objLeft + parseInt(methods.settings.offsetLeft);
            }
            if(methods.settings.align === "bottomRight"){
                position.top = ($(obj).position().top + $(obj).height() - 20) + objTop - parseInt(methods.settings.offsetBottom);
                position.left = ($(obj).position().left  + $(obj).width() - 43) + objLeft - parseInt(methods.settings.offsetRight);
            }
            return position;
        },
        pinUrl : function(obj){
        },
        pinImage : function(obj){
        	  var descr = (methods.settings.pinText == 'doctitle' || encodeURIComponent($(obj).get(0).alt) == '') ? encodeURIComponent(document.title) : encodeURIComponent($(obj).get(0).alt);
        		var url = '<a href="http://pinterest.com/pin/create/button/?url='+encodeURIComponent(window.location.href)+'&media='+ encodeURIComponent($(obj).get(0).src) +'&description='+descr+'"	class="pin-it-button" count-layout="'+methods.settings.pinCount+'"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a>'
            return url;
        }
    };

    $.fn.pinit = function(method){
        if(methods[method])
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        else if(typeof method === 'object' || !method)
            return methods.init.apply(this, arguments);
        else
            $.error('Method ' +  method + ' does not exist on jQuery.tooltip');
    };
})(jQuery);

