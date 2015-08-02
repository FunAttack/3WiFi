<html><head>
<title>3WiFi: Добавление точек в базу</title>
<meta http-equiv=Content-Type content="text/html;charset=UTF-8">
</head><body>

<form enctype="multipart/form-data" action="upload.php" method="POST">
    <!-- Поле MAX_FILE_SIZE должно быть указано до поля загрузки файла -->
    <input type="hidden" name="MAX_FILE_SIZE" value="15000000" />
    <!-- Название элемента input определяет имя в массиве $_FILES -->
	<table>
	<tr><td>Отчёт Router Scan:</td><td><input name="userfile" type="file" accept=".csv,.txt" /></td><td>(в формате <b>CSV</b> или <b>TXT</b>)</td></tr>
	<tr><td>Ваш комментарий:</td><td><input name="comment" type="text" <?php if (isset($_POST['comment'])) echo 'value="'.htmlspecialchars($_POST['comment']).'" '; ?>/></td></tr>
	<tr><td><input type="submit" value="Отправить файл" /></td><td></td></tr>
	</table>
</form>

<?php
function getExtension($filename)
{
	return substr(strrchr($filename, '.'), 1);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($_FILES) > 0)
{
	$uploaddir = 'uploads/';
	$filename = basename($_FILES['userfile']['name']);
	$uploadfile = $uploaddir . $filename;

	if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
		echo "Файл <b>".htmlspecialchars($filename)."</b> загружен на сервер.<br>\n";
		require 'con_db.php'; /* Коннектор MySQL */

		$ext = strtolower(getExtension($filename));
		$format = '';
		if ($ext == 'csv' || $ext == 'txt') $format = $ext;
		if ($format == '')
		{
			$format = 'csv';
			echo "Неизвестное расширение/формат файла, подразумевается CSV.<br>\n";
		}
		if (($handle = fopen($uploadfile, "r")) !== FALSE)
		{
			$comment = $_POST['comment'];
			if ($comment=='') $comment='none';

			$sql="INSERT INTO `$db_name`.`free` (`comment`, `IP`, `Port`, `Authorization`, `name`, `RadioOff`, `Hidden`, `BSSID`, `ESSID`, `Security`, `WiFiKey`, `WPSPIN`, `LANIP`, `LANMask`, `WANIP`, `WANMask`, `WANGateway`, `DNS`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `comment`=?, `IP`=?, `Port`=?, `Authorization`=?, `name`=?, `RadioOff`=?, `Hidden`=?, `BSSID`=?, `ESSID`=?, `Security`=?, `WiFiKey`=?, `WPSPIN`=?, `LANIP`=?, `LANMask`=?, `WANIP`=?, `WANMask`=?,`WANGateway`=?, `DNS`=?;";
			$stmt = $db->prepare($sql);

			$row = 0;
			switch ($format)
			{
				case 'csv':
				while (($data = fgetcsv($handle, 1000, ";")) !== FALSE)
				{
					$row++;
					$num = count($data);
					if ($row == 1)
					{
						if (($data[0]!=="IP Address")or($data[1]!=="Port")or($data[4]!=="Authorization")or($data[5]!=="Server name / Realm name / Device type")or($data[6]!=="Radio Off")or($data[7]!=="Hidden")or($data[8]!=="BSSID")or($data[9]!=="ESSID")or($data[10]!=="Security")or($data[11]!=="Key")or($data[12]!=="WPS PIN")or($data[13]!=="LAN IP Address")or($data[14]!=="LAN Subnet Mask")or($data[15]!=="WAN IP Address")or($data[16]!=="WAN Subnet Mask")or($data[17]!=="WAN Gateway")or($data[18]!=="Domain Name Servers"))
						{
							echo "Неподдерживаемый формат отчёта CSV, заголовки отсутствуют!<br>\n";
							break;
						}
					}
					if ($row !== 1)
					{
						$stmt->bind_param("ssssssssssssssssssssssssssssssssssss", // format
								// INSERT
								//    comment   IP        Port      Auth      Name      RadioOff  Hidden    BSSID     ESSID     Security   Key        WPS PIN    LAN IP     LAN Mask   WAN IP     WAN Mask   WAN Gate   DNS Serv
									$comment, $data[0], $data[1], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11], $data[12], $data[13], $data[14], $data[15], $data[16], $data[17], $data[18],
								// UPDATE
									$comment, $data[0], $data[1], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11], $data[12], $data[13], $data[14], $data[15], $data[16], $data[17], $data[18]
						);
						$stmt->execute();
					}
				}
				break;
				case 'txt':
				while (($str = fgets($handle)) !== FALSE)
				{
					$data = explode("\t", $str);
					$row++;
					if ($row == 1)
					{
						if (count($data) != 23)
						{
							echo "Неподдерживаемый формат отчёта TXT, нестандартное количество столбцов!<br>\n";
							break;
						}
					}
					$stmt->bind_param("ssssssssssssssssssssssssssssssssssss", // format
							// INSERT
							//    comment   IP        Port      Auth      Name      RadioOff  Hidden    BSSID     ESSID     Security   Key        WPS PIN    LAN IP     LAN Mask   WAN IP     WAN Mask   WAN Gate   DNS Serv
								$comment, $data[0], $data[1], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11], $data[12], $data[13], $data[14], $data[15], $data[16], $data[17], $data[18],
							// UPDATE
								$comment, $data[0], $data[1], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11], $data[12], $data[13], $data[14], $data[15], $data[16], $data[17], $data[18]
					);
					$stmt->execute();
				}
				break;
			}
			fclose($handle);
			$stmt->close();
			
			if ($row > 1) echo "Файл загружен в базу.<br>\n";
			
			if (file_exists($uploadfile))
			{
				unlink($uploadfile);
				echo "Временный файл удален.<br>\n";
			} else die("Ошибка: Временный Файл не найден!<br>\n");
		};

	} else {
		die("Ошибка: Файл не был загружен!<br>\n");
	}

	require 'chkxy.php';

	echo "Операция завершена.<br>\n";
}
?></body></html>