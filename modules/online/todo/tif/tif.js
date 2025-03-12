function _imgLoaded(e) {
	let buff = e.target.response;
	let ifds = UTIF.decode(buff);
	console.log(ifds[0]);

	let vsns = ifds, ma = 0, page = vsns[0];
	if (ifds[0].subIFD) {
		vsns = vsns.concat(ifds[0].subIFD);
	}
	
	for (let i = 0; i < vsns.length; i++) {
		let img = vsns[i];
		if (img["t258"] == null || img["t258"].length < 3) continue;
		let ar = img["t256"] * img["t257"];
		if (ar > ma) { 
			ma = ar; page = img; 
		}
	}

	UTIF.decodeImage(buff, page, ifds);
	let rgba = UTIF.toRGBA8(page), w = page.width, h = page.height;
	console.log("rgba", rgba.join(","));

	let ind = UTIF._xhrs.indexOf(e.target), img = UTIF._imgs[ind];
	UTIF._xhrs.splice(ind, 1); 
	UTIF._imgs.splice(ind, 1);
	
	let cnv = document.createElement("canvas"); 
	cnv.width = w; 
	cnv.height = h;
	let ctx = cnv.getContext("2d"), 
		imgd = ctx.createImageData(w, h);
	for (let i = 0; i < rgba.length; i++) 
		imgd.data[i] = rgba[i]; 
	ctx.putImageData(imgd, 0, 0);

	img.setAttribute("src", cnv.toDataURL());
}

window.onload = function(){
	let img = document.querySelector("#tifImg"),
		src = "tif/foldr_32.tif";

	let xhr = new XMLHttpRequest();
	UTIF._xhrs.push(xhr);
	UTIF._imgs.push(img);
	xhr.open("GET", src);
	xhr.responseType = "arraybuffer";
	//xhr.onload = UTIF._imgLoaded;
	xhr.onload = _imgLoaded;
	xhr.send();
};
