
<input id="idMaxFileSize" type="hidden"	value="<?php echo m_func::return_bytes(ini_get('upload_max_filesize'));	?>">

<table id="progressTable" cellspacing="0">
<tbody style="background-color:#FFFFFF; width: 100%; border:1px solid black;">
	<tr>
		<td colspan="4" style="text-align: left;">
			<progress id="progressPercNew" value="0" max="100"></progress>
			<span id="progressPercTxt" class="progressPercTxtCl">&nbsp;</span>
		</td>
	</tr>
	<tr>
		<td colspan="4" style="text-align: center;">
			 Отправка файлов
		<input type="button" value="Отменить отправку" id="idFlsAbort">
		<a style="float:right;cursor:hand;display:none;" id="uplturn" title="Свернуть" >vv Свернуть в трей vv</a>
		</td>
	</tr>
	<tr>
		<td class="dnld_key"><span style="white-space: nowrap">Размер файлов:</span></td>
		<td class="dnld_val"><span style="white-space: nowrap" id="idtotal">&nbsp;</span></td>
		<td class="dnld_key"><span style="white-space: nowrap">Загружено:</span></td>
		<td class="dnld_val"><span style="white-space: nowrap" id="idreceived">&nbsp;</span></td>
	</tr>
	<tr>
		<td class="dnld_key"><span style="white-space: nowrap">Всего файлов:</span></td>
		<td class="dnld_val"><span style="white-space: nowrap" id="idallcount">&nbsp;</span></td>
		<td class="dnld_key"><span style="white-space: nowrap">Файл:</span></td>
		<td style='width:1px;' class="dnld_val"><p style='width:150px;' class='dots' id="idcount">&nbsp;</p></td>
	</tr>
	<tr>
		<td class="dnld_key"><span style="white-space: nowrap">Средняя скорость:</span></td>
		<td class="dnld_val"><span style="white-space: nowrap" id="idspeed">&nbsp;</span></td>
		<td class="dnld_key"><span style="white-space: nowrap">Осталось	времени:</span></td>
		<td class="dnld_val"><span style="white-space: nowrap" id="idtime">&nbsp;</span></td>
	</tr>
	<tr id="uploper" style="display: none;">
		<td class="dnld_val" colspan="4" style="border: 1px solid black; border-top: 0px solid black;"><span style="white-space: nowrap" id="uplres">Проверка</span></td>
	</tr>
</tbody>
</table>

<div id="progressDivFile" class="progressDiv"></div>

<div title="Развернуть" id="progressSmall" style="border:1px solid red;width:208px;position:absolute;display:none;left:250px;top:450px;">
	<span title="Развернуть" id="progressPercSmall">Загружено:25%</span>
	<span title="Развернуть" id="idtimeSmall">Осталось:23с</span>
	<a style="float:right;cursor:pointer;" id="uplexpand" title="Развернуть">^^^</a>
</div>