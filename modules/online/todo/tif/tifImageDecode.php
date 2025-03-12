<?php
class tifImageDecode {
  protected $_endianType;

  public function decodeImage($buff, array $img, $ifds){
    // No width => probably not an image
    if(!isset($img["256"]) || !$img['256']) {
      return;
    }

    $data = unpack("C*", $buff);

    $id = $this->readASCII($data, 1, 2);
    $img['isLE'] = $id == "II";
    $img['width'] = $img["256"][0];  //delete img["t256"];
    $img['height'] = $img["257"][0];  //delete img["t257"];

    $compress = isset($img["259"]) && $img["259"] ? $img["259"][0] : 1;  //delete img["t259"];
		$fillOrder = isset($img["266"]) && $img["266"] ? $img["266"][0] : 1;  //delete img["t266"];
		// PlanarConfiguration	Метод хранения компонентов каждого пикселя.
    if(isset($img["284"]) && $img["284"] && $img["284"][0] == 2) {
		  exit("PlanarConfiguration 2 should not be used!");
    }

    $bitPP = 1;  // bits per pixel
		if(isset($img["258"]) && $img["258"]) {
      $bitPP = min(32, $img["258"][0]) * count($img["258"]);
    } else {
      $bitPP = (isset($img["277"]) && $img["277"]) ? $img["277"][0] : 1;
    }
		// Some .NEF files have t258==14, even though they use 16 bits per pixel
		if($compress == 1 && isset($img["279"], $img["278"], $img["262"]) && $img["279"] && $img["278"] && $img["262"][0] == 32803) {
		  $bitPP = round(($img["279"][0] * 8) / ($img["width"] * $img["278"][0]));
    }
		//echo "bitPP ". $bitPP;

		$bitPL = ceil($img["width"] * $bitPP / 8) * 8;
		$stripOffsets = $img["273"] ?? false;
		if(!$stripOffsets) {
      // 324 TileOffsets	For each tile, the byte offset of that tile, as compressed and stored on disk.
      $stripOffsets = $img["324"];
    }
		// 279	StripByteCounts	Количество байт на полосу после компрессии.
		$byteCount = $img["279"];
		if($compress == 1 && count($stripOffsets) == 1) {
      $byteCount = [$img["height"] * ($bitPL >> 3)];
    }
		if(!$byteCount) {
      $byteCount = $img["325"];
    }
		//echo "bitPL", $bitPL, "stripOffsets"; print_r($stripOffsets); print_r($byteCount);

    $bytes = array_fill ( 0 , $img["height"] * ($bitPL >> 3) , 0 );
    $bilen = 0;

		if(isset($img["322"]) && $img["322"]) {
      // TODO tiled
		} else {

      // stripped
      $rowsPerStrip = $img["278"] ? $img["278"][0] : $img["height"];
			$rowsPerStrip = min($rowsPerStrip, $img["height"]);
			for($i = 0; $i < count($stripOffsets); $i++) {
        $toff = ceil($bilen / 8) | 0;
			  $this->_decompress($img, $ifds, $data, $stripOffsets[$i], $byteCount[$i], $compress, $bytes, $toff, $fillOrder);
        $bilen += $bitPL * $rowsPerStrip;
      }
			$bilen = min($bilen, count($bytes) * 8);
			//echo "rowsPerStrip $rowsPerStrip bilen $bilen";
		}

    $img["data"] = $bytes;
		return $img;
  }

  protected function _decompress($img, $ifds, $data, $stripOffsets, $len, $compress, &$tgt, $toff, $fillOrder){
    if($compress == 1 || ($len == count(tgt) && $compress != 32767)) {
      for($j = 0; $j < $len; $j++) {
        $tgt[$toff + $j] = $data[$stripOffsets + $j + 1];
      }
		} else if($compress == 3) {
      //UTIF.decode._decodeG3(data, stripOffsets, len, tgt, toff, img.width, fillOrder, img["t292"] ? ((img["t292"][0] & 1) == 1) : false);
    } else if($compress == 4) {
      //UTIF.decode._decodeG4(data, stripOffsets, len, tgt, toff, img.width, fillOrder);
    } else if($compress == 5) {
      //UTIF.decode._decodeLZW(data, stripOffsets, tgt, toff);
    } else if($compress == 6) {
      //UTIF.decode._decodeOldJPEG(img, data, stripOffsets, len, tgt, toff);
    } else if($compress == 7) {
      //UTIF.decode._decodeNewJPEG(img, data, stripOffsets, len, tgt, toff);
    } else if($compress == 8) {
//      let src = new Uint8Array(data.buffer, stripOffsets, len);
//			let bin = pako["inflate"](src);
//			for(let i = 0; i < bin.length; i++) {
//        tgt[toff + i] = bin[i];
//      }
		} else if($compress == 32767) {
      //UTIF.decode._decodeARW(img, data, stripOffsets, len, tgt, toff);
    } else if($compress == 32773) {
      //UTIF.decode._decodePackBits(data, stripOffsets, len, tgt, toff);
    } else if($compress == 32809) {
      //UTIF.decode._decodeThunder(data, stripOffsets, len, tgt, toff);
    } else if($compress == 34713) {
      //UTIF.decode._decodeNikon(img, ifds, data, stripOffsets, len, tgt, toff);
    } else {
      exit("Unknown compression $compress");
    }
  }


  public function toRGBA8($out) {
    $w = $out["width"];
    $h = $out["height"];
    $area = $w * $h;
    $qarea = $area * 4;
    $data = $out["data"];

    $img = array_fill ( 0 , $area * 4 , 0 );

		//console.log($out);
		// 0: WhiteIsZero, 1: BlackIsZero, 2: RGB, 3: Palette color, 4: Transparency mask, 5: CMYK
		$intp = ($out["262"] ? $out["262"][0] : 2);
		$bps = ($out["258"] ? min(32, $out["258"][0]) : 1);

		//echo "$w $h $area $qarea $intp  $bps";


    if($intp == 0) {

		} else if($intp == 1) {

		} else if($intp == 2) {

      $smpls = $out["258"] ? count($out["258"]) : 3;

			if($bps == 8) {
        if($smpls == 4) {
          for($i = 0; $i < $qarea; $i++) {
            $img[$i] = $data[$i];
          }
				}
        if($smpls == 3) {
          for($i = 0; $i < $area; $i++) {
            $qi = $i << 2;
            $ti = $i * 3;
						$img[$qi] = $data[$ti];
						$img[$qi + 1] = $data[$ti + 1];
						$img[$qi + 2] = $data[$ti + 2];
						$img[$qi + 3] = 255;
					}
				}
      } else {  // 3x 16-bit channel
        if($smpls == 4) {
          for($i = 0; $i < $area; $i++) {
            $qi = $i << 2;
            $ti = $i * 8 + 1;
						$img[$qi] = $data[$ti];
						$img[$qi + 1] = $data[$ti + 2];
						$img[$qi + 2] = $data[$ti + 4];
						$img[$qi + 3] = $data[$ti + 6];
					}
				}
        if($smpls == 3) {
          for($i = 0; $i < $area; $i++) {
            $qi = $i << 2;
            $ti = $i * 6 + 1;
						$img[$qi] = $data[$ti];
						$img[$qi + 1] = $data[$ti + 2];
						$img[$qi + 2] = $data[$ti + 4];
						$img[$qi + 3] = 255;
					}
				}
      }

		} else if($intp == 3) {

		} else if($intp == 5) {

		} else if($intp == 6 && $out["t278"]) {  // only for DSC_1538.TIF

		} else {
      exit("Unknown Photometric interpretation: " . $intp);
    }
		return $img;
	}



  public function readASCII(&$data, int $offset, int $length) : string {
    $str = "";
    for ($i = $offset; $i < $offset + $length; $i++) {
      $str .= chr($data[$i]);
    }
    return $str;
  }



/*
UTIF.toRGBA8 = function(out) {
		let w = out.width, h = out.height, area = w * h, qarea = area * 4, data = out.data;
		let img = new Uint8Array(area * 4);
		//console.log(out);
		// 0: WhiteIsZero, 1: BlackIsZero, 2: RGB, 3: Palette color, 4: Transparency mask, 5: CMYK
		let intp = (out["t262"] ? out["t262"][0] : 2), bps = (out["t258"] ? Math.min(32, out["t258"][0]) : 1);
		//log("interpretation: ", intp, "bps", bps, out);
		if(false) {
		} else if(intp == 0) {
			let bpl = Math.ceil(bps * w / 8);
			for(let y = 0; y < h; y++) {
				let off = y * bpl, io = y * w;
				if(bps == 1) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = ((data[off + (i >> 3)]) >> (7 - (i & 7))) & 1;
						img[qi] = img[qi + 1] = img[qi + 2] = (1 - px) * 255;
						img[qi + 3] = 255;
					}
				}
				if(bps == 4) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = ((data[off + (i >> 1)]) >> (4 - 4 * (i & 1))) & 15;
						img[qi] = img[qi + 1] = img[qi + 2] = (15 - px) * 17;
						img[qi + 3] = 255;
					}
				}
				if(bps == 8) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = data[off + i];
						img[qi] = img[qi + 1] = img[qi + 2] = 255 - px;
						img[qi + 3] = 255;
					}
				}
			}
		} else if(intp == 1) {
			let smpls = out["t258"] ? out["t258"].length : 1;
			let bpl = Math.ceil(smpls * bps * w / 8);
			for(let y = 0; y < h; y++) {
				let off = y * bpl, io = y * w;
				if(bps == 1) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = ((data[off + (i >> 3)]) >> (7 - (i & 7))) & 1;
						img[qi] = img[qi + 1] = img[qi + 2] = (px) * 255;
						img[qi + 3] = 255;
					}
				}
				if(bps == 2) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = ((data[off + (i >> 2)]) >> (6 - 2 * (i & 3))) & 3;
						img[qi] = img[qi + 1] = img[qi + 2] = (px) * 85;
						img[qi + 3] = 255;
					}
				}
				if(bps == 8) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = data[off + i * smpls];
						img[qi] = img[qi + 1] = img[qi + 2] = px;
						img[qi + 3] = 255;
					}
				}
				if(bps == 16) {
					for(let i = 0; i < w; i++) {
						let qi = (io + i) << 2, px = data[off + (2 * i + 1)];
						img[qi] = img[qi + 1] = img[qi + 2] = Math.min(255, px);
						img[qi + 3] = 255;
					}
				} // ladoga.tif
			}
		} else if(intp == 2) {
			let smpls = out["t258"] ? out["t258"].length : 3;

			if(bps == 8) {
				if(smpls == 4) {
					for(let i = 0; i < qarea; i++) {
						img[i] = data[i];
					}
				}
				if(smpls == 3) {
					for(let i = 0; i < area; i++) {
						let qi = i << 2, ti = i * 3;
						img[qi] = data[ti];
						img[qi + 1] = data[ti + 1];
						img[qi + 2] = data[ti + 2];
						img[qi + 3] = 255;
					}
				}
			} else {  // 3x 16-bit channel
				if(smpls == 4) {
					for(let i = 0; i < area; i++) {
						let qi = i << 2, ti = i * 8 + 1;
						img[qi] = data[ti];
						img[qi + 1] = data[ti + 2];
						img[qi + 2] = data[ti + 4];
						img[qi + 3] = data[ti + 6];
					}
				}
				if(smpls == 3) {
					for(let i = 0; i < area; i++) {
						let qi = i << 2, ti = i * 6 + 1;
						img[qi] = data[ti];
						img[qi + 1] = data[ti + 2];
						img[qi + 2] = data[ti + 4];
						img[qi + 3] = 255;
					}
				}
			}
		} else if(intp == 3) {
			let map = out["t320"];
			let smpls = out["t258"] ? out["t258"].length : 1;
			for(let i = 0; i < area; i++) {
				let qi = i << 2, mi = data[i * smpls];
				img[qi] = (map[mi] >> 8);
				img[qi + 1] = (map[256 + mi] >> 8);
				img[qi + 2] = (map[512 + mi] >> 8);
				img[qi + 3] = 255;
			}
		} else if(intp == 5) {
			let smpls = out["t258"] ? out["t258"].length : 4;
			let gotAlpha = smpls > 4 ? 1 : 0;
			for(let i = 0; i < area; i++) {
				let qi = i << 2, si = i * smpls;
				let C = 255 - data[si], M = 255 - data[si + 1], Y = 255 - data[si + 2], K = (255 - data[si + 3]) * (1 / 255);
				img[qi] = ~~(C * K + 0.5);
				img[qi + 1] = ~~(M * K + 0.5);
				img[qi + 2] = ~~(Y * K + 0.5);
				img[qi + 3] = 255 * (1 - gotAlpha) + data[si + 4] * gotAlpha;
			}
		} else if(intp == 6 && out["t278"]) {  // only for DSC_1538.TIF
			let rps = out["t278"][0];
			for(let y = 0; y < h; y += rps) {
				let i = (y * w), len = rps * w;

				for(let j = 0; j < len; j++) {
					let qi = 4 * (i + j), si = 3 * i + 4 * (j >>> 1);
					let Y = data[si + (j & 1)], Cb = data[si + 2] - 128, Cr = data[si + 3] - 128;

					let r = Y + ((Cr >> 2) + (Cr >> 3) + (Cr >> 5));
					let g = Y - ((Cb >> 2) + (Cb >> 4) + (Cb >> 5)) - ((Cr >> 1) + (Cr >> 3) + (Cr >> 4) + (Cr >> 5));
					let b = Y + (Cb + (Cb >> 1) + (Cb >> 2) + (Cb >> 6));

					img[qi] = Math.max(0, Math.min(255, r));
					img[qi + 1] = Math.max(0, Math.min(255, g));
					img[qi + 2] = Math.max(0, Math.min(255, b));
					img[qi + 3] = 255;
				}
			}
		} else {
			log("Unknown Photometric interpretation: " + intp);
		}
		return img;
	};
*/
}
