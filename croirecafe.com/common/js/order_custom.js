/* !!------------------------------------ */
/* !! 一覧ページ */

//!! 商品個数
var itemlist = $(".order-index-itemlist > li");


itemlist.each(function(){
	var This = $(this);
	var quantity = This.data("quantity");
	var pcode = This.data("product_code");
	var weight = 0 ;
	This.find("i").addClass("_"+quantity);
	switch(pcode){
		case 3101561:
		case 3901561:
			weight = 2.5;
			break ;
		default: 
			weight = 1;
			break;
	}
	if(String(pcode).match("4101")){
		weight = 0;
	}
	if(String(pcode).match("3901")){
		This.addClass("P");
	}
	This.attr("data-weight",weight);
});

//!! 定期購入
itemlist.each(function(){
	var This = $(this);
	var pname = This.text();
	if(pname.match("定期購入価格")){
		This.closest(".order-index-row").addClass("teiki");
	}
});




//!! 定期購入本社出荷
var shopMemo = $("a[data-shopmemo]");
shopMemo.each(function(){
	var This = $(this);
	var title = This.data("shopmemo");
	if(title.match("定期本社出荷")){
		This.closest(".order-index-row").addClass("honsha-shukka shopmemo")
		.find(".shipping-delivery").prepend('<span class="shipping-delivery-memo">定期本社出荷</span>');
	}
});



//!! 通常購入お届け日
var shippingDeliveryDate = $("td.shipping-delivery-date");
shippingDeliveryDate.each(function(){
	var This = $(this);
	var row = This.closest(".order-index-row");
	var txt = This.text();
	if(!row.is(".teiki") && txt.match("20")){
		This.prepend('<span class="shipping-delivery-memo">お届け指定日</span>');
	}
});



var orderRow = $(".order-index-row");

//!!  個口分け判定
$(window).on('load',function(){
	orderRow.each(function(){
		var totalWeight = 0 ;
		var li = $(this).find(".order-index-itemlist li");
		li.each(function(){
			var w = $(this).attr("data-weight");
			var q = $(this).attr("data-quantity");
			w = Number(w)*Number(q);
			totalWeight += w;
		});
		$(this).attr("data-totalWeight",totalWeight);
		if(totalWeight > 5){
			$(this).addClass("kowake");
		}
	});
});

//別送判定


orderRow.each(function(){
	var This = $(this);
	var sender = This.data("sender");
	var receiver = This.data("receiver");
	if( sender != receiver ){
		This.addClass("bessou")
	}else{
		This.addClass("same");
	}
});



