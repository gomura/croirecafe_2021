
var test = location.href.match('localhost|:88');



/* !!------------------------------------ */
/* !! 商品合計金額修正 */

function fix_total_price(){
	
	if(!test){
		//return false
	}
	
	var total_price = $(".ec-cartRole__total .ec-cartRole__totalAmount");
	
	var total_price_number = 0;
	
	total_price.each(function(){
		var This = $(this);
		var txt = This.text(),
		
		price1 = txt.replace('￥', '').replace(',', '');
		price = parseFloat(price1);
		total_price_number += price;
	});
	
	total_price = total_price_number.toLocaleString();
	//alert(total_price);
	if($(".ec-cartRole__totalText")[0]){
		$(".ec-cartRole__totalText strong").html("￥"+total_price);
	}
}

$(function(){
	if($(".ec-cartRole__totalText")[0]){
		fix_total_price();
	}
});


/* !!------------------------------------ */
/* !! 別途送料表示 */
$(function(){
	
	var container = $(".cart-container");
	
	container.each(function(){
		var This = $(this),
			itemName = This.find(".ec-cartRow__name");
			itemName.each(function(){
				var _This = $(this);
				//var price = _This.find($(".total").data("price"));
				var price = This.find(".ec-cartRole__totalAmount.total").data("price");
				var price = Number(price);
				if(_This.text().match("定期購入価格")){
					This.addClass("teiki souryou-muryou").prepend('<div class="label-tag inline">定期購入商品</div>');
					return false;
				}else if(_This.text().match("送料無料")){
					This.addClass("souryou-muryou").prepend('<div class="label-tag inline">送料無料商品</div>');
					return false;
				}else if(_This.text().match("初回お試し")){
					This.addClass("shokai-otameshi souryou-muryou").prepend('<div class="label-tag inline">初回お試し商品</div>');
					return false;
				}else{
					This.addClass("default").prepend('<div class="label-tag inline">通常購入商品</div>');
					if( price >= 7000 ){
						This.addClass("souryou-muryou");
					}
					return false;
				}
			});
	});
});



/* !!------------------------------------ */
/* !! おひとり様一回 */

$(function(){
	var container = $("#history-container");
	if(!container[0]) return;
	
	container.load("//croirecafe.com/mypage/ .ec-historyRole__detailTitle",function(){
		 var pid = container.data("pid");
		 container.find("p").each(function(){
			 var myPid = $(this).data("pid"),
			 	 status = $(this).data("status");
			 
			 if(myPid == pid && status != "注文取消し") {
				 $(".ec-blockBtn--action.add-cart.wait").removeClass("wait");
				 return false;
			 }
			 
			 $(".ec-blockBtn--action.add-cart.wait").removeClass("wait");
			 
		 });
	});
});




/* !!------------------------------------ */
/* !! アラート */
$(function(){
	$(".ec-alert-warning__text").each(function(){
		var This = $(this);
		var txt = This.text().replace(/_/, "<br />");
		This.text(txt);
	});
	
});


/* !!------------------------------------ */
/* !! 10日間お試し */
$(function(){
	$(".ec-headingTitle, .ec-imageGrid__content, .ec-cartRow__name a").each(function(){
		var This = $(this);
		var txt = This.text();
		if(txt.match("10日間お試し")){
			$("body").addClass("otameshi_10");
		}
	})
});

/* !!------------------------------------ */
/* !! 送料無料 */
$(function(){
	$(".ec-headingTitle, .ec-imageGrid__content, .ec-cartRow__name a").each(function(){
		var This = $(this);
		var txt = This.text();
		if(txt.match("送料無料")){
			$("body").addClass("shipping_free");
		}
	})
});



/* !!------------------------------------ */
/* !! 初回お試し価格 */
$(function(){
	$(".ec-imageGrid__content, .ec-cartRow__name a").each(function(){
		var This = $(this);
		var txt = This.text();
		if(txt.match("初回お試し価格")){
			$("body").addClass("type_shokai");
		}
	})
});




/* !!------------------------------------ */
/* !! 定期購入 */

$(function(){
	$(".ec-imageGrid__content, .ec-cartRow__name a").each(function(){
		var This = $(this);
		var txt = This.text();
		if(txt.match("定期購入")){
			$("body").addClass("type_teiki");
		}
	})
});


//!! 定期割引の非表示
$(function(){
	var txt = $("#periodic_discount .ec-cartRole__totalAmount").text().split("￥");
	if(txt[1] == "0"){
		$("#periodic_discount").addClass("dn");
	}
});


//!! 配送の選択
$(function(){
	if(!$("#page_shopping")[0] && !$("select[id$=_shipping_delivery_date]")[0]) return;
	
	
	//if($("body").is(".type_teiki") ){
	if($("body").is(".type_teiki") || $("body").is(".type_shokai")){
		//配送方法
		var box = $(".ec-orderDelivery");
		box.each(function(){
			var This = $(this);
			var txt = This.text();
			var shipOption = This.find("select[id$=_Delivery] option");
			if(txt.match("クロワールプロバイオティクス12")){
				console.log("match");
				shipOption.each(function(){
					if($(this).val() == 4 || $(this).val() == 7 || $(this).val() == 13){
						if($(this).val() == "") return;
						$(this).remove();
					}
				});
				return false;
			
			}else{
				console.log("notmatch");
				shipOption.each(function(){
					if($(this).val() == 11 || $(this).val() == 14 ){
						if($(this).val() == "") return;
						$(this).remove();
					}
				});
				$(".ec-select.ec-select__time").remove();
				
				if(txt.match("初回お試し価格・期間限定送料無料")){
					$(".ec-select.ec-select__delivery").remove();
					
					return false;
				}
			}
			

		});
		
		//配送日
		if(!$("body").is(".type_teiki")) return;
		$("select[id$=_shipping_delivery_date] option").each(function(){
			var This = $(this),
				val = This.val();
				date = val.split("/");
			if( date[2] == "01" || date[2] == "15" ){
				return;
			}else{
				This.remove();
				This.wrap("span");
			}
		});
		$(".ec-select__delivery label").text("発送予定日");
	}else{
		var item_name = $(".ec-imageGrid__content");
		item_name.each(function(){
			var This = $(this);
			var shipOption = This.closest(".ec-orderDelivery").find("select[id$=_Delivery] option");
			if(This.text().match("クロワールプロバイオティクス12|クロワールコート")){
				console.log("match");
				shipOption.each(function(){
					if($(this).val() == 6 || $(this).val() == 13){
						$(this).remove();
					}
				});
				return false;
			}else{
				shipOption.each(function(){
					if($(this).val() == 14){
						$(this).remove();
					}
				});
				console.log("notmatch");
			}
		});		
	}
});


//!! 登録済みクレジットカード選択時

function setBtnText(txt){
	var cfmBtn = $("#summary_box__confirm_button button");
	if(cfmBtn[0]){
		cfmBtn.text(txt);
	}
}

$(function(){
	if($("#shopping_payment_11").prop('checked')){
		setBtnText("ご注文完了ページへ");
	}
});


/* !!------------------------------------ */
/* !! クーポンの移動 */

$(window).on("load", function() {
	var cp = $("#page_shopping #coupon");
	if(cp[0]){
		 $(".ec-orderPayment:first").after(cp);
	}
});




/* !!------------------------------------ */
/* !! 代理ログイン時にクレト登録表示 */
$(function(){
	if($("#admin_support_customer_login")[0]){
		return
	}else{
		if($("#shopping_order_Payment_8")[0]){
			$("#shopping_order_Payment_8").parent().remove();
		}
	}
});


