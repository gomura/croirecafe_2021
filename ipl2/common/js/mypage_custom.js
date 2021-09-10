

/* !!------------------------------------ */
/* !!  定期継続購入ウィンドウ */

var generateList2 =( function generateList(data) {
	
	return;
	
	var wrap = $("#teikiRepurchase");
	var inner = $("#teikiRepurchase-inner");
	var uid = wrap.data("id");
	var html = '<ul>';
	
	var user = data.filter(function(item, index){
	  	if (item.id == uid) return true;
	});
	
	var teiki = user[0].pid.split(",");
	
	if(!teiki[0]) {
		return;
	}
	
	for (var p = 0; p < teiki.length; p += 1) {
		
		var pid = teiki[p];
		switch(pid) {
			//クロワール茶_CM
			case "CR_C" : 
				var did = "74" ;
				var	name = "プレミアムクロワール茶";
				break;
			
			//クロワールゴールド_CM
			case "GO_C" :
				var did = "64" ;
				var	name = "クロワールゴールド";
				break;
			
			//ユーグレナ_CM
			case "YU_C" :
				var did = "68" ;
				var	name = "緑のDHA&EPA+<br />ゴールデンユーグレナ";
				break;
			
			//コエンザイム_CM	
			case "CO_C" :
				var did = "65" ;
				var	name = "クロワール 還元型<br />コエンザイムQ10";
				break;
				
			//Nアセチルグルコサミンロコモディ+B_CM	
			case "NA_C" :
				var did = "66" ;
				var	name = "Nアセチルグルコサミン<br />ロコモディ+B";
				break;
				
			//クロワールアイ・プロ_CM	
			case "PRO_C" :
				var did = "67" ;
				var	name = "クロワールアイ・プロ";
				break;
				
			//-------
			//クロワール茶_EC
			case "CR_E" : 
				var did = "63" ;
				var	name = "プレミアムクロワール茶";
				break;
			
			//クロワールゴールド_EC
			case "GO_E" :
				var did = "72" ;
				var	name = "クロワールゴールド";
				break;
			
			//ユーグレナ_EC
			case "YU_E" :
				var did = "69" ;
				var	name = "緑のDHA&EPA+<br />ゴールデンユーグレナ";
				break;
			
			//コエンザイム_EC
			case "CO_E" :
				var did = "73" ;
				var	name = "クロワール 還元型<br />コエンザイムQ10";
				break;
				
			//Nアセチルグルコサミンロコモディ+B_EC	
			case "NA_E" :
				var did = "70" ;
				var	name = "Nアセチルグルコサミン<br />ロコモディ+B";
				break;
				
			//クロワールアイ・プロ_EC	
			case "PRO_E" :
				var did = "71" ;
				var	name = "クロワールアイ・プロ";
				break;

			default : 
				//return;
				break;
		}
		html += '<li id="_'+did+'"><a href="/products/detail/'+did+'"><span>'+name+'</span></a></li>'	
	}//for
	html += "</ul>";
	inner.append(html).addClass("show");

});

$(function(){
	var wrap = $("#teikiRepurchase");
	if(wrap[0]){
		$.ajax({
		    url: "/common/js/repurchase.json",
		    cache: false
		}).then(generateList2).then(function(){
			
		});
	}
});


/* !!------------------------------------ */
/* !! 定期一覧の文言修正　*/

//!! /mypage/periodic 
$(function(){
	if(!$("#page_ipl_periodic_purchase_index")[0] && !$("#page_ipl_periodic_purchase_history")[0]) return;
	
	var row = $(".ec-historyRole");
	var orderOrder = $(".ec-orderOrder");
	var p = $(".ec-definitions");
	
	if(row[0]){
		row.each(function(){
			var This = $(this);
			var child = This.find(p);
			child.each(function(i){
				var This = $(this);
				This.addClass("_"+( i + 1 ));
			});
			
			var txt = This.find("._2 dd").text();
			if(txt == "休止"){
				This.find("._3 dd,._4 dd").text("未定");
			}
		});
	}else if(orderOrder[0]){
		var This = $(this);
		var child = This.find(p);
		child.each(function(i){
			var This = $(this);
			This.addClass("_"+( i + 1 ));
		});
		var txt = This.find("._3 dd").text();
		if(txt == "休止"){
			This.find("._6 dd").text("未定");
		}
	}
	
});

