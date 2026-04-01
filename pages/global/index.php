<?php
		$opc    = "";
		if(isset($_GET['opc'])) {
			$opc = $_GET['opc'];
		}
		$fichero = $opc.".php";
		if(file_exists($fichero)) {
			include($fichero);
		} else {
			 $_GET['opc'] = '';
			$fichero = "404.php";
			include($fichero);	
		}
?>