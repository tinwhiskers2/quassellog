<?php
$submitType="";
if(isset($_REQUEST['submit'])) {
	$submitType=$_REQUEST['submit'];
}
if($submitType != "Export") {
	printHeader();
}

$sqlitedir = 'sqlite:/var/lib/quassel/quassel-storage.sqlite';

$password="";
$username="";
$task="";
if(isset($_REQUEST['task'])) {
	$task=$_REQUEST['task'];
} else {
	printLogonScreen();
	exit(0);
}

if(isset($_REQUEST['username'])) {
	$username=$_REQUEST['username'];
}
if(isset($_REQUEST['password'])) {
	$password=$_REQUEST['password'];
}

if(checkPassword($username,$password)>0) {
	echo("Invalid logon\n</body></html>");
	exit(0);
}

if($task == "filter") {
	printMainScreen();
	exit(0);

} else if($task == "search") {
	if($_REQUEST['submit'] == "Export") {
		printResults(true);
	} else {
		printResults(false);
		echo("</body></html>");
	}
	exit(0);
} else {
	echo ("Stop fiddling!</body></html>");
}



exit(0);

///////////////////////
function checkPassword($username,$password) {
///////////////////////
	global $sqlitedir;

	try {
		$dbh  = new PDO($sqlitedir);
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n</body></html>";
		exit(0);
	}

	$query =  "SELECT username, userid, password, hashversion FROM 'quasseluser'";

	$stmt = $dbh->prepare($query);
	$stmt->execute();

	foreach ($stmt as $row)
	{
		if($row['username'] == $username) {
			switch ($row['hashversion']) {
				case null:
				case 0:
				return checkHashedPasswordSha1($password, $row['password']);
				break;
				case 1:
				return checkHashedPasswordSha2_512($password, $row['password']);
				break;
				default:
				return(1);
				break;
			}
		}
	}
	$dbh = null;

	return(1);
}

function checkHashedPasswordSha1($plainPassword, $dbHashedPassword)
{
	$calculatedPasswordHash = hash("sha1", $plainPassword);

	if ($calculatedPasswordHash == $dbHashedPassword) {
		return 0;
	}
	return 1;
}

function checkHashedPasswordSha2_512($plainPassword, $dbHashedPassword)
{
	$dbHashedPasswordArray = explode(":", $dbHashedPassword);

	if (count($dbHashedPasswordArray) == 2) {
		$calculatedPasswordHash = hash("sha512", $plainPassword . $dbHashedPasswordArray[1]);
		if ($calculatedPasswordHash == $dbHashedPasswordArray[0]) {
			return 0;
		}
	}
	return 1;
}



///////////////////////
function printLogonScreen() {
///////////////////////
?>

<form method="post" action="">

	<table class="layout" border="0" align="center" width="100%">
	<tr>
	<td width="20%">
	&nbsp;
	</td>
	<td width="60%">
		<table class="btable">
		<thead>
		<tr>
		<th>Authentication required</th>
		<th>&nbsp;</th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td>
		Quassel username:
		</td>
		<td>
		<input type="text" name="username" id="username">
		</td>
		</tr>
		<tr>
		<td>
		Password:
		</td>
		<td>
		<input type="password" name="password" id="password"> <input type="submit" value="Log in">
		</td>
		</tr>
		</tbody>
		</table>
	</td>
	<td width="20%">
	&nbsp;
	</td>
	</tr>
	</table>
<input type="hidden" name="task" value="filter">
</form>
</body>
</html>

<?php

}
///////////////////////
function printMainScreen() {
///////////////////////

global $password;
global $username;
?>
<form method="post" onsubmit="return checksubmit()" action="">

	<table class="layout" border="0" align="center" width="90%">
	<tr><td width="80%"><b>Select Message Filters</b></td><td width="20%">Logged in as <?= $username ?></td></tr>
	</table>
	<table class="layout" border="0" align="center" width="90%">
	<tr>
	<td width="50%">
		<table class="btable">
		<thead>
		<tr>
		<th>Network</th>
		<th>Channel</th>
		</tr>
		</thead>
		<tbody>
<?php

$chans = printAllChans();

?>
		</tbody>
		</table>
	</td>
	<td width="50%">

		<table class="btable">
		<thead>
		<tr>
		<th>Message Content</th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td>
		Message must include the text:<br><input type="text" id="text1" name="text1" size="40"> AND <br>
		<input type="text" id="text2" name="text2" size="40"> AND<br>
		<input type="text" id="text3" name="text3" size="40">
		</td>
		</tr>
		</tbody>
		</table>

		<br>

		<table class="btable">
		<thead>
		<tr>
		<th>Sender</th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td>
		Sender nick or ident or hostmask  must include: <input type="text" id="nick" name="nick" size="40">
		</td>
		</tr>
		</tbody>
		</table>

		<br>

		<table class="btable">
		<thead>
		<tr>
		<th>Date range</th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td>
		Start Date:
		</td>
		<td>
		<input type="text" id="startdate" name="startdate"></input> (yyyy-mm-dd)
		</td>
		</tr>
		<tr>
		<td>
		End Date:
		</td>
		<td>
		<input type="text" id="enddate" name="enddate"></input> (yyyy-mm-dd)
		</td>
		</tr>
		</tbody>
		</table>
		<br>

		<input type="checkbox" name="joins" id="joins">
		<label for="joins">Include joins, parts etc.</label><br>
		<input type="checkbox" name="host" id="host">
		<label for="host">Include ident/host</label><br><br>

		<input type="submit" value="Search" name="submit"><br><br>
<?php
	if(extension_loaded('zip')) {
?>
		<input type="submit" value="Export" name="submit"><br>
		Note: use Export (zip) in preference to Search for large result sets and test with a limited date range first.
<?php
	}
?>
	</td>
	</tr>
	</table>
<input type="hidden" name="task" value="search">
<input type="hidden" name="password" value="<?= $password ?>">
<input type="hidden" name="username" value="<?= $username ?>">
</form>

<script>

	document.onkeydown = function(evt) {
		evt = evt || window.event;
		if (evt.keyCode == 27) {
			d.show();
			d2.show();
		}
	};

	document.onclick = function(e) {
		//console.log (e.target.id);
		if(e.target.id == "picker") {
			return;
		}
		if(e.target.id != "startdate") {
			d.show();
		}
		if(e.target.id != "enddate") {
			d2.show();
		}
	};


	function formatDateToString(date){
		var dd = (date.getDate() < 10 ? '0' : '') + date.getDate();
		var MM = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
		var yyyy = date.getFullYear();
		return (yyyy + "-" + MM + "-" + dd);
	}

	const d = new Datepicker(document.getElementById("startdate"), {
			format: (d) => {
			return formatDateToString(d);
		},
	});

	const d2 = new Datepicker(document.getElementById("enddate"), {
			format: (d) => {
			return formatDateToString(d);
		},
	});

	function checkSelect(item) {
		for (x=0;x<document.getElementsByTagName('input').length; x++) {
			iname = document.getElementsByTagName('input').item(x).name;
			if(iname.substr(0,item.name.length+1)==item.name+"/") {
				document.getElementById(iname).checked=item.checked;
			}
		}
	}

	function checksubmit() {

		var startDate = document.getElementById("startdate").value;
		var endDate = document.getElementById("enddate").value;
		if((startDate.length==0) && (endDate.length==0)) {
			var r = confirm("Searches with no date restrictions might take a long time, be overwhelmingly large and could time out. Are you sure you want to continue?");
			if (!r) {return(false);}
		}
		return(true);
	}
</script>
</body>
</html>
<?php
}


///////////////////////
function printAllChans () {
///////////////////////
	global $sqlitedir;

	try {
		$dbh  = new PDO($sqlitedir);
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n</body></html>";
		exit(0);
	}


	$query =  "SELECT networkid, networkname FROM 'network'";

	$stmt = $dbh->prepare($query);
	$stmt->execute();

	$networks = [];
	foreach ($stmt as $row)
	{
		$networks[$row['networkname']] = $row['networkid'];
	}
	ksort($networks);
	$lastname="";
	foreach($networks as $name => $id) {
?>
		<tr>
		<td>
		<input type="checkbox" name="<?= $name ?>" id="<?= $name ?>" onclick="checkSelect(this);">
		<label for="<?= $name ?>"><?= $name ?></label>
		</td>
		<td>
		(All Channels)
		</td>
		</tr>
<?php

		$query =  "SELECT buffername, bufferid FROM 'buffer' WHERE networkid = '".$id."' ";

		$stmt = $dbh->prepare($query);
		$stmt->execute();

		$channels = [];

		foreach ($stmt as $row)
		{
			$channels[$row['buffername']] = $row['bufferid'];
		}
		ksort($channels);
		foreach($channels as $cname => $cid) {
			if(strlen($cname)>0) {
?>
		<tr>
		<td>
		&nbsp;
		</td>
		<td>
		<input type="checkbox" name="<?= $name ?>/<?= $cname ?>" id="<?= $name ?>/<?= $cname ?>">
		<label for="<?= $name ?>/<?= $cname ?>"><?= $cname ?></label>
		</td>
		</tr>
<?php
			}
		}
	}
	$dbh = null;
}

///////////////////////
function printHeader() {
///////////////////////
?>
<html>
<head>
<link rel="stylesheet" href="quassellog.css">
<link rel="stylesheet" href="datepicker.css">
<script src="datepicker.js"></script>
<title>Quassel Log Viewer</title>
</head>
<body>
<?php
}

///////////////////////
function printResults($export) {
///////////////////////

	global $username;
	$tempdir="";
	$tempzip="";
	if($export) {
		$tempzip = $tempfile=tempnam(sys_get_temp_dir(),'');
		$tempdir = $tempfile=tempnam(sys_get_temp_dir(),'');
		if (file_exists($tempdir)) { unlink($tempdir); }
		mkdir($tempdir);
	} else {
		ob_implicit_flush();

?>
		<table class="btable" width="100%">
		<thead>
		<tr>
		<th>Time</th>
		<th>Sender</th>
		<th>Message</th>
		<th>Network</th>
		<th>Channel</th>
		</tr>
		</thead>
		<tbody>
<?php
	}

	$filter1="";
	$filter2="";
	$filter3="";
	$filter1sql="";
	$filter2sql="";
	$filter3sql="";

	$sender="";
	$sendersql="";

	$startdatesql="";
	$enddatesql="";

	$joins=false;
	$host=false;

	if(isset($_REQUEST['nick'])) {
		if(strlen($_REQUEST['nick'])) {
			$sendersql=" sender LIKE :sender AND";
			$sender="%".$_REQUEST['nick']."%";
		}
	}
	if(isset($_REQUEST['text1'])) {
		if(strlen($_REQUEST['text1'])) {
			$filter1sql=" message LIKE :filter1 AND";
			$filter1="%".$_REQUEST['text1']."%";
		}
	}
	if(isset($_REQUEST['text2'])) {
		if(strlen($_REQUEST['text2'])) {
			$filter2sql=" message LIKE :filter2 AND";
			$filter2="%".$_REQUEST['text2']."%";
		}
	}
	if(isset($_REQUEST['text3'])) {
		if(strlen($_REQUEST['text3'])) {
			$filter3sql=" message LIKE :filter3 AND";
			$filter3="%".$_REQUEST['text3']."%";
		}
	}

	if(isset($_REQUEST['startdate'])) {
		if(strlen($_REQUEST['startdate'])) {
			$timestamp = strtotime($_REQUEST['startdate']) * 1000;
			if($timestamp>100000) {
				$startdatesql=" time > ".$timestamp." AND";
			}
		}
	}

	if(isset($_REQUEST['enddate'])) {
		if(strlen($_REQUEST['enddate'])) {
			$timestamp = strtotime($_REQUEST['enddate']) * 1000;
			if($timestamp>100000) {
				$enddatesql=" time < ".$timestamp." AND";
			}
		}
	}
	if(isset($_REQUEST['joins'])) {
		$joins=true;
	}
	if(isset($_REQUEST['host'])) {
		$host=true;
	}

	global $sqlitedir;

	try {
		$dbh  = new PDO($sqlitedir);
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n</body></html>";
		exit(0);
	}

	$query = "SELECT type, flags, time, sender, message FROM backlog JOIN sender ON backlog.senderid = sender.senderid , buffer ON backlog.bufferid = buffer.bufferid ".
	", network ON buffer.networkid = network.networkid , quasseluser ON buffer.userid = quasseluser.userid ".
	"WHERE ".$filter1sql.$filter2sql.$filter3sql.$sendersql.$startdatesql.$enddatesql." username = :username AND networkname = :network AND buffername = :buffer";

	$stmt = $dbh->prepare($query);
	$stmt->bindParam(':username', $username);

	$flist=[];

	$fh=null;

	foreach($_POST as $name => $value) {


		if(strpos($name,"/") == false) {continue;}
		list($network, $channel) = explode("/", $name);

		if($export) {
			$fpath = $tempdir . "/" . $network . "-" . $channel . ".txt";
			array_push($flist,$fpath);
			$fh = fopen($fpath, "w");
		}

		if(strlen($filter1)) {
			$stmt->bindParam(':filter1', $filter1);
		}
		if(strlen($filter2)) {
			$stmt->bindParam(':filter2', $filter2);
		}
		if(strlen($filter3)) {
			$stmt->bindParam(':filter3', $filter3);
		}
		if(strlen($sender)) {
			$stmt->bindParam(':sender', $sender);
		}
		$stmt->bindParam(':network', $network);
		$stmt->bindParam(':buffer', $channel);
		$stmt->execute();

		foreach ($stmt as $row)
		{
			if($row['type'] == 32768) continue;  // netsplit
			if($row['type'] == 65536) continue;  // netsplit

			$nick = $row['sender'];
			$ident="";
			if(strpos($nick,"!")) {
				list($newnick, $newident) = explode("!", $nick);
				$nick=$newnick;
				$ident=$newident;
			}

			$nickout = $nick;
			if($host) {
				$nickout = $row['sender'];
			}

			if($row['type'] != 1) {
				if((!$joins)&&($row['type'] != "4")) continue;  // actions (4) are still allowed even if other things are skipped
				$ion="<i><font color = \"#00bd14\">";
				$ioff="</font></i>";
				if($export) {
					$ion="";
					$ioff="";
				}

				switch ($row['type']) {
					case null:
					case "2":
					$row['message'] = $ion."Notice ".$row['message'].$ioff;
					break;
					case "4":
					$row['message'] = $ion.$nick." ".$row['message'].$ioff;
					break;
					case "8":
					$row['message'] = $ion."Nick ".$row['message'].$ioff;
					break;
					case "16":
					$row['message'] = $ion."Mode ".$row['message'].$ioff;
					break;
					case "32":
					$row['message'] = $ion."Joined ".$row['message'].$ioff;
					break;
					case "64":
					$row['message'] = $ion."Parted ".$row['message'].$ioff;
					break;
					case "128":
					break;
					case "256":
					$row['message'] = $ion."Kicked ".$row['message'].$ioff;
					break;
					case "512":
					$row['message'] = $ion."Killed ".$row['message'].$ioff;
					break;
					case "1024":
					$row['message'] = $ion."Server ".$row['message'].$ioff;
					break;
					case "2048":
					$row['message'] = $ion."Info ".$row['message'].$ioff;
					break;
					case "4096":
					$row['message'] = $ion."Error ".$row['message'].$ioff;
					break;
					case "8192":
					$row['message'] = $ion."Day changed to ".$row['message'].$ioff;
					break;
					case "16384":
					break;
					default:
					$row['message'] = $row['message']." (".$row['type'].")";
					break;
				}
			}

			$date = date('Y-m-d H:i:s', $row['time'] / 1000);
			if($export) {
				fwrite($fh, $date."\t".$nickout."\t".$row['message']."\n");
			} else {
?>
		<tr>
		<td><?= $date ?></td><td><?= $nickout ?></td><td><?= $row['message'] ?></td><td><?= $network ?></td><td><?= $channel ?></td>
		</tr>
<?php
			}
		}
	}
	if($export) {
		fclose($fh);

		// create ZIP
		$zip = new ZipArchive();

		if ($zip->open($tempzip, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) == true) {
			foreach($flist as $fpath) {

				$zip->addFile($fpath, basename($fpath));
			}
			$zip->close();
			$zipName = "quassel-".gmdate('Ymd-his').".zip";
			header("Content-Type: application/zip");
			header("Content-Disposition: attachment; filename=\"".$zipName."\"");
			header("Content-Length: " . filesize($tempzip));
			readfile($tempzip);
		} else {
			printHeader();
			echo("Failed to create zip archive\n</body></html>");
		}
		// cleanup
		foreach($flist as $fpath) {
			unlink($fpath);
		}
		unlink($tempzip);
		rmdir($tempdir);
	} else {
?>
		</tbody>
		</table>
<?php
	}
	$dbh = null;
}
?>
