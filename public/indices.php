<?php

$db = new PDO('mysql:host=mariadb;dbname=operand_iscool', 'root', '123456') or die("Erro");


$sql = "INSERT INTO agenda (ddd, numero, excluido) VALUES ";


for ($i=0; $i < 10000; $i++) { 
	$sql .= "(:ddd$i, :numero$i, :excluido$i), ";
}

$sql = substr($sql, 0, -2) . ";";




try{
	$stmt = $db->prepare($sql);

	for ($i=0; $i < 10000; $i++) { 
		$ddd = rand(10,99);
		$numero = rand(1000,9999) . rand(1000,9999);
		$excluido = rand(0,1);

		//$stmt->bindParam
		$stmt->bindValue(":ddd$i", $ddd);
		$stmt->bindValue(":numero$i", $numero);
		$stmt->bindValue(":excluido$i", $excluido);
	}



	try{
		$stmt->execute();

	} catch (Exception $e){
		echo "Try Execute
		<pre>";
		$stmt->debugDumpParams();
		print_r($e);
		exit();
	}



} catch (Exception $e){
	echo "Try Prepare
	<pre>";
	print_r($e);
	exit();
}

echo "<br /> Script finalizado! <br />";