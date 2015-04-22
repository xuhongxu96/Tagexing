function validateForm(e) {
	var isNull = false;
	var arr = $(e.target).serializeArray();
	for (var i = 0; i < arr.length; ++i) {
		if (arr[i].value == "") {
			isNull = true;
			break;
		}
	}
	if (isNull) {
		alert("请正确填写信息！");
		return false;
	}
}