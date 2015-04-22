wx.ready(function() {
	// 1 判断当前版本是否支持指定 JS 接口，支持批量判断
	wx.checkJsApi({
		jsApiList: [
			'checkJsApi',
			'onMenuShareTimeline',
			'onMenuShareAppMessage',
			'onMenuShareQQ',
			'onMenuShareWeibo',
			'hideMenuItems',
			'showMenuItems',
			'hideAllNonBaseMenuItem',
			'showAllNonBaseMenuItem',
			'translateVoice',
			'startRecord',
			'stopRecord',
			'onRecordEnd',
			'playVoice',
			'pauseVoice',
			'stopVoice',
			'uploadVoice',
			'downloadVoice',
			'chooseImage',
			'previewImage',
			'uploadImage',
			'downloadImage',
			'getNetworkType',
			'openLocation',
			'getLocation',
			'hideOptionMenu',
			'showOptionMenu',
			'closeWindow',
			'scanQRCode',
			'chooseWXPay',
			'openProductSpecificView',
			'addCard',
			'chooseCard',
			'openCard'
		],
		success: function(res) {
			console.log('s' + res);
		},
		fail: function(res) {
			console.log('f' + res);
		},
		complete: function(res) {}
	});

	// 2. 分享接口
	// 2.1 监听“分享给朋友”，按钮点击、自定义分享内容及分享结果接口
	wx.onMenuShareAppMessage({
		title: '快来关注imall师大慈善商店和踏鸽行师大公共自行车',
		desc: '快来关注imall师大慈善商店和踏鸽行师大公共自行车，可以抽奖哦~我刚刚抽了奖哦~',
		link: 'http://mp.weixin.qq.com/s?__biz=MzAwNDA5MjQzMw==&mid=210130025&idx=1&sn=725a9f8f528999c695bcd7f2b0a8adb0#rd',
		imgUrl: 'http://mmbiz.qpic.cn/mmbiz/Bn2vpJx6j5KlpUhLibeW8Ft4ibbFuGKlEpHgULEvOctwvEJxpBLsa375PYNywtVkUTUvPicnDZgIPMA5TIgCpicib7w/640?tp=webp&wxfrom=5',
		trigger: function(res) {},
		success: function(res) {
		},
		cancel: function(res) {},
		fail: function(res) {
			console.log(res);
		}
	});

	// 2.2 监听“分享到朋友圈”按钮点击、自定义分享内容及分享结果接口
	wx.onMenuShareTimeline({
		title: '快来关注imall师大慈善商店和踏鸽行师大公共自行车',
		desc: '快来关注imall师大慈善商店和踏鸽行师大公共自行车，可以抽奖哦~我刚刚抽了奖哦~',
		link: 'http://mp.weixin.qq.com/s?__biz=MzAwNDA5MjQzMw==&mid=210130025&idx=1&sn=725a9f8f528999c695bcd7f2b0a8adb0#rd',
		imgUrl: 'http://mmbiz.qpic.cn/mmbiz/Bn2vpJx6j5KlpUhLibeW8Ft4ibbFuGKlEpHgULEvOctwvEJxpBLsa375PYNywtVkUTUvPicnDZgIPMA5TIgCpicib7w/640?tp=webp&wxfrom=5',
		trigger: function(res) {},
		success: function(res) {
			$.ajax({
				url: "main.php?a=share",
				success: function() {
					alert("分享成功，获得第二次抽奖机会");
				}
			});
		},
		cancel: function(res) {
			alert("未分享");
		},
		fail: function(res) {}
	});

	// 2.3 监听“分享到QQ”按钮点击、自定义分享内容及分享结果接口
	wx.onMenuShareQQ({
		title: '快来关注imall师大慈善商店和踏鸽行师大公共自行车',
		desc: '快来关注imall师大慈善商店和踏鸽行师大公共自行车，可以抽奖哦~我刚刚抽了奖哦~',
		link: 'http://mp.weixin.qq.com/s?__biz=MzAwNDA5MjQzMw==&mid=210130025&idx=1&sn=725a9f8f528999c695bcd7f2b0a8adb0#rd',
		imgUrl: 'http://mmbiz.qpic.cn/mmbiz/Bn2vpJx6j5KlpUhLibeW8Ft4ibbFuGKlEpHgULEvOctwvEJxpBLsa375PYNywtVkUTUvPicnDZgIPMA5TIgCpicib7w/640?tp=webp&wxfrom=5',
		trigger: function(res) {},
		success: function(res) {
		},
		cancel: function(res) {},
		fail: function(res) {
			console.log(res);
		}
	});

	// 2.4 监听“分享到微博”按钮点击、自定义分享内容及分享结果接口
	wx.onMenuShareWeibo({
		title: '快来关注imall师大慈善商店和踏鸽行师大公共自行车',
		desc: '快来关注imall师大慈善商店和踏鸽行师大公共自行车，可以抽奖哦~我刚刚抽了奖哦~',
		link: 'http://mp.weixin.qq.com/s?__biz=MzAwNDA5MjQzMw==&mid=210130025&idx=1&sn=725a9f8f528999c695bcd7f2b0a8adb0#rd',
		imgUrl: 'http://mmbiz.qpic.cn/mmbiz/Bn2vpJx6j5KlpUhLibeW8Ft4ibbFuGKlEpHgULEvOctwvEJxpBLsa375PYNywtVkUTUvPicnDZgIPMA5TIgCpicib7w/640?tp=webp&wxfrom=5',
		trigger: function(res) {},
		success: function(res) {
			alert("分享成功");
		},
		cancel: function(res) {},
		fail: function(res) {
			console.log(res);
		}
	});


});

wx.error(function(res) {
	alert(res.errMsg);
});
