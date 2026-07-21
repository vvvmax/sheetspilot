"use strict";


function UniteAdminSheetsPilot(){
	
	var t = this;
	
	var g_errorMessageID = null, g_hideMessageCounter = 0, g_errorMessageHideFunc = null;
	var g_ajaxLoaderID = null, g_ajaxHideButtonID = null, g_successMessageID = null, g_ajaxErrorID = null;	
	var g_colorPickerCallback = null;
	var g_providerAdmin = new UniteProviderAdminSheetsPilot(), g_dialogActivation;
	g_providerAdmin.setParent(this);
	
	var g_temp = {
		handle:null,
		keyupTrashold: 500,
		timer:null
	};
	
	this.getvalopt = {
			FORCE_BOOLEAN: "force_boolean",
			FORCE_NUMERIC: "force_numeric",
			TRIM: "trim"
	};
	
	
	this.__________GENERAL_FUNCTIONS_____ = function(){};	

	/**
	 * check if debug mode
	 */
	this.isDebugMode = function(){
		
		var debugMode = false;
		
		if(typeof g_doublyDebugMode != "undefined" && g_doublyDebugMode === true)
			debugMode = true;
		
		return(debugMode);
	}
	
	/**
	 * debug html on the top of the page (from the master view)
	 */
	this.debug = function(html){
		html += "<a href='javascript:jQuery(\"#div_debug\").hide()' class='unite-debug-close'>X</a>";
		jQuery("#div_debug").show().html(html);
	};
	
	
	
	/**
	 * output data to console
	 */
	this.trace = function(data,clear){
		if(clear && clear == true)
		console.clear();	
		//console.trace();		
		console.log(data);
	};
	
		
	
	/**
	 * check if was pressed right mouse button
	 */
	this.isRightButtonPressed = function(event){
		
		if(event.buttons == 2 || event.button == 2)
			return(true);
		
		return(false);
	};

	
	
	
	/**
	 * insert to CodeMirror editor
	 * @param data
	 */
	this.insertToCodeMirror = function(cm, text){
		
	    var doc = cm.getDoc();
	    var cursor = doc.getCursor(); 
	    	    
	    doc.replaceSelection(text); 
	    
	    /*
	    //set marked
	    var to = {
	    		line: cursor.line,
	    		ch: cursor.ch+text.length
	    }
	    
	    var options = {
	    		className:"uc-cm-mark-key"
	    };
	    	    
	    doc.markText(cursor, to, options);
	    */
	};
	
	
	/**
	 * get random number
	 */
	this.getRandomNumber = function(){
		  var min = 1;
		  var max = 1000000;
		  return Math.floor(Math.random() * (max - min + 1) + min);
	};
	
	
	
	
	/**
	 * get object property
	 */
	this.getVal = function(obj, name, defaultValue, opt){
		
		if(!defaultValue)
			var defaultValue = "";
		
		var val = "";
		
		if(!obj || typeof obj != "object")
			val = defaultValue;
		else if(obj.hasOwnProperty(name) == false){
			val = defaultValue;
		}else{
			val = obj[name];			
		}
		
		//sanitize
		
		switch(opt){
			case t.getvalopt.FORCE_BOOLEAN:
				val = t.strToBool(val);
			break;
			case t.getvalopt.TRIM:
				val = String(val);
				val = jQuery.trim(val);
			break;
			case t.getvalopt.FORCE_NUMERIC:
				val = jQuery.trim(val);
				if(typeof val == "string"){
					val.replace("px","");
					val = Number(val);
				}
			break;
		}
		
		return(val);
	}
	
	
	/**
	 * add css setting to object
	 */
	this.addCssSetting = function(objSettings, objCss, name, cssName, suffix){
		
		if(!suffix)
			var suffix = "";
		
		var value = t.getVal(objSettings, name, null);
		
		if(value)			
			objCss[cssName] = value + suffix;
		
		return(objCss);
	};
	
	

	
	/**
	 * get simple object size
	 */
	this.objSize = function(obj) {
	    var count = 0;
	    
	    if (typeof obj == "object") {
	    
	        if (Object.keys) {
	            count = Object.keys(obj).length;
	        } else if (window._) {
	            count = _.keys(obj).length;
	        } else if (window.jQuery) {
	            count = jQuery.map(obj, function() { return 1; }).length;
	        } else {
	            for (var key in obj) if (obj.hasOwnProperty(key)) count++;
	        }
	        
	    }
	    
	    return count;
	};
	
	/**
	 * check if property object exists
	 */
	this.isObjPropertyExists = function(object, name){
		
		if(typeof object != "object")
			return(false);
		
		return object.hasOwnProperty(name);
	}
	
	this.__________ARRAYS_____ = function(){};	
	
	/**
	 * return if source array includes any of the second array values
	 */
	this.isArrIncludesAnotherArrItem = function(source, second){
		
		var isContains = second.some(function(value){
			
			return source.includes(value);
		});
		
		return(isContains);
	}
	
	this.__________STRINGS_____ = function(){};	

	/**
	 * get text diff
	 */
	this.getTextDiff = function(first, second) {
		
	    var start = 0;
	    while (start < first.length && first[start] == second[start]) {
	        ++start;
	    }
	    var end = 0;
	    while (first.length - end > start && first[first.length - end - 1] == second[second.length - end - 1]) {
	        ++end;
	    }
	    end = second.length - end;
	    return second.substr(start, end - start);
	}	
	
	
	/**
	 * raw url decode
	 */
	function rawurldecode(str){return decodeURIComponent(str+'');}
	
	/**
	 * raw url encode
	 */
	function rawurlencode(str){str=(str+'').toString();return encodeURIComponent(str).replace(/!/g,'%21').replace(/'/g,'%27').replace(/\(/g,'%28').replace(/\)/g,'%29').replace(/\*/g,'%2A');}
	
	
	/**
	 * utf8 decode
	 */
	function utf8_decode(str_data){var tmp_arr=[],i=0,ac=0,c1=0,c2=0,c3=0;str_data+='';while(i<str_data.length){c1=str_data.charCodeAt(i);if(c1<128){tmp_arr[ac++]=String.fromCharCode(c1);i++;}else if(c1>191&&c1<224){c2=str_data.charCodeAt(i+1);tmp_arr[ac++]=String.fromCharCode(((c1&31)<<6)|(c2&63));i+=2;}else{c2=str_data.charCodeAt(i+1);c3=str_data.charCodeAt(i+2);tmp_arr[ac++]=String.fromCharCode(((c1&15)<<12)|((c2&63)<<6)|(c3&63));i+=3;}}
	return tmp_arr.join('');}
	
	/**
	 * base 64 decode
	 */
	this.base64_decode = function(data){var b64="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";var o1,o2,o3,h1,h2,h3,h4,bits,i=0,ac=0,dec="",tmp_arr=[];if(!data){return data;}
	data+='';do{h1=b64.indexOf(data.charAt(i++));h2=b64.indexOf(data.charAt(i++));h3=b64.indexOf(data.charAt(i++));h4=b64.indexOf(data.charAt(i++));bits=h1<<18|h2<<12|h3<<6|h4;o1=bits>>16&0xff;o2=bits>>8&0xff;o3=bits&0xff;if(h3==64){tmp_arr[ac++]=String.fromCharCode(o1);}else if(h4==64){tmp_arr[ac++]=String.fromCharCode(o1,o2);}else{tmp_arr[ac++]=String.fromCharCode(o1,o2,o3);}}while(i<data.length);dec=tmp_arr.join('');dec=utf8_decode(dec);return dec;}
	
	
	/**
	 * utf-8 encode
	 */
	function utf8_encode(argString){
		if(argString===null||typeof argString==="undefined"){return"";}
		var string=(argString+'');var utftext="",start,end,stringl=0;start=end=0;stringl=string.length;for(var n=0;n<stringl;n++){var c1=string.charCodeAt(n);var enc=null;if(c1<128){end++;}else if(c1>127&&c1<2048){enc=String.fromCharCode((c1>>6)|192)+String.fromCharCode((c1&63)|128);}else{enc=String.fromCharCode((c1>>12)|224)+String.fromCharCode(((c1>>6)&63)|128)+String.fromCharCode((c1&63)|128);}
		if(enc!==null){if(end>start){utftext+=string.slice(start,end);}
		utftext+=enc;start=end=n+1;}}
		if(end>start){utftext+=string.slice(start,stringl);}
	return utftext;}
	
	
	/**
	 * base 64 encode
	 */
	this.base64_encode = function(data){
		var b64="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";var o1,o2,o3,h1,h2,h3,h4,bits,i=0,ac=0,enc="",tmp_arr=[];if(!data){return data;}
		data=utf8_encode(data+'');do{o1=data.charCodeAt(i++);o2=data.charCodeAt(i++);o3=data.charCodeAt(i++);bits=o1<<16|o2<<8|o3;h1=bits>>18&0x3f;h2=bits>>12&0x3f;h3=bits>>6&0x3f;h4=bits&0x3f;tmp_arr[ac++]=b64.charAt(h1)+b64.charAt(h2)+b64.charAt(h3)+b64.charAt(h4);}while(i<data.length);enc=tmp_arr.join('');var r=data.length%3;return(r?enc.slice(0,r-3):enc)+'==='.slice(r||3);
	}
	
	
	/**
	 * encode some content
	 */
	this.encodeContent = function(value){
		return t.base64_encode(rawurlencode(value));
	};
	
	
	/**
	 * get hash of some string or object
	 */
	this.getHash = function(str){
		
		if(!str)
			return("");
		
		var asString = true;
		
		if(typeof str == "object")
			str = JSON.stringify(str);
		else{
			if(typeof str != "string")
				str = String(str);
		}
		
		/*jshint bitwise:false */
	    var i, l;
	    
	    var hval = 0x811c9dc5;
	    	
	    for (i = 0, l = str.length; i < l; i++) {
	        hval ^= str.charCodeAt(i);
	        hval += (hval << 1) + (hval << 4) + (hval << 7) + (hval << 8) + (hval << 24);
	    }
	    if( asString ){
	        // Convert to 8 digit hex string
	        return ("0000000" + (hval >>> 0).toString(16)).substr(-8);
	    }
	    return hval >>> 0;		
		
		/*
		return s.split("").reduce(function(a, b) {
		      a = ((a << 5) - a) + b.charCodeAt(0);
		      return a & a
		}, 0);		
		
		/*
	    var hash = 0,
	      i, char;
	    if (s.length == 0) return hash;
	    for (i = 0, l = s.length; i < l; i++) {
	      char = s.charCodeAt(i);
	      hash = ((hash << 5) - hash) + char;
	      hash |= 0; // Convert to 32bit integer
	    }
	    return hash;
	    */
	};
	
	/**
	 * encode object for save
	 */
	this.encodeObjectForSave = function(objData){
		
		var jsonData = JSON.stringify(objData);
		var strEncodedData = t.encodeContent(jsonData);
		
		return(strEncodedData);
	};
	
	/**
	 * decode some content
	 */
	this.decodeContent = function(value){
		
		return rawurldecode(t.base64_decode(value));
	};
	
	
	/**
	 * get random string
	 */
	this.getRandomString = function(numChars) {
		 
		if(!numChars)
			 var numChars = 8;
		 
		var text = "";
		var possible = "abcdefghijklmnopqrstuvwxyz0123456789";
		
		for (var i = 0; i < numChars; i++)
		   text += possible.charAt(Math.floor(Math.random() * possible.length));
	
		return text;
	};	
	
	/**
	 * return true if some string has english chars only (not latin etc)
	 */
	this.isStringAscii = function(str){
		
		var isAscii = /^[ -~\t\n\r]+$/.test(str);
		
		return(isAscii);
	}
	
	/**
	 * get name from some title
	 */
	this.getNameFromTitle = function(title){
		
		var name = title.trim();
		
		// trim. replace spaces. lowercase
		name = name.replace( /\W+/g, '_' );

		name = name.toLowerCase();
		
		return(name);
	}
	
	this.__________EVENTS_____ = function(){};	


	/**
	 * trigger some event
	 */
	this.triggerEvent = function(eventName, opt1){
		
		eventName = "unite_" + eventName;
				
		jQuery("body").trigger(eventName, opt1);
		
	};
	
	
	/**
	 * on some event
	 */
	this.onEvent = function(eventName, func, objBody){
		
		eventName = "unite_" + eventName;
		
		if(!objBody)
			var objBody = jQuery("body");
		
		objBody.on(eventName, func);
	};
	
	
	/**
	 * destroy some event
	 */
	this.offEvent = function(eventName){
		
		jQuery("body").off(eventName);
		
	};
	
	
	/**
	 * run function with trashold
	 */
	this.runWithTrashold = function(func, event, objInput){
		
		if(g_temp.handle)
			clearTimeout(g_temp.handle);
		
		g_temp.handle = setTimeout(function(){
			func(event, objInput);
		}
		, g_temp.keyupTrashold);
		
	};
	
	
	/**
	 * run on change input value with trashold
	 */
	this.onChangeInputValue = function(objInput, func){
		
		objInput.keyup(function(){
			
			t.runWithTrashold(function(event){
				var value = objInput.val();
				var oldValue = objInput.data("uc_old_val");
				if(value !== oldValue)
					func(event, objInput);
				
				objInput.data("uc_old_val",value);
			});
			
		});
		
	};
	
	
	this.__________HTML_RELATED_____ = function(){};	

	/**
	 * add some option to select
	 */
	this.addOptionToSelect = function(objSelect, value, text, addDataName, addDataValue){
		
		var option = jQuery('<option>', {
		    value: value,
		    text: text
		});
		
		if(addDataName)
			option.data(addDataName, addDataValue);
		
		objSelect.append(option);
		
	};
	
	
	/**
	 * add text to input, to specific place if available
	 */
	this.addTextToInput = function(objInput, addText){
		
		
		var type = t.getInputType(objInput);
		if(type != "text" && type != "textarea"){
			trace(objInput);
			throw new Error("wrong input type: " + type);
		}
		
		var input = objInput[0];
		var cursorPos = undefined;
		if(typeof input.selectionStart != "undefined")
			cursorPos = input.selectionStart;
		
		var value = objInput.val();
		
		if(cursorPos === undefined)
			value += addText;
		else	
			value = value.substr(0, cursorPos) + addText + value.substr(cursorPos);
		
		objInput.val(value);
		objInput.focus();
		
		if(cursorPos !== undefined){
			var newPos = cursorPos + addText.length;
			input.setSelectionRange(newPos, newPos);
		}
		
	};
	
	
	/**
	 * load include file, js or css
	 * additional values: "replaceID, addProtocol"
	 */
	this.loadIncludeFile = function(type, url, data){
		
		//additional input values
		var addProtocol = t.getVal(data, "addProtocol", false, t.getvalopt.FORCE_BOOLEAN);
		var replaceID = t.getVal(data, "replaceID");
		var name = t.getVal(data, "name");
		var onload = t.getVal(data, "onload");
		
		if(addProtocol === true)
			url = location.protocol + "//" + url;
		
		//add random number at the end
		var noRand = t.getVal(data, "norand");
		if(!noRand){
			var rand = Math.floor((Math.random()*100000)+1);
			
			if(url.indexOf("?") == -1)
				url += "?rand="+rand;
			else
				url += "&rand="+rand;
		}
		
		if(replaceID)
			jQuery("#"+replaceID).remove();
		
		switch(type){
			case "js":
				var tag = document.createElement('script');
				tag.src = url;
				
				//add onload function if exists
				if(typeof onload == "function"){
					
					tag.onload = function(){
						onload(jQuery(this), replaceID);
					};
					
				}
				
				var firstScriptTag = document.getElementsByTagName('script')[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
				tag = jQuery(tag);
				
				if(name)
					tag.attr("name", name);
				
			break;
			case "css":
				jQuery("head").append("<link>");
				var tag = jQuery("head").children(":last");
				var attributes = {
					      rel:  "stylesheet",
					      type: "text/css",
					      href: url
				};
				
				if(name)
					attributes.name = name;
				
				//add onload function if exists
				if(typeof onload == "function"){
					
					attributes.onload = function(){
						
						onload(jQuery(this), replaceID);
					};
					
				}
				
				tag.attr(attributes);
			break;
			default:
				throw Error("Undefined include type: "+type);
			break;
		}
		
			
		//replace current element
		if(replaceID)
			tag.attr({id:replaceID});
		
		return(tag);
	};
	
	
	/**
	 * convert css array to string
	 */
	this.arrCssToStrCss = function(arrCss, selector, addBr){
		
		var strContent = "";
		jQuery.each(arrCss, function(key, value){
			if(key == "inline-css")
				strContent += value;
			else
				strContent += key+":"+value+";";
			
			if(addBr === true)
				strContent += "\n";
		});
		
		if(!strContent)
			return("");
		
		if(!selector)
			return(strContent);
		
		var strCss = selector += "{";
		
		if(addBr == true)
			strCss += "\n";
		
		strCss += strContent+"}";
		
		if(addBr === true)
			strCss += "\n";
		
		return(strCss);
	};
	
	
	/**
	 * wrap css in mobile
	 */
	this.wrapCssInMobile = function(css, isTablet){
		
		if(isTablet === "tablet")
			isTablet = true;			
		
		if(!css)
			return("");
				
		if(isTablet === true){
			var output = "@media (max-width:780px){"+css+"}";
		}else{
			var output = "@media (max-width:480px){"+css+"}";
		}
		
		return(output);
	};
	
	
	/**
	 * print custom css style
	 * generate id and replace old one
	 */
	this.printCssStyle = function(css, objID, objContainer){
		
		if(!objContainer)
			var objContainer = jQuery("head");
		
		var styleID = null;
		
		if(objID)
			styleID = "unite_style_"+objID;
		
		//remove old
		jQuery("#"+styleID).remove();
		
		//don't insert empty css
		if(!css)
			return(true);
		
		//generate new
		var html = "<style id='"+styleID+"' type='text/css'>\n";
		html += css+"\n";
		html += "</style>";
				
		//append new
		objContainer.append(html);
		
	};
	

	
	/**
	 * unselect some button / buttons
	 */
	this.enableButton = function(buttonID){
		jQuery(buttonID).removeClass("button-disabled");
	};
	
	/**
	 * unselect some button / buttons
	 */
	this.disableButton = function(buttonID){
		jQuery(buttonID).addClass("button-disabled");
	};
	
	/**
	 * return true / false if the button enabled
	 */
	this.isButtonEnabled = function(buttonID){
		if(jQuery(buttonID).hasClass("button-disabled"))
			return(false);
		
		return(true);
	};
	
	
	/**
	 * disable input
	 */
	this.disableInput = function(objInput){
		objInput.addClass("setting-disabled").prop("disabled","disabled");
	};
	
	/**
	 * enable input
	 */
	this.enableInput = function(objInput){
		objInput.removeClass("setting-disabled").prop("disabled","");
	};
	
	/**
	 * get input type (from jquery object)
	 */
	this.getInputType = function(objInput){
				
		if(objInput.is("input[type='text']"))
			return("text");

		if(objInput.is("textarea"))
			return("textarea");
		
		if(objInput.is("input[type='radio']"))
			return("radio");

		if(objInput.is("select"))
			return("select");

		if(objInput.is("input[type='checkbox']"))
			return("checkbox");

		if(objInput.is("input[type='button']"))
			return("button");
		
		//get type by data
		var inputType = objInput.data("inputtype");
		if(inputType)
			return(inputType);
		
		
		//output exception
		var inputName = objInput.prop("name");
		if(!inputName)
			inputName = objInput[0].tagname;
			
		trace(objInput);
		console.trace();
		
		throw new Error("Undefined input: " + inputName);
	}
	
	/**
	 * check if the input is simple input
	 */
	this.isSimpleInputType = function(inputType){
	
		switch(inputType){
			case "text":
			case "textarea":
			case "radio":
			case "select":
			case "checkbox":
				return(true);
			break;
		}
		
		return(false);
	}
	
	
	/**
	 * show or hide element
	 */
	this.showHideElement = function(objElement, isShow){
		if(isShow == true)
			objElement.show();
		else
			objElement.hide();
	}
	
	
	/**
	 * set cursor position on some element
	 */
	this.setCursorPosition = function(objElement, pos) {
		
		if(pos < 0)
			pos = 0;
		
		var el = objElement[0];
		
	    var range = document.createRange();
	    var sel = window.getSelection();
	    range.setStart(el.childNodes[0], pos);
	    range.collapse(true);
	    sel.removeAllRanges();
	    sel.addRange(range);
	    el.focus();
	}	
	
	
	this.__________MODIFY_CONTENT_____ = function(){};	
	
	
	/**
	 * replace all occurances
	 */
	this.replaceAll = function(text, from, to){
		
		return text.split(from).join(to);		
	};
	
	/**
	 * convert object to array
	 */
	this.objToArray = function(obj){
		if(typeof obj != "object")
			throw new Error("objToArray error: not object");
		
		var arr = [];
		jQuery.each(obj,function(key, item){
			arr.push(item);
		});
		
		return(arr);
	}
	
	
	/**
	 * turn string value ("true", "false") to string 
	 */
	this.strToBool = function(str){
		
		switch(typeof str){
			case "boolean":
				return(str);
			break;
			case "undefined":
				return(false);
			break;
			case "number":
				if(str == 0)
					return(false);
				else 
					return(true);
			break;
			case "string":
				str = str.toLowerCase();
						
				if(str == "true" || str == "1")
					return(true);
				else
					return(false);
				
			break;
		}
		
		return(false);
	};
	
	/**
	 * boolean to string
	 */
	this.boolToStr = function(str){
		if(typeof str == "string")
			return(str);
		
		str = (str == true)?"true":"false";
		
		return(str);
	};
	
	/**
	 * change rgb & rgba to hex
	 */
	this.rgb2hex = function(rgb) {
		if (rgb.search("rgb") == -1 || jQuery.trim(rgb) == '') return rgb; //ie6
		
		function hex(x) {
			return ("0" + parseInt(x).toString(16)).slice(-2);
		}
		
		if(rgb.indexOf('-moz') > -1){
			var temp = rgb.split(' ');
			delete temp[0];
			rgb = jQuery.trim(temp.join(' '));
		}
		
		if(rgb.split(')').length > 2){
			var hexReturn = '';
			var rgbArr = rgb.split(')');
			for(var i = 0; i < rgbArr.length - 1; i++){
				rgbArr[i] += ')';
				var temp = rgbArr[i].split(',');
				if(temp.length == 4){
					rgb = temp[0]+','+temp[1]+','+temp[2];
					rgb += ')';
				}else{
					rgb = rgbArr[i];
				}
				rgb = jQuery.trim(rgb);
				
				rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);
				
				hexReturn += "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3])+" ";
			}
			
			return hexReturn;
		}else{
			var temp = rgb.split(',');
			if(temp.length == 4){
				rgb = temp[0]+','+temp[1]+','+temp[2];
				rgb += ')';
			}
			
			rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);
			
			return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
		}
		
		
	};
	
	/**
	 * get rgb from hex values
	 */
	this.convertHexToRGB = function(hex) {
		var hex = parseInt(((hex.indexOf('#') > -1) ? hex.substring(1) : hex), 16);
		return [hex >> 16,(hex & 0x00FF00) >> 8,(hex & 0x0000FF)];
	};
	
	/**
	 * strip slashes to some string
	 */
	this.stripslashes = function(str) {
		return (str + '').replace(/\\(.?)/g, function (s, n1) {
			switch (n1) {
				case '\\':
				return '\\';
				case '0':
				return '\u0000';
				case '':
				return '';
				default:
				return n1;
			}
		});
	};
	
	/**
	 * strip html tags, allowed <br>,<i>
	 */
	this.stripTags = function(input, allowed) {
	    allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
	    var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
	        commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
	    return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
	        return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
	    });
	};
	
	/**
	 * strip all tags, keep formatting tags
	 */
	this.stripTagsKeepFormatting = function(input){
		
		return t.stripTags(input, "<b><br><i><em><strong><small><ins><sub><sup>");
	}
	
	/**
	 * turn object to escape html string
	 */
	this.objectToEscepeHtmlString = function(obj){
		
		if(typeof obj != "object")
			return(obj);
		
		var str = JSON.stringify(obj);
		str = t.htmlspecialchars(str);
		
		return(str);
	};
	
	
	/**
	 * escape html, turn html to a string
	 */
	this.htmlspecialchars = function(string){
		
		if(!string)
			return(string);
		
		  return string
		      .replace(/&/g, "&amp;")
		      .replace(/</g, "&lt;")
		      .replace(/>/g, "&gt;")
		      .replace(/"/g, "&quot;")
		      .replace(/'/g, "&#039;");
	};
	
	
	/**
	 * escape double slash
	 */
	this.escapeDoubleQuote = function(str){
		
		if(!str)
			return(str);
		
		return str.replace('"','\"');
	};
	
	
	/**
	 * capitalize first letter
	 */
	this.capitalizeFirstLetter = function(str){
		
		str = str.substr(0, 1).toUpperCase() + str.substr(1).toLowerCase();
		return(str);
	};
	
	
	/**
	 * get transparency value from 0 to 100
	 */
	this.getTransparencyFromRgba = function(rgba, inPercent){
		var temp = rgba.split(',');
		if(temp.length == 4){
			inPercent = (typeof inPercent !== 'undefined') ? inPercent : true;
			return (inPercent) ? temp[3].replace(/[^\d.]/g, "") : temp[3].replace(/[^\d.]/g, "") * 100;
		}
		
		return false;
	};
	
	/**
	 * add px or leave % if needed
	 */
	this.normalizeSizeValue = function(strValue){
		
		strValue = String(strValue);
		strValue.toLowerCase();
		
		if(jQuery.isNumeric(strValue))
			strValue += "px";
			
		return(strValue);		
	};
	
	
	/**
	 * remove line breaks and tabs
	 */
	this.removeLineBreaks = function(str, replaceSign){
		if(!replaceSign)
			var replaceSign = "";
		
		str.replace(/\s+/g, replaceSign); 
		
		return(str);
	};
	
	/**
	 * remove amp from string
	 */
	this.convertAmpSign = function(str){
		var str = str.replace(/&amp;/g, '&');		
		return(str);
	};
	
	
	
	/**
	 * filter object, leave child items by keys
	 */
	this.filterObjectByKeys = function(obj, arrKeys){
		
		if(typeof obj != "object")
			return(obj);
		
		if(jQuery.isArray(arrKeys) == false)
			throw new Error("filterObjectByKeys error - arrKeys should be array");
		
		var outputObj = {};
		
		jQuery.each(arrKeys, function(index, key){
			
			if(obj.hasOwnProperty(key))
				outputObj[key] = obj[key];
		});
		
		return(outputObj);
	};
	
	
	this.__________PATHS_AND_URLS_____ = function(){};	
	
	
	/**
	 * get base name from path
	 */
	this.pathinfo = function(path) {
		var obj = {};
		
		if(typeof path == "object"){
			trace(path);
			throw new Error("pathinfo error: path is object");
		}
		
		obj.basename = path.replace(/\\/g,'/').replace(/.*\//, '');
		obj.filename = obj.basename.substr(0,obj.basename.lastIndexOf('.'));
		
		return(obj);
	}
	
	
	/**
	 * strip path slashes from both sides
	 */
	this.stripPathSlashes = function(path){
		return path.replace(/^\/|\/$/g, '');		
	};
	
	
	/**
	 * convert to full url
	 */
	this.urlToFull = function(url, urlBase){
		
		if(!url)
			return(url);
		
		if(!urlBase)
			var urlBase = g_urlBaseSheetsPilot;
		
		//try to convert assets path from provider
		url = g_providerAdmin.urlAssetsToFull(url);
		 
		if(!url)
			return("");
		
		if(typeof url == "number")
			url = String(url);
		
		if(typeof url != "string"){
			trace(url);
			throw new Error("url should be a string type");
		}
		
		var urlSmall = url.toLowerCase();
		
		if(urlSmall.indexOf("http://") !== -1 || urlSmall.indexOf("https://") !== -1)
			return(url);
		
		if(url.indexOf(urlBase) !== -1)
			return(url);
		
		url = jQuery.trim(url);
		
		if(!url || url == "")
			return("");
		
		url = urlBase + url;
		return(url);
	}
	
	
	/**
	 * convert to relative url
	 */
	this.urlToRelative = function(url, urlBase){
		
		if(!urlBase)
			var urlBase = g_urlBaseSheetsPilot;
		
		url = url.replace(urlBase, "");
		return(url);
	};

	
	/**
	 * get url of some view
	 */
	this.getUrlView = function(view, options, isNoWindow){
		
		var urlBase = g_urlViewBaseUC;
		if(isNoWindow === true)
			urlBase = g_urlViewBaseNowindowUC;
		
		var url = t.addUrlParam(urlBase, "view", view);
		
		if(options && options != ""){
			
			//make url from object
			if(typeof(options) == "object"){
				jQuery.each(options, function(key, value){
					if(typeof value == "object"){
						value = JSON.stringify(value);
						value = t.encodeContent(value);
					}
					
					url += "&"+key+"="+value;
					
				});
			}
			else
				url += "&"+options;
		}
		
		return(url);
	};
	
	
	/**
	 * get current view url
	 */
	this.getUrlCurrentView = function(options){
		var url = g_urlViewBaseUC+"&view=" + g_view;
		
		if(options)
			url += "&"+options;
		
		return(url);
	};
	
		
	
	
	
	this.__________VALIDATION_FUNCTIONS_____ = function(){};	
	
	
	/**
	 * validate that object has some element name
	 */
	this.validateObjProperty = function(obj, propertyName, objName){
		
		if(typeof obj != "object")
				throw new Error("The object is empty (with property: " + elementName);
		
		if(typeof propertyName == "object"){
			
			jQuery(propertyName).each(function(index, pname){
				t.validateObjProperty(obj, pname, objName);
			});
			
			return(false);
		}
		
		if(obj.hasOwnProperty(propertyName) == false){
			trace(obj);
			
			if(!objName)
				objName = "";
			
			throw new Error("The "+objName+" object should has property: " + propertyName);
		}
		
	};
	
	
	/**
	 * validate that the dom object exists
	 * the obj has to be jquery object of don element
	 */
	this.validateDomElement = function(obj, objName){
		
		if(typeof obj != "object"){
			trace(obj);
			trace(typeof obj);
			console.trace();
			throw new Error("The object: "+objName+" not inited well");
		}
		
		if(obj.length == 0)
			throw new Error(objName+" not found!");
		
	};
	
	/**
	 * validate that field not empty
	 */
	this.validateNotEmpty = function(val, fieldName){
		if(typeof val == "undefined" || jQuery.trim(val) == "")
			throw new Error("Please fill <b>"+ fieldName + "</b> field");
	};
	
	/**
	 * validate that some value is object
	 */
	this.validateIsObject = function(val, fieldName){
		if(typeof val !== "object")
			throw new Error("The field must be object: "+fieldName);
	};
	
	
	/**
	 * validate name field
	 */
	this.validateNameField = function(val, fieldName){
		
		var errorMessage = "The field <b>"+ fieldName + "</b> allow only english lowercase letters, numbers and underscore. Example: first_name ";
		
		var regex = /^[a-z0-9_]+$/;
	    if(regex.test(val) == false)
			throw new Error(errorMessage);
	}
	
	
	
	
	this.__________AJAX_REQUEST_____ = function(){};
	
	/**
	 * Whether an error container exists and is visible (including ancestors).
	 *
	 * @param {jQuery} $container Candidate container.
	 * @return {boolean}
	 */
	function isUsableErrorMessageContainer($container) {
		if (!$container || !$container.length) {
			return false;
		}

		return $container.filter(':visible').length > 0;
	}

	/**
	 * Show AJAX error in the posts-editor modal when the sidebar panel is unavailable.
	 *
	 * @param {string} htmlError Error HTML/text.
	 * @return {boolean}
	 */
	function showErrorInAjaxDialog(htmlError) {
		var $dialog = jQuery('#ubai_ajax_error_dialog');
		if (!$dialog.length) {
			return false;
		}

		var $message = $dialog.find('.unlimitedai-plugin__popup-error-message');
		if ($message.length) {
			$message.html(htmlError);
		} else {
			$dialog.find('.unlimitedai-plugin__popup-body').html(htmlError);
		}

		$dialog.addClass('ue-active');
		return true;
	}

	function hideErrorAjaxDialog() {
		jQuery('#ubai_ajax_error_dialog').removeClass('ue-active');
	}

	function getErrorDialogPlainText() {
		var $message = jQuery('#ubai_ajax_error_dialog .unlimitedai-plugin__popup-error-message');
		if (!$message.length) {
			return '';
		}

		return jQuery.trim($message.text());
	}

	function copyErrorDialogText() {
		var text = getErrorDialogPlainText();
		if (!text) {
			return;
		}

		function onCopied() {
			if (typeof g_notification !== 'undefined' && g_notification) {
				var copiedLabel = (typeof sheetspilot !== 'undefined' && sheetspilot.editor && sheetspilot.editor.copy_to_clipboard)
					? sheetspilot.editor.copy_to_clipboard
					: 'Copied to clipboard';

				if (typeof g_notification.insertText === 'function') {
					g_notification.insertText(copiedLabel);
				}

				if (typeof g_notification.showNotification === 'function') {
					g_notification.showNotification();
				}
			}
		}

		if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
			navigator.clipboard.writeText(text).then(onCopied).catch(function () {
				copyTextWithFallback(text, onCopied);
			});
			return;
		}

		copyTextWithFallback(text, onCopied);
	}

	function copyTextWithFallback(text, onSuccess) {
		var textArea = document.createElement('textarea');
		textArea.value = text;
		textArea.style.position = 'fixed';
		textArea.style.left = '-9999px';
		textArea.style.top = '0';
		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			document.execCommand('copy');
			if (typeof onSuccess === 'function') {
				onSuccess();
			}
		} catch (err) {
			console.error('Copy failed', err);
		}

		document.body.removeChild(textArea);
	}

	/**
	 * Fallback error UI for pages without a visible general error container.
	 *
	 * @param {string} htmlError Error HTML/text.
	 * @return {boolean}
	 */
	function showErrorInFallbackDialog(htmlError) {
		if (showErrorInAjaxDialog(htmlError)) {
			return true;
		}

		if (typeof g_notification !== 'undefined' && g_notification && typeof g_notification.showErrorMessage === 'function') {
			g_notification.showErrorMessage(htmlError);
			return true;
		}

		return false;
	}

	/**
	 * show error message or call once custom handler function
	 */
	this.showErrorMessage = function(htmlError){

		function setErrorMessageHtml($container, html){
			if(!$container || !$container.length)
				return;

			var $text = $container.find(".unlimitedai-general-error-message__text");
			if($text.length)
				$text.html(html);
			else
				$container.html(html);
		}

		function tryShowInContainer($container) {
			if (!isUsableErrorMessageContainer($container)) {
				return false;
			}

			$container.show();
			setErrorMessageHtml($container, htmlError);
			return true;
		}

		var shown = false;

		if(g_errorMessageID !== null){
			switch(typeof g_errorMessageID){
				case "object":
					shown = tryShowInContainer(jQuery(g_errorMessageID));
				break;
				case "function":
					g_errorMessageID(htmlError);
					shown = true;
				break;
				default:
					shown = tryShowInContainer(jQuery("#"+g_errorMessageID));
				break;
			}
			
		} else {
			shown = tryShowInContainer(jQuery("#error_message"));
		}

		if (!shown) {
			showErrorInFallbackDialog(htmlError);
		}
		
		showAjaxButton();
	};

	/**
	 * hide error message
	 */
	function hideErrorMessage(){

		if(g_errorMessageID !== null){
			switch(typeof g_errorMessageID){
				case "object":
					var $messageContainer = jQuery(g_errorMessageID);
					$messageContainer.hide();
				break;
				case "string":
					var $messageContainerById = jQuery("#"+g_errorMessageID);
					$messageContainerById.hide();
				break;	
				case "function":
					if(typeof g_errorMessageHideFunc == "function")
						g_errorMessageHideFunc();
				break;
			}
			
			if(g_hideMessageCounter > 0){
				g_hideMessageCounter = 0;
				g_errorMessageID = null;
				g_errorMessageHideFunc = null;
			}else
				g_hideMessageCounter++;
		}else{
			jQuery("#error_message").hide();
		}
	};
	
	
	/**
	 * set error message id
	 */
	this.setErrorMessageID = function(id){
		g_errorMessageID = id;
		g_hideMessageCounter = 0;
	};
	
	
	/**
	 * set hide error func
	 */
	this.setErrorMessageOnHide = function(func){
		g_errorMessageHideFunc = func;
	}
	
	
	/**
	 * set success message id
	 */
	this.setSuccessMessageID = function(id){
		g_successMessageID = id;
	};
	
	/**
	 * show success message
	 */
	this.showSuccessMessage = function(htmlSuccess){
		
		var id = "#success_message";		
		var delay = 2000;
		if(g_successMessageID){
			id = "#"+g_successMessageID;
			delay = 500;
		}
		
        if (htmlSuccess !== 'Layout Updated'){
		jQuery(id).show().html(htmlSuccess);
        } else {
            var content ='<i class="fal fa-check-circle" aria-hidden="true" style="color: green;"></i><span>' + 'Saved Successfully' + '</span>';
            jQuery(id).show().html(content);
        }
		
		setTimeout(t.hideSuccessMessage,delay);
	};
	
	
	/**
	 * hide success message
	 */
	this.hideSuccessMessage = function(){
		
		if(g_successMessageID){
			jQuery("#"+g_successMessageID).hide();
			g_successMessageID = null;	//can be used only once.
		}
		else
			jQuery("#success_message").slideUp("slow").fadeOut("slow");
		
		showAjaxButton();
	};
	
	
	/**
	 * set ajax loader id that will be shown, and hidden on ajax request
	 * this loader will be shown only once, and then need to be sent again.
	 */
	this.setAjaxLoaderID = function(id){
		g_ajaxLoaderID = id;
	};

	this.setErrorStatusID = function(id){
		g_ajaxErrorID = id;
	};
	
	/**
	 * show loader on ajax actions
	 */
	var showAjaxLoader = function(){
		
		if(!g_ajaxLoaderID)
			return(false);
			
		if(typeof(g_ajaxLoaderID) == "function")
			g_ajaxLoaderID("show_loader");
		else
			jQuery("#"+g_ajaxLoaderID).show();
		
	};

	/**
	 * show error status
	 */
	var showErrorStatus = function(){
		
		if(!g_ajaxErrorID)
			return(false);
			
		
		jQuery("#"+g_ajaxErrorID).fadeIn();
		setTimeout(function(){
			jQuery("#"+g_ajaxErrorID).fadeOut();
		}, 2000)
	};
	
	
	/**
	 * hide and remove ajax loader. next time has to be set again before "ajaxRequest" function.
	 */
	var hideAjaxLoader = function(){
		
		if(!g_ajaxLoaderID)
			return(false);
			
		if(typeof g_ajaxLoaderID == "function"){
			
			g_ajaxLoaderID("hide_loader");
			
		}else{
			jQuery("#"+g_ajaxLoaderID).hide();
			g_ajaxLoaderID = null;
		}
			
	};
	
	/**
	 * set button to hide / show on ajax operations.
	 */
	this.setAjaxHideButtonID = function(buttonID){
		g_ajaxHideButtonID = buttonID;
	};
	
	/**
	 * if exist ajax button to hide, hide it.
	 */
	function hideAjaxButton(){
		
		if(!g_ajaxHideButtonID)
			return(false);

		if(typeof g_ajaxHideButtonID == "function"){
			g_ajaxHideButtonID("hide_button");
		}else{
			jQuery("#"+g_ajaxHideButtonID).hide();
		}
		
	};
	
	/**
	 * if exist ajax button, show it, and remove the button id.
	 */
	function showAjaxButton(){
		
		if(!g_ajaxHideButtonID)
			return(false);
		
		if(typeof g_ajaxHideButtonID == "function"){
			g_ajaxHideButtonID("show_button");
		}else{
			jQuery("#"+g_ajaxHideButtonID).show();
			g_ajaxHideButtonID = null;			
		}
		
		
	};

	
	/**
	 * add url param
	 */
	this.addUrlParam = function(url, param, value){
		
		if(url.indexOf("?") == -1)
			url += "?";
		else
			url += "&";
		
		if(typeof value == "undefined")
			url += param;
		else	
			url += param + "=" + value;
		
		return(url);
	}
	
	
	/**
	 * get ajax url with action and params
	 */
	this.getUrlAjax = function(action, params){
		
		var url = g_urlAjaxActionsSheetsPilot;
		
		url = t.addUrlParam(url, "action", "sheetspilot_ajax_actions");
		
		if(typeof g_doublyNonce == "string")
			url = t.addUrlParam(url, "nonce", g_doublyNonce);
		
		if(action)
			url = t.addUrlParam(url, "client_action", action);
		
		if(params)
			url = t.addUrlParam(url, params);

		if (typeof g_showdebug !== 'undefined' && g_showdebug) {
			url = t.addUrlParam(url, 'showdebug', 'true');
		}
		
		return(url);
	}
	
	/**
	 * add form files to data
	 */
	this.addFormFilesToData = function(formID, objData){

		var objForm = jQuery("#"+formID);
		if(objForm.length == 0)
			throw new Error("form with ID: "+ formID + " not found");
		
    	var objFiles = objForm.find("input[type='file']");
    	if(objFiles.length == 0)
			throw new Error("no file inputs found in form: " + formID);
    	
    	jQuery.each(objFiles, function(index, objFile){
    		var fieldName = objFile.name;
    		
    		jQuery.each(objFile.files, function(index2, file){
    			objData.append(fieldName, file);
    		});
    	});
		
	}
	
	/**
	 * True when the browser cancelled the request (e.g. user navigated away).
	 */
	function isAjaxRequestInterrupted(jqXHR, textStatus) {
		if (textStatus === 'abort') {
			return true;
		}

		if (!jqXHR) {
			return false;
		}

		var status = jqXHR.status;
		if (status !== 0 && status !== '0') {
			return false;
		}

		var responseText = jqXHR.responseText;
		return !responseText || responseText === '0';
	}

	/**
	 * Whether PHP stack traces are enabled for SheetsPilot AJAX.
	 */
	function isAjaxTraceEnabled(){
		if(typeof g_showtrace !== "undefined" && g_showtrace)
			return true;

		if(typeof sheetspilot !== "undefined" && sheetspilot.editor && sheetspilot.editor.g_showtrace)
			return true;

		return false;
	}

	/**
	 * check ajax return
	 */
	this.ajaxReturnCheck = function(response, successFunction){

		

		//cell orienterd save
		if( response && response.message && response.message.cell_address !== undefined ){
			if( !response.message.save_result ){
				t.showCellErrorMessage( response.message.cell_address, response.message.message ); 
			}
		}

		if(!response){
			t.showErrorMessage("Empty ajax response!");
			return(false);					
		}
		
		if(typeof response != "object"){
			
			try{
				response = jQuery.parseJSON(response);
			}catch(e){
				t.showErrorMessage("Ajax Error!!! not ajax response");
				t.debug(response);
				return(false);
			}
		}
		
		if(response == -1){
			t.showErrorMessage("ajax error!!!");
			return(false);
		}
		
		if(response == 0){
			t.showErrorMessage("ajax error, action: <b>"+action+"</b> not found");
			return(false);
		}
		
		if(response.success == undefined){
			t.showErrorMessage("The 'success' param is a must!");
			return(false);
		}
		
		if(response.success == false){
			t.showErrorMessage(response.message);
			return(false);
		}
		
		//run a success event function
		if(typeof successFunction == "function"){
			
			//show success message only if custom id exists
			if(response.message && g_successMessageID)
				t.showSuccessMessage(response.message);
			
			successFunction(response);
		}
		else{
			if(response.message)
				t.showSuccessMessage(response.message);
		}
		
		if(response.is_redirect)
			location.href=response.redirect_url;
		
		
	}
	
	/**
	 * Function to apply error message based on cell
	 * */
	this.showCellErrorMessage = function( cellClass, cellMessage){
		var $errorMessage = jQuery('<div>', {
			class: 'incell_error_message',
			text: cellMessage		
		});
		jQuery(cellClass).append($errorMessage);
		setInterval(function(){
			 
			$errorMessage.fadeOut(function(){
				$errorMessage.replaceWith('');
			})
		}, 2000)
	}


	
	/**
	 * Ajax request function. call wp ajax, if error - print error message.
	 * if success, call "success function" 
	 */
	this.ajaxRequest = function(action,data,successFunction,completeFunction){
		
		if(typeof data == "undefined")
			var data = {};
		
		//raw mode - for including file uploads
		var isRawMode = false;
		if(typeof data.append == "function"){
			isRawMode = true;
			var objData = data;
			objData.append("action", "sheetspilot_ajax_actions");
			objData.append("client_action", action);
			if(typeof g_doublyNonce == "string")
				objData.append("nonce", g_doublyNonce);
		}else{
			
			//simple mode
			var objData = {
					action:"sheetspilot_ajax_actions",
					client_action:action,
					data:data
				};
			if(typeof g_doublyNonce == "string")
				objData.nonce = g_doublyNonce;
		}

		if (typeof g_showdebug !== 'undefined' && g_showdebug) {
			if (isRawMode) {
				objData.append('showdebug', 'true');
			} else {
				objData.showdebug = true;
			}
		}

		if (isAjaxTraceEnabled()) {
			if (isRawMode) {
				objData.append('showtrace', 'true');
			} else {
				objData.showtrace = true;
			}
		}
		
		
		hideErrorMessage();
		showAjaxLoader();
		hideAjaxButton();
 
		var ajaxOptions = {
				type:"post",
				url:g_urlAjaxActionsSheetsPilot,
				dataType: 'json',
				data:objData,
				success:function(response){
					hideAjaxLoader();					
					t.ajaxReturnCheck(response, successFunction);					
				},
				error:function(jqXHR, textStatus, errorThrown){

					hideAjaxLoader();

					if (isAjaxRequestInterrupted(jqXHR, textStatus)) {
						showAjaxButton();
						return;
					}

					showErrorStatus();
					
					switch(textStatus){
						case "parsererror":
						case "error":
							t.debug(jqXHR.responseText);
						break;
					}
					
					t.showErrorMessage("Ajax Error!!! " + textStatus);
				},
				complete:function(jqXHR, textStatus){
					if(typeof completeFunction !== "function"){
						return;
					}

					var response = null;
					if(jqXHR && jqXHR.responseJSON){
						response = jqXHR.responseJSON;
					}else if(jqXHR && jqXHR.responseText){
						try{
							response = jQuery.parseJSON(jqXHR.responseText);
						}catch(e){
							response = null;
						}
					}

					completeFunction(response, textStatus);
				}
		}
		
		//add some options for raw mode
		if(isRawMode == true){
			ajaxOptions.global = false;
			ajaxOptions.processData = false;
			ajaxOptions.contentType = false;
		}
		
		jQuery.ajax(ajaxOptions);
		
	};//ajaxrequest

	
	
	
	this.z_________TIMER_FUNCTIONS_______ = function(){}

	/**
	 * start timer
	 */
	this.startTimer = function(){
		
		g_temp.timer = jQuery.now();
		
	};
	
	/**
	 * print timer
	 */
	this.printTimer = function(){
		
		var currentTime = jQuery.now();
		if(!g_temp.timer){
			trace("timer not started!");
			return(false);
		}
		
		var diff = currentTime - g_temp.timer;
		trace("time passed: "+diff);
		
	};
	
	/**
	 * print time stamp
	 */
	this.printTimeStamp = function(stamp){
		
		if(!stamp)
			var stamp = jQuery.now();
		
		var date1 = new Date(stamp);
		trace(date1);
	}
	
	this.z_________DATA_FUNCTIONS_______ = function(){};
	
	/**
	 * set data value
	 */
	this.storeGlobalData = function(key, value){
		key = "unite_data_"+key;
		jQuery.data(document.body, key, value);
	};

	/**
	 * set data value
	 */
	this.consoleLog = function( value, marker = false ){
		if (sheetspilot.editor.g_isLogOn == 1) {
			console.log( '---CONSOLELOG---- ' + marker );
			console.log( value );
		}
	};
	
	
	/**
	 * get global data
	 */
	this.getGlobalData = function(key){
		key = "unite_data_"+key;
		var value = jQuery.data(document.body, key);
		
		return(value);
	};
	
	/**
	 * clear provider setting
	 */
	this.clearProviderSetting = function(type, objInput, dataname){
		
		if(typeof g_providerAdmin.clearSetting != "function")
			return(false);
		
		var response = g_providerAdmin.clearSetting(type, objInput, dataname);
		
		return(response);
	}
	
	/**
	 * set value of provider setting
	 */
	this.providerSettingSetValue = function(type, objInput, value){
		
		if(typeof g_providerAdmin.setSettingValue != "function")
			return(false);
		
		var response = g_providerAdmin.setSettingValue(type, objInput, value);
		
		return(response);
		
	};
	
	
	/**
	 * init provider settings
	 */
	this.initProviderSettingEvents = function(type, objInput){
				
		if(typeof g_providerAdmin.initSettingEvents != "function")
			return(true);
		
		g_providerAdmin.initSettingEvents(type, objInput);
		
		
	};
	
		
	
	this.__________GLOBAL_INIT_____ = function(){};

	/**
	 * init error message
	 */
	this.initMessagesBehaviour = function(){

		/**
		 * Sync sidebar chrome when expandable panels (.uc-error-message-expanded) need to overlap the table.
		 */
		function syncSidebarExpandedForPanels($sidebar) {

			if (!$sidebar || !$sidebar.length) {
				return;
			}

			var anyExpanded = $sidebar.find(".unlimitedai-plugin__panel.uc-error-message-expanded").length > 0;

			$sidebar.toggleClass("unlimitedai-plugin__sidebar--error-expanded", anyExpanded);
		}

		jQuery(document).on("click", ".unlimitedai-plugin__panel-control--expand", function(event){
			event.preventDefault();

			var $panel = jQuery(this).closest(".unlimitedai-plugin__panel");
			if(!$panel.length)
				return;

			$panel.toggleClass("uc-error-message-expanded");

			syncSidebarExpandedForPanels($panel.closest(".unlimitedai-plugin__sidebar"));
		});

		jQuery(document).on("click", ".unlimitedai-plugin__sidebar-debug__header", function(event){
			event.preventDefault();

			var $panel = jQuery(this).closest(".unlimitedai-plugin__panel");
			if(!$panel.length)
				return;

			$panel.toggleClass("uc-prompt-expanded");
		});

		jQuery(document).on("click", ".unlimitedai-plugin__panel-control--close", function(event){
			event.preventDefault();

			var $panel = jQuery(this).closest(".unlimitedai-plugin__panel");
			if(!$panel.length)
				return;

			$panel.hide().removeClass("uc-error-message-expanded");

			syncSidebarExpandedForPanels($panel.closest(".unlimitedai-plugin__sidebar"));
		});

		jQuery(document).on("click", "#ubai_ajax_error_dialog .ubai-ajax-error-dialog__close, #ubai_ajax_error_dialog .ubai-ajax-error-dialog__ok", function(event){
			event.preventDefault();
			hideErrorAjaxDialog();
		});

		jQuery(document).on("click", "#ubai_ajax_error_dialog .ubai-ajax-error-dialog__copy", function(event){
			event.preventDefault();
			event.stopPropagation();
			copyErrorDialogText();
		});

		jQuery(document).on("click", "#ubai_ajax_error_dialog.unlimitedai-plugin__popup", function(event){
			if (event.target === this) {
				hideErrorAjaxDialog();
			}
		});
	};
	
	
	
	/**
	 * global init
	 */
	this.globalInit = function(){
		
		t.initMessagesBehaviour();
		
		g_providerAdmin.setParent(t);
		g_providerAdmin.init();
		
		if(typeof g_ugMediaDialog != "undefined")
			g_ugMediaDialog.init();
				
		//initVersionDialog();
		//handleCheckCatalog();
	};
	
	
}

if(!g_doublyAdmin)
	var g_doublyAdmin;


//user functions:
function trace(data,clear){
	
	if(!g_doublyAdmin)
		g_doublyAdmin = new UniteAdminSheetsPilot();
	
	g_doublyAdmin.trace(data,clear);
}

function clearTrace(){
	
	console.clear();
}

function debug(data){
	
	if(!g_doublyAdmin)
		g_doublyAdmin = new UniteAdminSheetsPilot();
	
	g_doublyAdmin.debug(data);
}

/**
 * debug line by line
 */
function debugLine(data){
	
	data += "   "+Math.random();
	
	var html = jQuery("#div_debug").html();
	html += "<br>";
	html += data;
	
	jQuery("#div_debug").show().html(html);
	
}


/**
 * Init CodeMirror on any textarea.ubai-codemirror in the document (for panels that don't use settings init).
 */
function ubaiInitCodeMirrorEditors() {
	
	if (typeof window.CodeMirror === "undefined") {
		return;
	}

	jQuery("textarea.ubai-codemirror").each(function () {
		var ta = this;
		if (ta.getAttribute("data-cm-inited") === "1") {
			return;
		}

		var mode = jQuery(ta).data("codemirror-mode") || "text/plain";
		var opts = {
			mode: mode,
			lineNumbers: true,
			lineWrapping: true
		};

		var cm = window.CodeMirror.fromTextArea(ta, opts);
		ta.setAttribute("data-cm-inited", "1");

		cm.on("change", function () {
			ta.value = cm.getValue();
			jQuery(ta).trigger("change");
		});
	});
}

//run the init function
jQuery(document).ready(function(){

	if(!g_doublyAdmin)
		g_doublyAdmin = new UniteAdminSheetsPilot();

	g_doublyAdmin.globalInit();

	ubaiInitCodeMirrorEditors();
});

