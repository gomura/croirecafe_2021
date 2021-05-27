var w = $(window).width();
var is_mobile = w < 768;
var global = this;
var body = $("body");

$.ajaxSetup({
	cache: false
});


if(!is_mobile){
	$("meta[name='viewport']").attr('content', 'width=1400,user-scalable=yes');
}

/* !! アンドロイドのバージョン関数を真偽で返す */
var ua = navigator.userAgent;
function lowerAndroid(n) {
	var bo = false;
	var ua = navigator.userAgent.toLowerCase();
	var version = ua.substr(ua.indexOf('android')+8, 3);
	if(ua.indexOf("android")) if(parseFloat(version) < n) bo = true;
	return bo;
}

function getUrlVars(){ // URLに付与した文字列から変数を取得する。
		var vars = [], hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for(var i = 0; i <hashes.length; i++)
	{
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
}

$.easing.slowdown = function(x,t,b,c,d){
    return -c * ((t=t/d-1)*t*t*t - 1) + b;
}

$(function(){
	var url = location.href ;
	if(url.match("test")){
		$("html").addClass("test");
	} 
});

/* !!------------------------------------ */
/* !! スクロールイベント */

$(function(){
	
	$(window).scroll(function () {
	  	
	  	var scroll = $(this).scrollTop(); 
	  	var winH = $(window).innerHeight();
	  	
	  	if( scroll > 0 ){ 
		  	$("body").addClass('moved'); 
	  	}
	  	
	  	else{ 
		  	$("body").removeClass("moved");
	  	}
	  	
	  	if(scroll >= winH){
		  	$("body").addClass("fv_passed")
	  	}else{
		  	$("body").removeClass("fv_passed")
	  	}
	  	
/*
		(function(){

		  	var footH = $("#footer").offset().top;
		  	if( scroll > footH - winH ){
			  	$("body").addClass("foot_inview");
		  	}else{
			  	$("body").removeClass("foot_inview");
		  	}
		  	
	  	}());
*/
	  	
	});

});
	

//!!  一覧のliを埋める
$(function(){
	
	var fill = $("*[data-column]");
	
	if(fill[0] && !is_mobile){
		fill.each(function(){
			var This = $(this);
			var column = This.data("column");
			var len = This.data("column") - This.find(" > li").length % column;
			if(len == column){
				return;
			}
			for (var p = 0; p < len; p += 1) {
				This.append('<li class="nh"></li>');
			}//for
			
		});
	}
	
	
});



/* !! CSStransitionの終了をイベントを検知する。 */
var listenEvents = [
    'webkitTransitionEnd', // webkit(Chrome1.0, Safari3.2)
    'oTransitionEnd',      // Opera10.5
    'otransitionend',      // Opera12
    'transitionend'        // IE10, Fx4以降, 12.10以降
];

var transitionEnd = listenEvents.join(' '); //.on(transitionEnd,func..)で使用。


//!! transformの値を返す 
function transform3d_value(e){
 if(e == "none"){
	 return false;
 }
  var values = e.split('(')[1];
  values = values.split(')')[0];
  values = values.split(', ');
  var matrix = {'scale-x':values[0],'rotate-z-p':values[1],'rotate-y-p':values[2],'perspective1':values[3],'rotate-z-m':values[4],'scale-y':values[5],'rotate-x-p':values[6],'perspective2':values[7],'rotate-y-m':values[8],'rotate-x-m':values[9],'scale-z':values[10],'perspective3':values[11],'translate-x':values[12],'translate-y':values[13],'translate-z':values[14],'perspective4':values[15]};
  return matrix;
}

/*
$(function(){
	var myregex = new RegExp("$store.al-jpan.com/");
	if(location.href.match(myregex)){
		console.log(true);
	}
});
*/



//!!　外部からのリンク対策

$(function(event){
	//this.event.preventDefault();
	var str = location.href
	var str = str.split("#");
	var margin = !is_mobile ? 50 : 50 ;
	
	if(str[1]){
		//$("#main").addClass("hide");
		$(window).on("load",function() {
			//alert(margin)
			//$("#main").removeClass("hide");
			var pos = $("#"+str[1]).offset().top - margin;
			$("html, body").animate({scrollTop:pos},1000,"slowdown",function(){
/*
				$("#shade").fadeOut(1000,function(){
					$("#shade").remove();
				});
*/
			});
		});	
	}
});


	


/* !!------------------------------------ */
/* !! inview */

$(function(){
	if(!is_mobile){
		$(".inv").attr("data-offset",-50);
	}else{
		$(".inv").attr("data-offset",-100);
	}
	
	$("body").on("inview",".inv",function(){
		

	//	$(".inv").on("inview",function(){
		var This = $(this);
		if(!is_mobile){ //PC
			//This.attr('src',This.data("pc"));
			//This.one("load",function(){
				This.removeClass("inv");
				//alert("c")
			//})
		}
		else{ //MOBILE
			
			//This.attr('src',This.data("sp"));
			//This.one("load",function(){
				This.removeClass("inv");
			//})
		}
		
	});
});


/* !!------------------------------------ */
/* !! lazylaod */

/*
$(function(){
	if($("img[data-original]").length < 1){
		return;
	}
	$("img[data-original]").lazyload({
	    effect : 'fadeIn',
	    effectspeed : 500,
	    //threshold: 200,
	    skip_invisible: true,
	    load: function(e){
			//callback
			//var This = $(this);
			//var dataSrc = This.data("original");
			//var srcSet = dataSrc.replace(/(.[a-z]+)$/, "@2x$1 2x");
			//This.attr("srcset", srcSet);
			$(this).addClass("on");
		}
	});
	
});
*/


/* !!------------------------------------ */
/* !! 高さを揃える */
function normalizeHeight(elem,mobile){
	if(is_mobile && !mobile ) return;
	var h = 0 ;
	elem.each(function(){
		var This = $(this);
		elemH = This.height();
		if(elemH >= h){
			h = elemH;
		}
	});
	elem.css("height",h);
}

$(function(){
	normalizeHeight($(".rel1"));
});


/* !!------------------------------------ */
/* !! 検索窓 */
$(function(){
	var category_id = $("select.category_id");
	if(category_id[0]){
		category_id.find("option").each(function(){
			var This = $(this);
			var ThisVal = This.val();
			switch(ThisVal){
				case "10":
				case "9":
				case "7":
				case "8":
				case "6":
				case "13":
				case "14":
					This.remove();
					break;
				default:
					break;
			}
		})
	}
});



/* !!------------------------------------ */
/* !! トップページ */

$(function(){
	$(".item ._btn").on("click",function(){
		var This = $(this),
			selection = This.closest(".inner").find(".selection");
		
		if(This.is("a")) return;
		$(".selection").removeClass("on");
		selection.addClass("on");
		
	});
	
	$(".selection").on("click",function(){
		$(this).removeClass("on");
	})
	
});




/* !!------------------------------------ */
/* !! クロワール一覧リスト生成 */




function initSlide(){
	
	$(".slide.croire").slick({
        dots: true,
        arrows: true,
        autoplay: false,
        speed: 300,
        slidesToShow:1,
	});
	
}

var generateList =( function generateList(data) {

	var wrap = $("#products-list-ul");
	var html = "";
	for (var p = 0; p < data.length; p += 1) {

		var item = data[p];
		
		html += '<li><a href="'+item.url.teiki+'"></a><div class="inner">';
		html += '<div class="img bg" data-pcode="'+item.pcode+'"><img src="'+item.thumb_url+'" /></div>';
		html += '<div class="txt">';
		html += '<h3 class="pname"><span>'+item.name_disp+'</span></h3>';
		html += '<p class="desc">'+item.desc_long+'</p>';
		html += '</div>';
		html += '</div></li>';
		
	}//for
	
	wrap.append(html);
	
});

$(function(){
	var wrap = $("#products-list-ul");
	if(wrap[0]){
		$.ajax({
		    url: "/product/products.json",
		}).then(generateList).then(function(){
			
		}).then(function(){
			if(is_mobile){
				$(".cell .img").each(function(){
					var This = $(this);
					This.find("img").on("load",function(){
						//setHeightTittle(This);
					})
				    
				});
			}
		});
		
	}
});









/* !!------------------------------------ */
/* !! フッターの読み込み */
$(window).on('load',function() {
//$(function(){
	$(".ec-layoutRole__footer").before('<section id="page-footer"></section>');
	$("#page-footer").load("/common/html/page-footer.html");
});


/* !!------------------------------------ */
/* !! FAQ */

function toggleFAQ(elem){
	elem.toggleClass("opened");
	elem.next("dd").slideToggle(400,"slowdown");
}

$(function(){
	$("body").on("click","main.FAQ dl dt",function(){
		toggleFAQ($(this));
	});
});






/* !!------------------------------------ */
/* !! キーアップ */
$(window).keyup(function(e){
  //$("div").text(e.keyCode);
  var test = location.href.match('localhost|:88')
  if(e.keyCode == "219" && test){
	  $("body").toggleClass("showBlk");
  }else{
	  return false;
  }
});



/* !!------------------------------------ */
/* !! 検索結果 */

$(function(){
	var count	= $(".ec-searchnavRole__counter");
	var item 	= $(".ec-shelfGrid__item");
	
	if(count[0]){
		count.css({visibility:'hidden'});
		
		item.each(function(){
			var This = $(this);
			var html = This.html();
			var keyword = []
			if(html.match("プレゼント|送料無料")){
				This.remove();
			}
		});
	}
	
	
});





/* !!------------------------------------ */
/* !! 商品詳細ページ */

//!! iframeの追加

$(function(){
	if($("a#frame")[0]){
		var src = $("a#frame").attr("href");
		//$("#page-footer").before('<iframe src="'+src+'" frameborder="0" class="detail"></iframe>');
		//$("#page-footer").before('<div id="loadedContent"></div>');
		$(".ec-layoutRole__contents").after('<div id="loadedContent"></div>');
		$("#loadedContent").load(src+" #content-detail",function(){
			$("#page-footer").load("/common/html/page-footer.html");
		});
	}
});

//!! PB12
$(function(){
	if($("#PB12_1")[0]){
		$("body").addClass("PB12_1");
	}
});

$(function(){
	if($("#PB12_2")[0]){
		$("body").addClass("PB12_2");
	}
});









$(function(){
	var pid = $(".ec-productRole").data("pid");
	if(pid == "124" || pid == "36" || pid == "29"){
		//displayCoupon(pid);
	}else{
		return false;
	}
});


//!! 規格の選択
function selectClassCate(str){
	var $option = $('select.form-control option');
	$option.each(function(){
		var This = $(this);
		var This_str = This.text();
		if(str == This_str){
			This.parent().val(This.val()).trigger('change');
			This.parent().trigger('change');
			var txt = $(".price02_default.teiki").text();
			txt = txt.replace(/¥ /, "");
			$(".price02_default.teiki").text(txt);
			This.parent().hide(0);
		}else{
			return;
		}
	})
}



$(window).on("load",function() {
//$(function(){
	if($("#detail_cart_box__cart_class_category_id[data-type=定期購入]")[0]||$("#detail_cart_box__cart_class_category_id[data-type=定期購入商品]")[0]){
		selectClassCate("初回割引")
	}
});







/* !!------------------------------------ */
/* !! 会員登録スクリプト */
/*
$(window).on("load",function() {
	
	var txt = $("#admin_customer_note").text();
	return;
	if(txt){
		var array = txt.split("　"),
			array = array[1].split("／"),
			point = array[0];
		
		$("#admin_customer_plg_point_current").val(point);
		if(txt.match("受信する")){
			$("#admin_customer_mailmaga_flg_0").click();
		}else{
			$("#admin_customer_mailmaga_flg_1").click();
		}
		$("#admin_customer_plg_point_current").trigger("focus");
		//$("#button_box__insert_button button").click();
	}
	
});
*/

/* !!------------------------------------ */
/* !! 入力フォーム */

$(function(){
	
	var input_email = $('input[id*="_email_"]');
	var input_zip = $('input[id$="_postal_code"]');
	var input_tel = $('input[id$="_phone_number"]');
	var input_card = $('input[id*="_order_card_no"],input[id*="_security_code"]')
	input_email.each(function(){
		var This = $(this);
		This.attr('inputmode','email');
	});
	input_zip.each(function(){
		var This = $(this);
		This.attr('inputmode','numeric');
	});
	input_tel.each(function(){
		var This = $(this);
		This.attr('inputmode','numeric');
	});
	
	input_card.each(function(){
		var This = $(this);
		This.attr('inputmode','numeric');
	});

	
});


//!! ひらがな→カタカナ変換

$(function(){
	var input_kana = $('input[id*="_kana_kana"]');
	input_kana.on('blur',function(){
		var This = $(this);
		var TargetString = This.val();
		TargetString = TargetString.replace(/[ぁ-ん]/g, function(s) {
		   return String.fromCharCode(s.charCodeAt(0) + 0x60);
		});
		//alert(TargetString)
		This.val(TargetString);
	})
});


/*
TargetString = TargetString.replace(/[ぁ-ん]/g, function(s) {
   return String.fromCharCode(s.charCodeAt(0) + 0x60);
});
*/


/* !!------------------------------------ */
/* !! マガジンの読み込み */

function loadBlogData(rssUrl){
	
	$.ajax({
		url:'/common/lib/get.php?url='+rssUrl,
		type: 'GET',
		dataType: 'html',
		cache: false,
	    success: function(data){

			var htmlstr = "";
			//アイテムの調整
			var blog = $(data);
			var count = 0;		
			blog.find("article").each(function (i) {
				count ++ ;
				if( count > 3 ){
					return false;
				}
				if( count < 0 ) { // 表示件数の設定
					return ;
				}
				
				var This = $(this),
					plink = This.find("a").attr("href"),
					img = This.find(".post-thumbnail figure").html(),
					ttl = This.find(".post-title").text(),
					date = This.find(".post-date").text(),
					cat = This.find(".label a").text();
				
				htmlstr +='<li>'
						+'<a href="'+plink+'" target="_blank"></a>'
						+'<div class="img">'+img+'</div>'
						+'<div class="ttl">'+ttl+'</div>'
						+'<div class="date">'+date+'</div>'
						+'</li>'
				//htmlstr += '<a href="' + el.find("link").text() + '" title="' + el.find("title").text() + '" target="_blank">' + el.find("title").text() + ' - ' + el.find("category").text() + '</a>';
				//htmlstr += '</li>';
				
			});
			
			
			//挿入する
			$("#magazine ul.magazine").html(htmlstr);
	     }
	});
}

$(function(){
	if($("#magazine")[0]){
		//$("#magazine").one("inview",function(){
			loadBlogData('https://blog.al-japan.com/');
		//})
	} 
});
