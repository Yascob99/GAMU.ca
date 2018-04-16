  var isMobile = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
  }
function changeCSS(cssFile, cssLinkIndex) {

	var oldlink = document.getElementsByTagName("link").item(cssLinkIndex);

	var newlink = document.createElement("link");
	newlink.setAttribute("rel", "stylesheet");
	newlink.setAttribute("type", "text/css");
	newlink.setAttribute("href", cssFile);

	document.getElementsByTagName("head").item(0).replaceChild(newlink, oldlink);
}
function toggleCSS(css1, css2, cssLinkIndex){
  var oldlink = document.getElementsByTagName("link").item(cssLinkIndex);
  console.log(oldlink.href + "  http://" + window.location.hostname + css2)
  if (oldlink.href == "http://" + window.location.hostname + css2){
    var newlink = document.createElement("link");
  	newlink.setAttribute("rel", "stylesheet");
  	newlink.setAttribute("type", "text/css");
  	newlink.setAttribute("href", css1);
  }
  else{
    var newlink = document.createElement("link");
  	newlink.setAttribute("rel", "stylesheet");
  	newlink.setAttribute("type", "text/css");
  	newlink.setAttribute("href", css2);
  }

	document.getElementsByTagName("head").item(0).replaceChild(newlink, oldlink);
}

function getContent(element) {
    return document.getElementById(element).innerHTML;
}

function getStyle(obj) {
    var elt = document.getElementById(obj);
    var out = "";
    var st = elt.style;
    var cs = window.getComputedStyle(elt, null);
    for (var x in st) {
        out += "  " + x + " = '" + st[x] + "' > '" + cs[x];
    }
    return out;
}
function toRGB(c) {
    return parseInt(c, 16);
}

function componentToHex(c) {
    var hex = c.toString(16);
    return hex.length == 1 ? "0" + hex : hex;
}

function rgbToHex(r, g, b) {
    return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
}

function add_style(style, id){
	var css = style,
		head = document.head || document.getElementsByTagName('head')[0],
		style = document.createElement('style');

	style.id = id
	style.type = 'text/css';
	if (style.styleSheet){
	  style.styleSheet.cssText = css;
	} else {
	  style.appendChild(document.createTextNode(css));
	}
	head.appendChild(style);
}
function changeCSS(cssFile, cssLinkIndex) {

        var oldlink = document.getElementsByTagName("link").item(cssLinkIndex);

        var newlink = document.createElement("link");
        newlink.setAttribute("rel", "stylesheet");
        newlink.setAttribute("type", "text/css");
        newlink.setAttribute("href", cssFile);

        document.getElementsByTagName("head").item(0).replaceChild(newlink, oldlink);
      }
function getContent(element) {
    return document.getElementById(element).innerHTML;
}

function getStyle(obj) {
    var elt = document.getElementById(obj);
    var out = "";
    var st = elt.style;
    var cs = window.getComputedStyle(elt, null);
    for (var x in st) {
        out += "  " + x + " = '" + st[x] + "' > '" + cs[x];
    }
    return out;
}

function supportsSVG(){
	return (bowser.chrome && bowser.version >= 31) || (bowser.firefox && bowser.version >= 38) || (bowser.msedge && bowser.version >= 12) || (bowser.safari && bowser.version >= 8) || (bowser.opera && bowser.version >= 31) || (bowser.msie && bowser.version >= 9) || (bowser.ios && bowser.version >= 7.1) || (bowser.android && bowser.version >= 4.1);
}
function checkMobile(){
	if (isMobile.any()){
		changeCSS("/mobile.css", 0)
	}
	return false;
}
if (!supportsSVG()){
  add_style("html { background-image: url('/images/pattern2.png') !important; }")
}
