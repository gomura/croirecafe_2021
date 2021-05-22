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


/* !!------------------------------------ */
/* !! クロワール一覧スライド */

function setHeightTittle(elem){
	var h = elem.height();
	var tgt = elem.next(".ttl");
	tgt.height(h);
}



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
	
	var wrap = $("#croire-list");
	var html = "";
	var today = wrap.data("today");
	var range = wrap.data("range");
	
	for (var p = 0; p < data.length; p += 1) {

		var item = data[p],
			base_price2 = "",
			slug = item.slug,
			img_pos = item.img_pos,
			thumb_url = item.thumb_url,
			img_label = item.img_label,
			name = item.name,
			cont = item.cont,
			desc = item.desc,
			base_price = item.price.base,
			base_price2 = item.price.base2,
			comm1=item.price.comm1,
			comm2=item.price.comm2,
			teiki1_price = item.price.teiki1,
			teiki2_price = item.price.teiki2,
			try_price = item.price.try,
			base_url = item.url.base,
			base_url2 = item.url.base2,
			teiki_url = item.url.teiki,
			try_url = item.url.try,
			disc_txt = item.disc_txt,
			banner = item.banner;
			
		html += '<div data-offset="200" id="'+slug+'" class="cell inv '+slug+' '+img_pos+' index-'+p+'"><a href="/user_data/croire#'+slug+'" class="cover"></a><div class="inner">';
		if(item.url.try_direct == true){
			if(img_label){
				html += '<div class="img img_labeled"><a href="'+try_url+'"><img class="'+slug+'" src="'+thumb_url+'"><img class="img_label" src="'+img_label+'"></a></div>';
			}else{
				html += '<div class="img"><a href="'+try_url+'"><img class="'+slug+'" src="'+thumb_url+'"></a></div>';
			}
			html += banner;
		}else{
			html += '<div class="img"><img src="'+thumb_url+'"></div>';
		}
		html += '<div class="data">';
		html += '<div class="ttl">'+name+'<i class="cont">'+cont+'</i>'+'<span>'+desc+'</span></div>';
		if(base_price2 != "" && base_price2){
			html += '<div class="base_price"><p class="_1"><span>メーカー希望販売価格 </span><i>'+base_price+'</i>円（税込）'+comm1+'<br class="sp" /><i>'+base_price2+'</i>円（税込）'+comm2+'</p></div>'
		}else{
			html += '<div class="base_price"><p class="_1"><span>メーカー希望販売価格 </span><i>'+base_price+'</i>円（税込）</p></div>'
		}
		html += '</div>'
		if(item.url.try_direct == true){
			html += '<ul class="idx sp-rev">';
		}else{
			html += '<ul class="idx">';
		}
		
		if(teiki1_price){
			var teiki_icon1_img = '<img src="/img/top/30poffS.png" alt="" width="93" height="54" >';
			if(item.price.teiki_icon1_img == "45"){
				teiki_icon1_img = '<img src="/img/top/45poffS.png" alt="" width="93" height="54" >';
			}
			html += '<li class="teiki"><p class="hd">お得な定期お届けコース</p>'
				 //+ 	'<p class="txt">サイトリニューアルキャンペーン！</p>'
				 +  '<p class="txt">&nbsp;</p>'
				 + 	'<div class="price">'
				 +	'<p class="_2">'+teiki_icon1_img+'<i>'+teiki1_price+'</i><b class="en">円（税込）</b></p></div>'
				 + 	'<a class="btn2" data-slug="'+slug+'" data-payment="teiki" href="'+teiki_url+'">詳しくみる</a>'
				 +	'</li>';
		}
		if(base_price){
			if(item.url.try_direct == true){
				html += '<li class="onestop red"><p class="hd">期間限定特別価格</p>';
			}else{
				html += '<li class="onestop"><p class="hd">今回のみお届けコース</p>';
			}
			
			if(disc_txt){
				html += '<p class="txt show disc"><span>'+disc_txt+'</span></p>';
			}else{
				html += '<p class="txt">&nbsp;</p>';
			}
			
			html += '<div class="price">';
			if(item.price.base_from){
				html += '<p class="_2"><img src="/img/top/10poff.png" alt="10poff" width="110" height="49" /><i>'+item.price.base_from+'</i><b class="en">円（税込）〜</b></p></div>'
			}else{
				
				if(disc_txt){
					html +=	'<p class="_2 sale"><b class="label">期間限定<br />特別価格</b><i>'+try_price+'</i><b class="en">円（税込）</b></p></div>'
				}else{
					html +=	'<p class="_2"><img src="/img/top/10poff.png" alt="10poff" width="110" height="49" /><i>'+try_price+'</i><b class="en">円（税込）</b></p></div>'
				}
				
			}
			if(base_price2 != "" && base_price2){
				html += '<a class="btn2" data-slug="'+slug+'" data-href="'+try_url+'|'+base_url+'|'+base_url2+'" data-price="'+try_price+'|'+base_price+'|'+base_price2+'" data-payment="onestop" href="'+base_url+'" data-comm="'+comm1+'|'+comm2+'">詳しくみる</a></li>';
			}else{
				
				if(item.url.try_direct == true){
					html += '<a class="btn2_D" href="'+try_url+'">詳しくみる</a></li>';
				}else{
					html += '<a class="btn2" data-slug="'+slug+'" data-href="'+try_url+'|'+base_url+'" data-price="'+try_price+'|'+base_price+'" data-payment="onestop" href="'+base_url+'">詳しくみる</a></li>';
				}
			}
			
		}
		html += '</ul><p class="pb12">※他のクロワール商品の定期購入特典である初回限定50％割引はございません。</p></div></div>';
		
	}//for
	
	wrap.append(html);
	
});

$(function(){
	var wrap = $("#croire-list");
	if($("#croire-list")[0]){
		$.ajax({
		    url: "/product/products.json",
		}).then(generateList).then(function(){
			//initSlide();
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
/* !! ポップアップ */
$(function(){
	var body = $("body");
	var popup = $("#popup");
	var inner = $("#popup-inner");
	var btnOn = $("#croire-list .btn2");
	var btnClose = popup.find(".close");
	if($("#croire-index")[0] || $(".product_page.PB12_2")){
		$("body").on("click","#croire-list .btn2,#popup .close,#shade,#show_popup_pb12",function(event){
			var cell = $(this).closest(".cell");
			if(cell.is("#croire-coat")){
				return;
			}
			if(!is_mobile){
				$("#popup").css("top", 0);
			}
			
			event.preventDefault();
			var This = $(this);
			var payment = This.data("payment");
			var slug = This.data("slug");
			$(".comm_1, .comm_2").html("");
			if(payment){
				$("#popup").removeClass().addClass(payment);
				if(slug){
					$("#popup").addClass(slug);
				}
				inner.find(">div").removeClass("on");
				inner.find("."+payment).addClass("on");
				if(payment == "teiki"){
					var href = This.attr("href");
					$("#teiki-link-btn").attr("href",href);
					if(!is_mobile){
						var top = $(window).scrollTop();
						$("#popup").css("top", top);
					}
					
				}else{
					if(!is_mobile){
						$("#popup").css("top", "50%");
					}
					
					var price = This.data("price"),
						price = price.split("|");
					var href = This.data("href"),
						href = href.split("|");
					inner.find(".price_1 i").html(price[0]);
					inner.find(".price_2 i").html(price[1]);
					inner.find(".btn2._1").attr("href",href[0]);
					inner.find(".btn2._2").attr("href",href[1]);
					if(href[2]) inner.find(".btn2._3").attr("href",href[2]);
					if(price[2]){
						inner.find(".price_3 i").html(price[2]);
						$(".price3_row").fadeIn(0);
					}else{
						inner.find(".price_3 i");
						$(".price3_row").fadeOut(0);
					}
					if(This.data("comm")){
						var comm = This.data("comm"),
							comm = comm.split("|");
						if(comm[0]) inner.find(".comm_1").html(comm[0]);
						if(comm[0]) inner.find(".comm_2").html(comm[1]);						
					}
				}
			}
			
			body.toggleClass("popup");
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
/*
		$("iframe.detail").on("load",function(){
			var H = $(this).contents().innerHeight();
			$(this).css("height",H);
		})
*/
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




//!! 商品名の変形
//!!定期価格の表示変更
var dispAltPrice =(function dispAltPrice(data){
		//var priceDisp = $("#detail_description_box__class_range_sale_price");
		var	mypid = $(".ec-productRole").data("pid");
		var data = data.filter(function(item, index){
		  	if (item.pid == mypid) return true;
		});
		if(!data[0]){
			if(mypid == "125"){
				dispAltPrice2();
			}else{
				return;
			}
		} 
		var price1 = data[0].price.teiki1;
		var price2 = data[0].price.teiki2;
		
		if( mypid == "122"){
			$(".ec-price__price").addClass("pb12").html(price1+'円');
			//return;
		}else{
			
		}
		$(".ec-price__price").addClass("dis").html(price1+'円');
		if( mypid == "122"){
			var rate = [45,30];
		}
		else if(mypid == "125"){
			var rate = [65,30];
		}else{
			var rate = [30,15];
		}
		var html = 	"";
			html = 	'<span class="teiki2">(2回目以降'+rate[1]+'%OFF '+price2+'円 税込)</span><br />';
			html += '<span class="teiki2">(特典回'+rate[0]+'%OFF '+price1+'円 税込)</span>';
			html += '<span class="notice" style="font-size:.6em">※特典回とは3回購入毎の次の購入を指します</span>';
		$(".ec-price__price").next(".ec-price__tax")
		.after(html);
		
		
		//return;
	}
);

function dispAltPrice2(){
	$(".ec-price__price").addClass("").html('初回65%OFF 975円<br />');
	var html = 	"";
		html = 	'<span class="teiki2">(2回目以降15%OFF  2,368円 税込)</span>';
		html += '<span class="teiki2">(特典回30%OFF 1,950円 税込)</span>';
		html += '<span class="teiki2">(購入6回目ごとにクロワールアイ・Qプレゼント)</span>';
		html += '<span class="notice" style="font-size:.6em">※特典回とは3回購入毎の次の購入を指します</span>';
	$(".ec-price__price").next(".ec-price__tax")
	.after(html);
};


$(function(){
	var ttl = $('.ec-headingTitle');
	if(ttl[0]){
		var txt = ttl.html();
		txt = txt.replace(/\[/, '<span class="type">');
		txt = txt.replace(/\]/, '<\/span><br />');
		ttl.html(txt);
		if(txt.match("継続価格")){
			$("#item_detail_area span.small").each(function(){
				var This = $(this),
					str = This.text();
					str = str.replace(/税抜/,"税込");
				This.text(str);
			});
		}
		if(txt.match("定期購入価格")){
			$("body").addClass("teiki");
			var id = $(".ec-productRole").data("pid");
			$.ajax({
			    url: "/common/js/products.json"
			}).then(dispAltPrice);
		}
	}
});



/* !! 割引の表示 */

function displayCoupon(pid){
	var html 	= "<div class='banner ' data-offset='-300'>"
				//+ "<a href='/products/detail/124'></a>"
				+ "<div class='date'>3/2〜3/3<i>期間限定特別価格+送料無料</i></div>"
				+ "<div class='price-row'><span class='hd'>メーカー希望販売価格3,780円より1,280円OFF!</span>"
				+ "<span class='price1'><i>2,500</i><i class='en'>円</i></span>"
				+ "<span class='box'>1日あたり約<i>84</i><i class='en'>円</i></span></div>※お1家族様1袋まで</div>";
	if(pid == "36" || pid == "29"){
		var html 	= "<div class='banner ' data-offset='-300'>"
					+ "<a href='/products/detail/124'></a>"
					+ "<div class='date'>3/2〜3/3<i>期間限定特別価格+送料無料</i></div>"
					+ "<div class='price-row'><span class='hd'>メーカー希望販売価格3,780円より1,280円OFF!</span>"
					+ "<span class='price1'><i>2,500</i><i class='en'>円</i></span>"
					+ "<span class='box'>1日あたり約<i>84</i><i class='en'>円</i></span></div>※お1家族様1袋まで ▶︎ 商品ページはこちら</div>";
	}
	$(".ec-headingTitle").before(html);
}

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
/* !! 期間限定バナー */
$(function(){

	var data = sessionStorage.getItem('f_bnr_close_flg');
	if(data){
		return false;
	}

	var a = new Date();
	var b = new Date(2021,4,14);
	
	console.log(a);
	console.log(b);
	if(a > b){
		$("#cp_bnr_wrap").remove();
		return false;
	}
	var html =	'<div class="f-bnr">';
		html += '<a href="/products/detail/115"></a>';
		html += '<i class="close"><img src="/common/img/cp_GW/close_btn.png" alt="close" width="24" height="24" /></i>';
		html += '<picture>';
		html += '<source media="(max-width: 767px)" srcset="/common/img/cp_midori1980_sp.png">';
		html += '<img src="/common/img/cp_midori1980.png" alt="cp_202102" width="" height="" />';
		html += '</picture></div>';
	
	$("body").addClass("has_f-bnr").append(html);
	

	var close = $(".f-bnr .close");
	$("body").on("click",".f-bnr .close",function(){
		$("body").removeClass("has_f-bnr");
		$(".f-bnr").remove();

		// sessionStorage にデータを保存する
		sessionStorage.setItem('f_bnr_close_flg', true);
	});


});


