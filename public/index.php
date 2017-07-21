<?php

header("Content-Type: text/html; charset=utf8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");


$di = new \Phalcon\DI\FactoryDefault();

$di->set('db', function(){
	return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
		"host" => "mariadb",
		"username" => "root",
		"password" => "123456",
		"dbname" => "operand_iscool"
	));
});

$app = new \Phalcon\Mvc\Micro($di);



//RETRIEVES ALL BANK ACCOUNTS
$app->get('/v1/bankaccounts', function() use ($app) {

	$sql = "SELECT id,name,balance FROM bank_account ORDER BY name";
	$result = $app->db->query($sql);
	$result->setFetchMode(Phalcon\Db::FETCH_OBJ);
	$data = array();

	while ($bankAccount = $result->fetch()){
		$data[] = array(
			'id' => $bankAccount->id,
			'name' => $bankAccount->name,
			'balance' => $bankAccount->balance,
		);
	}


	$response = new Phalcon\Http\Response();

	if ($data == false) {
		$response->setStatusCode(404, "Not Found");
		$response->setJsonContent(array('status' => 'NOT-FOUND'));
	} else {
		$response->setJsonContent(array(
			'status' => 'FOUND',
			'data' => $data
		));
	}


	return $response;

});



//Adds a new bank account
$app->post('/v1/bankaccounts', function() use ($app) {

    $bankAccount = $app->request->getPost();

    if (!bankAccount) {
    	$bankAccount = (array) $app->request->getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->insert("bank_account",
            array($bankAccount['name'], $bankAccount['balance']),
            array("name", "balance")
        );

        $response->setStatusCode(201, "Created");
        $bankAccount['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array('status' => 'OK', 'data' => $bankAccount));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});


$app->options('/v1/bankaccounts', function () use ($app) {
	$app->response->setHeader('Access-Control-Allow-Origin', '*');
});



//Updates bank account based on primary key
$app->put('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {

    $bankAccount = $app->request->getPut();
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->update("bank_account",
            array("name", "balance"),
            array($bankAccount['name'], $bankAccount['balance']),
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});





//Deletes bank account based on primary key
$app->delete('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->delete("bank_account",
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;
});







$app->get('/v1/bankaccounts/search/{id:[0-9]+}', function ($id) use ($app) {
    $sql = "SELECT id, name, balance FROM bank_account WHERE id = ?";

    $result = $app->db->query($sql, array($id));
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);

    $data = array();
    $bankAccount = $result->fetch();
    $response = new Phalcon\Http\Response();

    if ($bankAccount == false) {
        $response->setStatusCode(404, 'Not Found');
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
        $sqlOperations = "SELECT id, operation, bank_account_id, date, value FROM bank_account_operations 
        WHERE bank_account_id = " . $id. " ORDER BY date";

        $resultOperations = $app->db->query($sqlOperations);
        $resultOperations->setFetchMode(Phalcon\Db::FETCH_OBJ);
        $bankAccountOperations = $resultOperations->fetchAll();

        $response->setJsonContent(array(
            'id' => $bankAccount->id,
            'name' => $bankAccount->name,
            'balance' => $bankAccount->balance,
            'operations' => $bankAccountOperations,
        ));

        return $response;

    }
});



$app->post('/v1/bankaccounts/deposit', function () use ($app){
    $depositInfo = $app->request->getPost();
    if (!$depositInfo) {
        $depositInfo = (array) $app->request->getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->insert("bank_account_operations",
            array("deposit",$depositInfo['bank_account_id'], $depositInfo['value'],date('Y-m-d H:i:s')),
            array("operation","bank_account_id","value","date")
        );

        //atualizar saldo conta bancária
        $sqlUpdate = "UPDATE bank_account SET balance = (SELECT SUM(value) as balance FROM bank_account_operations WHERE bank_account_id = ?) WHERE id = ?";
        $app->db->query($sqlUpdate, array($depositInfo['bank_account_id'],$depositInfo['bank_account_id']));

        $response->setStatusCode(201, "Created");
        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;
});



$app->post('/v1/bankaccounts/withdrawal', function () use ($app){
    $withdrawalInfo = $app->request->getPost();
    if (!$withdrawalInfo) {
        $withdrawalInfo = (array) $app->request->getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->insert("bank_account_operations",
            array("withdrawal",$withdrawalInfo['bank_account_id'], $withdrawalInfo['value']*-1,date('Y-m-d H:i:s')),
            array("operation","bank_account_id","value","date")
        );

        //atualizar saldo conta bancária
        $sqlUpdate = "UPDATE bank_account SET balance = (SELECT SUM(value) as balance FROM bank_account_operations WHERE bank_account_id = ?) WHERE id = ?";
        $app->db->query($sqlUpdate, array($withdrawalInfo['bank_account_id'],$withdrawalInfo['bank_account_id']));

        $response->setStatusCode(201, "Created");
        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;
});








$app->get('/', function () use ($app) {
	echo "Operand Is Cool!";
});



$app->NotFound(function () use ($app){
	$app->response->setStatusCode(404, "Not Found")->sendHeaders();
	echo 'This is crazy, but this page was not found!';
});


$app->handle();