<?php
// Adds MySQL support for experiment
//check which domain experiment is hosted on

//Debug print
//ini_set('display_errors', E_ALL);

function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

$curURL = curPageURL();
$parse = parse_url($curURL);
$domain = $parse['host'];

if ($domain == 'localhost'){
	//MAMP MySQL settings
	$host = 'localhost';
	$user = 'root';
	$pass = 'root';
	$dbname = 'neurogrid';
}
elseif ($domain == 'arc-vlab.mpib-berlin.mpg.de'){
    //ARC vlab server
    $host = 'mariadb';
    $user = 'wu';
    $pass = 'ZcPLNMSp6nzRjT4T';
    $dbname= 'wu_search';
}elseif ($domain == 'arc-vlab-test.mpib-berlin.mpg.de'){
    //ARC vlab server
    $host = 'mariadb';
    $user = 'wu';
    $pass = 'ZcPLNMSp6nzRjT4T';
    $dbname= 'wu_search';
}

// elseif ($domain == 'www.causal-bayes.net'){
// 	//Björn's server
// 	$host = 'localhost';
// 	$user = 'db1019463-gp';
// 	$pass = 'kernelspace';
// 	$dbname = 'db1019463-gridsearch';
// }
// elseif ($domain == 'abc-webstudy.mpib-berlin.mpg.de'){
// 	$host = 'localhost';
// 	$user = 'turtleisadmin';
// 	$pass = '#turtleisdb1optnt!';
// 	$dbname = 'gridsearch';
// }

//	WHICH FUNCTION TO PERFORM? ////
if( isset($_GET['action']) ){
    $action = $_GET['action'];
}

//1. Intialize the database with $iter iterations
if ($action == 'initializeDB'){
	$iterations = $_GET['iter'];
	$handshake = $_GET['pass'];
	if ($handshake=='reproducingkernelhilbertspace'){
		initializeDB($host, $user, $pass, $dbname, $iterations);
	}
	
}//2. Assign a scenario
elseif ($action == 'assignScenario'){
	assignScenario($host, $user, $pass, $dbname);
}
//3. Retrieve a scenario
elseif ($action == 'retrieveScenario'){
	$MTurkID = $_GET['MTurkID'];
	retrieveScenario($host, $user, $pass, $dbname, $MTurkID);
}
//4. Complete scenario
elseif ($action == 'completeScenario') {
	$experiment = $_GET['Experiment'];
	$scenarioId = $_GET['scenarioId'];
	$MTurkID = $_GET['MTurkID'];
	$assignmentID = $_GET['assignmentID'];
	$scale = $_GET['scale'];
	$envOrder= $_GET['envOrder'];
	$searchHistory= $_GET['searchHistory'];
	$reward = $_GET['reward'];
	$starArray = $_GET['starArray'];
	$bonusLevel = $_GET['bonusLevel'];
	$age = $_GET['age'];
	$gender = $_GET['gender'];
	$processDescription = $_GET['processDescription'];
	completeScenario($host, $user, $pass, $dbname, $experiment, $scenarioId, $MTurkID, $assignmentID, $scale, $envOrder, $searchHistory, $reward, $starArray, $bonusLevel, $age, $gender, $processDescription);
}



///////////initial creation of scenarios///////////

function initializeDB($host, $user, $pass, $dbname, $iterations){
	//connect to database
	$conn = new mysqli($host, $user, $pass, $dbname);
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
		};
	//create scenario array
	$scenarioArr = array();
	//loop through reward conditions, kernel parameterizations, and horizon orderings;
	for($iter = 0; $iter < $iterations; $iter ++){
		for($environment = 0; $environment <2; $environment ++){ //two environments: smooth vs. rough
			for ($contextOrder = 0; $contextOrder <2; $contextOrder ++){
	    		$value = array('environment' => $environment, 'contextOrder' => $contextOrder);
	    		array_push ($scenarioArr, $value);
		}}};

	//Check if experiment1 table exists
	$querycheck =  "SELECT 1 FROM experiment1";
	$query_result=mysqli_query($conn, $querycheck);

	//if table exists
	if ($query_result !== FALSE){
		//wipe table
		$deleteRows = "TRUNCATE TABLE experiment1";
		if (mysqli_query($conn,$deleteRows)) {
	    	echo "db table wiped";
		}
		else {
	    	echo "Error wiping table: " . $conn->error;
		}
	}
	//if table does not exist
	else{
		//Create experiment1 table
		$createScenarios = "CREATE TABLE experiment1(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		MTurkID VARCHAR(255) NULL,
		environment INT(2) NULL, 
		contextOrder INT(2) NULL,
		grid_assignmentID VARCHAR(255) NULL,
		stick_assignmentID VARCHAR(255) NULL,
		grid_scale BLOB,
		stick_scale BLOB,
		grid_envOrder BLOB,
		stick_envOrder BLOB,
		grid_searchHistory BLOB,
		stick_searchHistory BLOB,
		grid_starArray BLOB,
		stick_starArray BLOB,
		grid_bonusLevel BLOB,
		stick_bonusLevel BLOB,
		grid_reward DECIMAL(3,2) DEFAULT 0.00,
		stick_reward DECIMAL(3,2) DEFAULT 0.00,
		grid_start DATETIME DEFAULT NULL,
		stick_start DATETIME DEFAULT NULL,
		grid_end DATETIME DEFAULT NULL,
		stick_end DATETIME DEFAULT NULL,
		grid_age INT(3) NULL,
		stick_age INT(3) NULL,
		grid_gender INT(2) NULL,
		stick_gender INT(2) NULL,
		grid_processDescription BLOB,
		stick_processDescription BLOB,
		assigned INT(2) DEFAULT 0,
		returned INT(2) DEFAULT 0,
		completed INT(2) DEFAULT 0
		)
		";

	if (mysqli_query($conn, $createScenarios)) {
	    echo "Table created successfully";
	} else {
	    echo "Error creating table: " . $conn->error;
	}
	}

	//fill scenario table with data
	foreach($scenarioArr as $condArr){
		$environment= $condArr['environment'];
		$contextOrder = $condArr['contextOrder'];
		$assigned = 0;
		$returned = 0;
		$completed = 0;
		$sql = "INSERT INTO experiment1 (environment, contextOrder, assigned, returned, completed)
		VALUES ($environment, $contextOrder, $assigned, $returned, $completed)";
		if ($conn->query($sql) === TRUE) {
	} 	else {
	    	echo "Error: " . $sql . "<br>" . $conn->error;
	}
	}

	//check number of entries
	$countQuery = "SELECT COUNT(*) FROM experiment1";
	$countResult = mysqli_query($conn,$countQuery);
	$count = mysqli_fetch_row($countResult);
	echo $count[0];
	$conn->close();

};

/////////assign random scenario that has not yet been assigned////////
function assignScenario($host, $user, $pass, $dbname){
	//connect to database
	$conn = new mysqli($host, $user, $pass, $dbname);
	// Check connection
	if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
	} ;
	//1:randomly find a scenario where assigned<1 and completed <1
	$query = "SELECT * FROM experiment1 WHERE assigned<1 AND completed<1 ORDER BY RAND() LIMIT 1";
	$result = mysqli_query($conn, $query);
	if ($result->num_rows !== 0){
		$scenarioRow = $result->fetch_array(MYSQLI_ASSOC);
		$id = $scenarioRow['id'];
		$environment = $scenarioRow['environment'];
		$contextOrder = $scenarioRow['contextOrder'];
		if ($contextOrder==0){
			//update grid_start to now() and 'assigned' to 1 
			$update = $conn -> prepare("UPDATE experiment1 SET grid_start=now(), assigned=assigned + 1 WHERE id=?");
			$update -> bind_param("i", $id);
		}else{
			//update stick_start to now() and 'assigned' to 1 
			$update = $conn -> prepare("UPDATE experiment1 SET stick_start=now(), assigned=assigned + 1 WHERE id=?");
			$update -> bind_param("i", $id);
		}
		//Execeute
		if ($result = $update->execute()){
		  	$update->free_result();
		}
		echo json_encode(array('scenarioId' => $id, 'environment'=>$environment, 'contextOrder'=>$contextOrder));
		$conn->close();
	}
	//2: if all scenarios are assigned, create a new one
	else{
		$result->close();
		$firstExp = rand(0,1); //Determine which experiment is first
		if ($firstExp==0){
			//Grid first
			$stmt = $conn->prepare("INSERT INTO experiment1 (environment, contextOrder, grid_start, assigned) VALUES (?, ?, now(), '1')");
			$stmt->bind_param("ii", $environment, $contextOrder);
		}else{
			//sticks first
			$stmt = $conn->prepare("INSERT INTO experiment1 (environment, contextOrder, stick_start, assigned) VALUES (?, ?, now(), '1')");
			$stmt->bind_param("ii", $environment, $contextOrder);
		}
		 //bind parameters to mysql query
		$environment = rand(0,1); //generate a new environment number
		$contextOrder = $firstExp; //
		$stmt->execute();
		$id = mysqli_insert_id($conn);
		echo json_encode(array('scenarioId' => $id, 'environment'=>$environment, 'contextOrder'=>$contextOrder));
		$stmt->close();
		$conn->close();
		
	}
}



///////////////retrieve scenario after return for part 2 /////////////////

function retrieveScenario($host, $user, $pass, $dbname, $MTurkID){
	//connect to database
	$conn = new mysqli($host, $user, $pass, $dbname);
	// Check connection
	if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
	} ;
	//find scenario matching MTurkID
	$query = "SELECT * FROM experiment1 WHERE MTurkID='$MTurkID' limit 1";
	$result = mysqli_query($conn, $query);
	if ($result->num_rows !== 0){
		$scenarioRow = $result->fetch_array(MYSQLI_ASSOC);
		$id = $scenarioRow['id'];
		$environment = $scenarioRow['environment'];
		$contextOrder = $scenarioRow['contextOrder'];
		if ($contextOrder==0){ // ContextOrder  is reversed for Part2
			//update stick_start to now() and 'assigned' to 1 
			$update = $conn -> prepare("UPDATE experiment1 SET stick_start=now(), returned=returned + 1 WHERE id=?");
			$update -> bind_param("i", $id);
		}elseif ($contextOrder==1){
			//update grid_start to now() and 'assigned' to 1 
			$update = $conn -> prepare("UPDATE experiment1 SET grid_start=now(), returned=returned + 1 WHERE id=?");
			$update -> bind_param("i", $id);
		}
		//Excecute
		if ($result = $update->execute()){
		  	$update->free_result();
		}
		echo json_encode(array('scenarioId' => $id, 'environment'=>$environment, 'contextOrder'=>$contextOrder));
		$conn->close();
	}
}

////////////mark scenario as completed/////////////////
function completeScenario($host, $user, $pass, $dbname, $Experiment, $scenarioId, $MTurkID, $assignmentID, $scale, $envOrder, $searchHistory, $reward, $starArray, $bonusLevel, $age, $gender, $processDescription){
	//connect to database
	$conn = new mysqli($host, $user, $pass, $dbname);
	// Check connection
	if ($conn->connect_error) {
    	die("Connection failed: " . $conn->connect_error);
	}
	//sanitize text fields
	$MTurkID = mysqli_real_escape_string($conn, $MTurkID);
	$assignmentID = mysqli_real_escape_string($conn, $assignmentID);
	$processDescription = mysqli_real_escape_string($conn, $processDescription);
	//stick of grid data?
	if ($Experiment=='grid'){//Grid
		$query = $conn->prepare("UPDATE experiment1 set grid_end=now(), MTurkID=?, grid_assignmentID=?, grid_scale=?, grid_envOrder = ?, grid_searchHistory=?, grid_reward = ?, grid_starArray=?, grid_bonusLevel=?, grid_age= ?, grid_gender = ?, grid_processDescription=?, completed=completed + 1 WHERE id=?");
		$query -> bind_param("sssssdssiisi", $MTurkID, $assignmentID, $scale, $envOrder, $searchHistory, $reward, $starArray, $bonusLevel, $age, $gender, $processDescription, $scenarioId);
	}elseif ($Experiment=='sticks'){//sticks
		$query = $conn->prepare("UPDATE experiment1 set stick_end=now(), MTurkID=?, stick_assignmentID=?, stick_scale=?, stick_envOrder = ?, stick_searchHistory=?, stick_reward = ?, stick_starArray=?, stick_bonusLevel=?, stick_age= ?, stick_gender = ?, stick_processDescription=?, completed=completed + 1 WHERE id=?");
		$query -> bind_param("sssssdssiisi", $MTurkID, $assignmentID, $scale, $envOrder, $searchHistory, $reward, $starArray, $bonusLevel, $age, $gender, $processDescription, $scenarioId);
	}
	
	if ($result = $query->execute()){
  	echo "success";
  	$query->free_result();
	}
	else {
  	echo "error";
	}
	$conn->close();
	
}
?>