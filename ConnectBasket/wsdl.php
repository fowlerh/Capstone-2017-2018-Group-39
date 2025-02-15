<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'config.php';//make the cofig file include
global $details;//make the connection vars global


$data = json_decode( file_get_contents('php://input') );
 
if($data->method == "check_login")
{	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('SELECT Username, FirstName, LastName, EmailAddress, ReceiveEmails, Password FROM Users WHERE Username=?');
	$stmt->bind_param('s', $username); 
	
	$username = $data->username;
	$password = $data->password;
	$hashpass = password_hash($password, PASSWORD_DEFAULT);

	$stmt->execute();

	$result = $stmt->get_result();
	if ($result->num_rows == 1)
	{
		while ($row = $result->fetch_assoc()) {
			if(password_verify($password, $row['Password']))
			{
				$User = $row['Username'];
				$FirstName = $row["FirstName"];
				$LastName = $row['LastName'];
				$Email = $row['EmailAddress'];
				$Notifications = $row['ReceiveEmails'];
				$success = true;
								
			}
		}
	}
	else
	{
		$success = false;
	}
	
	$stmt->close();
	
	/*if ($success)
	{
		$stmt = $conn->prepare('Call addLogMessage(?, ?, 1)');
		$stmt->bind_param('ss', $Message, $User); 

		$Message = $User . ' logged in';
		$stmt->execute();
		
		$stmt->close();
	}*/

	$jsonData=array();
	$jsonData['success']=$success;
	$jsonData['first']=$FirstName;
	$jsonData['last']=$LastName;
	$jsonData['username']=$User;
	$jsonData['email']=$Email;
	$jsonData['notifications']=$Notifications;
	
	$_SESSION['authenticated'] = $success;
	$_SESSION['firstname'] = $FirstName;
	$_SESSION['lastname'] = $LastName;
	$_SESSION['email'] = $Email;
	$_SESSION['username'] = $User;
	$_SESSION['notifications'] = $Notifications;
	
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "check_auth")
{	
	
	$jsonData=array();
	if (isset($_SESSION['authenticated']))
	{
		$jsonData['authenticated']=$_SESSION['authenticated'];
		$jsonData['firstname']=$_SESSION['firstname'];
		$jsonData['lastname']=$_SESSION['lastname'];
		$jsonData['username']=$_SESSION['username'];
		$jsonData['email']=$_SESSION['email'];
		$jsonData['notifications']=$_SESSION['notifications'];
	}
	else
	{
		$jsonData['authenticated']=false;
		$jsonData['firstname']="";
		$jsonData['lastname']="";
		$jsonData['username']="";
		$jsonData['email']="";
		$jsonData['notifications']=$_SESSION['notifications'];
	}	
	
 	echo json_encode($jsonData);
 
}

else if($data->method == "logout")
{	
	// remove all session variables
	session_unset(); 

	// destroy the session 
	session_destroy(); 
	
}

else if($data->method == "create_user")
{
	$firstname = $data->firstname;
	$lastname = $data->lastname;
	$username = $data->username;
	$password = $data->password;
	$email = $data->email;
	$receiveEmails = intval($data->receiveEmails);
	$hashpass = password_hash($password, PASSWORD_DEFAULT);
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('Insert Into Users(Username, LastName, FirstName, Password, EmailAddress, ReceiveEmails) Values(?,?,?,?,?,?)');
	$stmt->bind_param('ssssss', $username,$lastname,$firstname,$hashpass,$email,$receiveEmails); 

	$stmt->execute();
	
	$stmt = $conn->prepare('SELECT Count(UsersTableID) FROM Users WHERE Username=? and Password=?');
	$stmt->bind_param('ss', $username,$hashpass); 


	$stmt->execute();

	$result = $stmt->get_result();
	if ($result->num_rows > 0)
	{
		$success = true;
		
		/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 2)');
		$stmt->bind_param('ss', $Message, $User); 

		$User = $_SESSION['username'];
		$Message = $username . ' was created';
		$stmt->execute();*/
		
	}
	else
	{
		$success = false;
	}

	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "change_status")
{
	$status = $data->status;
	// $id = $data->id;

	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('UPDATE Messages SET Status=? WHERE MessagesTableID=1');
	$stmt->bind_param('s', $status); 
	// $stmt->bind_param('ss', $status,$id); 

	$stmt->execute();
	
	// $stmt = $conn->prepare('Select Count(MessagesTableID) WHERE Status="?" and MessagesTableID=?');
	// $stmt->bind_param('ss', $status,$id); 

	// $stmt->execute();
	// $result = $stmt->get_result();
	// if ($result->num_rows > 0)
	// {
		$success = $conn->error;
	// }
	// else
	// {
		// $success = false;
	// }

	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}




else if($data->method == "edit_profile")
{
	$username = $_SESSION['username'];
	$email = $data->email;
	$notifications = $data->notifications;
	$groups = explode("|",$data->groups);
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('Call updateUserInfo(?,?,?)');
	$stmt->bind_param('sss', $email, $notifications, $username); 

	$stmt->execute();	
	
	/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 6)');
	$stmt->bind_param('ss', $Message, $username); 

	$Message = $username . ' updated profile.';
	$stmt->execute();*/
	
	$stmt = $conn->prepare('Call removeUserGroups(?)');
	$stmt->bind_param('s', $username); 

	$stmt->execute();	
	
	foreach ($groups as $g)
	{
		$stmt = $conn->prepare('Call addUserToGroup(?,?)');
		$stmt->bind_param('ss', $g, $username); 

		$stmt->execute();
		
		/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 6)');
		$stmt->bind_param('ss', $Message, $username); 

		$Message = 'User was added to group: ' . $g;
		$stmt->execute();*/
	}	
	
	$success = true;
	
	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "create_owner")
{
	$firstname = $data->firstname;
	$lastname = $data->lastname;
	$address = $data->address;
	$city = $data->city;
	$state = $data->state;
	$zipcode = $data->zipcode;
	$phone = $data->phone;
	$email = $data->email;
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('Insert Into Owners(FirstName, LastName, Address, City, State, ZipCode, PhoneNumber, EmailAddress) Values(?,?,?,?,?,?,?,?)');
	$stmt->bind_param('ssssssss', $firstname,$lastname,$address,$city,$state,$zipcode,$phone,$email); 

	$stmt->execute();
	
	$stmt = $conn->prepare('SELECT Count(OwnersTableID) FROM Owners WHERE FirstName=? and LastName=?');
	$stmt->bind_param('ss', $firstname,$lastname); 


	$stmt->execute();

	$result = $stmt->get_result();
	if ($result->num_rows > 0)
	{
		$success = true;
	}
	else
	{
		$success = false;
	}

	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "create_pet")
{
	$name = $data->name;
	$age = $data->age;
	$species = $data->species;
	$breed = $data->breed;
	$color = $data->color;
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('Insert Into Pets(Name, Age, Species, Breed, Color) Values(?,?,?,?,?)');
	$stmt->bind_param('sssss', $name,$age,$species,$breed,$color); 

	$stmt->execute();
	
	$stmt = $conn->prepare('SELECT Count(PetsTableID) FROM Pets WHERE Name=? and Age=?');
	$stmt->bind_param('ss', $name,$age); 


	$stmt->execute();

	$result = $stmt->get_result();
	if ($result->num_rows > 0)
	{
		$success = true;
	}
	else
	{
		$success = false;
	}

	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "create_message")
{
	$username = $_SESSION['username'];
	$caseNumber = $data->caseNumber;
	$patientName = $data->patientName;
	$ownerName = $data->ownerName;
	$category = $data->category;
	$body = $data->body;
	$recipient = $data->recipient;
	$contactMethod = $data->contactMethod;
	$urgency = $data->urgency;
	$hospital = $data->hospital;
		
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('call addMessage(?,?,?,?,?,?,?,?,?,?)');
	
	$stmt->bind_param('ssssssssss', $caseNumber,$patientName,$ownerName,$category,$body,$recipient,$contactMethod,$urgency,$username,$hospital); 
	

	$stmt->execute();
	
	$result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$MessageID = $row['MessageID'];
	}
	
	/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 3)');
	$stmt->bind_param('ss', $Message, $username); 

	$Message = 'New message created. Sent to: ' . $recipient ;
	$stmt->execute();*/
		
	$success = true;
	
	$jsonData=array();
	$jsonData['success']=$success;
	$jsonData['MessageID']=$MessageID;
 
	$conn->close();
	echo json_encode($jsonData);
 
}


else if($data->method == "claim_message")
{
	$username = $_SESSION['username'];
	$MessageID = $_SESSION['MessageID'];
		
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('call claimMessage(?, ?)');
	
	$stmt->bind_param('is', $MessageID,$username); 
	

	$stmt->execute();
	
	
	/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 3)');
	$stmt->bind_param('ss', $Message, $username); 

	$Message = 'New message created. Sent to: ' . $recipient ;
	$stmt->execute();*/
		
	$success = true;
	
	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "add_noteRoute")
{
	$username = $_SESSION['username'];
	$MessageID = $_SESSION['MessageID'];
	$Note = $data->note;
	$UrgencyLevel = $data->urgency;
	$Recipient = $data->recipient;
		
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('call addNoteWithRoute(?,?,?,?,?)');
	
	$stmt->bind_param('issss', $MessageID,$Note,$UrgencyLevel,$Recipient, $username); 
	

	$stmt->execute();
	
	
	/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 3)');
	$stmt->bind_param('ss', $Message, $username); 

	$Message = 'New message created. Sent to: ' . $recipient ;
	$stmt->execute();*/
		
	$success = true;
	
	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "add_noteComplete")
{
	$username = $_SESSION['username'];
	$MessageID = $_SESSION['MessageID'];
	$Note = $data->note;
		
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	$stmt = $conn->prepare('call addNoteComplete(?,?,?)');
	
	$stmt->bind_param('iss', $MessageID,$Note,$username); 
	

	$stmt->execute();
	
	
	/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 3)');
	$stmt->bind_param('ss', $Message, $username); 

	$Message = 'New message created. Sent to: ' . $recipient ;
	$stmt->execute();*/
		
	$success = true;
	
	$jsonData=array();
	$jsonData['success']=$success;
 
	$conn->close();
	echo json_encode($jsonData);
 
}

else if($data->method == "get_messages")
{
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('Call getMessagesForUser(?)');
	$stmt->bind_param('s', $username);
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'CreateDate' => $row['CreateDate'], 'CreatedBy' => $row['CreatedBy'], 'Subject' => $row['Subject'], 'Recipient' => $row['Recipient'], 'MessageID' => $row['MessageID'], 'Urgency' => $row['Urgency'], 'Hospital' => $row['Hospital']);
	}

	$conn->close();
	echo json_encode(array('messages' => $arr)); 
}

else if($data->method == "get_allMessages")
{
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('Call getAllMessages()');
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'CreateDate' => $row['CreateDate'], 'CreatedBy' => $row['CreatedBy'], 'Subject' => $row['Subject'], 'Recipient' => $row['Recipient'], 'Status' => $row['Status'], 'MessageID' => $row['MessageID'], 'Hospital' => $row['Hospital']);
	}

	$conn->close();
	echo json_encode(array('messages' => $arr)); 
}


else if($data->method == "get_notes")
{
	$username = $_SESSION['username'];
	$MessageID = $_SESSION['MessageID'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('Call getNotesForMessage(?)');
	$stmt->bind_param('i', $MessageID);
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'CreateDate' => $row['CreateDate'], 'CreatedBy' => $row['CreatedBy'], 'Recipient' => $row['Recipient'], 'Note' => $row['Note'], 'UrgencyLevel' => $row['UrgencyLevel']);
	}

	$conn->close();
	echo json_encode(array('notes' => $arr)); 
}

else if($data->method == "get_claimedMessages")
{
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('Call getClaimedMessagesForUser(?)');
	$stmt->bind_param('s', $username);
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'CreateDate' => $row['CreateDate'], 'CreatedBy' => $row['CreatedBy'], 'Subject' => $row['Subject'], 'Recipient' => $row['Recipient'], 'MessageID' => $row['MessageID'], 'Urgency' => $row['Urgency'], 'Hospital' => $row['Hospital']);
	}

	$conn->close();
	echo json_encode(array('messages' => $arr)); 
}

else if($data->method == "get_otherClaimedMessages")
{
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('Call getMessagesClaimedByOtherUsersInGroup(?)');
	$stmt->bind_param('s', $username);
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'CreateDate' => $row['CreateDate'], 'CreatedBy' => $row['CreatedBy'], 'Subject' => $row['Subject'], 'Recipient' => $row['Recipient'], 'MessageID' => $row['MessageID'], 'Urgency' => $row['Urgency'], 'Hospital' => $row['Hospital']);
	}

	$conn->close();
	echo json_encode(array('messages' => $arr)); 
}

else if($data->method == "set_messageID")
{
	$username = $_SESSION['username'];
	$_SESSION['MessageID'] = $data->message;
	$_SESSION['LastPage'] = $data->page;
	
	
	$success = true;
	
	$jsonData=array();
	$jsonData['success']=$success;
 
	echo json_encode($jsonData);
}

else if($data->method == "get_messageDetails")
{
	$username = $_SESSION['username'];
	$MessageID = $_SESSION['MessageID'];
	$From = $_SESSION['LastPage'];
	
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('CALL getMessageDetails(?,?)');
	$stmt->bind_param('is', $MessageID,$username); 
		
	$stmt->execute();

	
    $jsonData=array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$jsonData['Body']=$row['Body'];
		$jsonData['CaseNumber']=$row['CaseNumber'];
		$jsonData['Category']=$row['Category'];
		$jsonData['CreatedBy']=$row['CreatedBy'];
		$jsonData['CreateDate']=$row['CreateDate'];
		$jsonData['LastClaimedBy']=$row['LastClaimedBy'];
		$jsonData['OwnerContactMethod']=$row['OwnerContactMethod'];
		$jsonData['OwnerName']=$row['OwnerName'];
		$jsonData['PatientName']=$row['PatientName'];
		$jsonData['Recipient']=$row['Recipient'];
		$jsonData['UrgencyLevel']=$row['UrgencyLevel'];
		$jsonData['Status']=$row['Status'];
		$jsonData['From']=$From;
	}
	

	
	/*$stmt = $conn->prepare('Call addLogMessage(?, ?, 4)');
	$stmt->bind_param('ss', $Message, $username); 

	$Message = 'Message with case number: ' . $jsonData['CaseNumber'] . ' was viewed';
	$stmt->execute();*/

	$conn->close();
	echo json_encode($jsonData); 
	
}

else if($data->method == "get_groups")
{
	
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('CALL getUserGroups(?)');
	$stmt->bind_param('s', $username); 
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'Group' => $row['GroupName'], 'Member' => $row['Member']);
	}

	$conn->close();
	echo json_encode(array('groups' => $arr)); 
}

else if($data->method == "get_categories")
{
	
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('CALL getCategories()');
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'Category' => $row['CategoryName']);
	}

	$conn->close();
	echo json_encode(array('categories' => $arr)); 
}

else if($data->method == "get_categoryQuestions")
{
	
	$username = $_SESSION['username'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('CALL getCategoryQuestions()');
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'Category' => $row['Category'],'QuestionID' => $row['QuestionID'], 'QuestionText' => $row['QuestionText']);
	}

	$conn->close();
	echo json_encode(array('categoryQuestions' => $arr)); 
}

else if($data->method == "add_categoryQuestionAnswer")
{
	
	$username = $_SESSION['username'];
	$questionID = $data->questionID;
	$answer = $data->answer;
	$messageID = $data->messageID;
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('CALL addCategoryQuestionAnswer(?,?,?)');
	$stmt->bind_param('isi', $questionID, $answer, $messageID); 
	
	$stmt->execute();

	$success = true;
	
	
    $jsonData=array();
	$jsonData['success']=$success;

	$conn->close();
	echo json_encode($jsonData); 
}

else if($data->method == "get_categoryQuestionAnswers")
{
	
	$username = $_SESSION['username'];
	$messageID = $_SESSION['MessageID'];
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('CALL getCategoryQuestionAnswers(?)');
	$stmt->bind_param('i', $messageID); 
	
	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'QuestionText' => $row['QuestionText'],'AnswerText' => $row['AnswerText']);
	}

	$conn->close();
	echo json_encode(array('categoryQuestionAnswers' => $arr)); 
}

else if($data->method == "get_logs")
{
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('SELECT LogMessage, Username, DBCreateDate FROM LogMessages Order By DBCreateDate desc Limit 50');

	$stmt->execute();

	
    $arr = array();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) 
	{
		$arr[] = array( 'LogMessage' => $row['LogMessage'], 'User' => $row['Username'], 'CreateDate' => $row['DBCreateDate']);
	}

	$conn->close();
	echo json_encode(array('logs' => $arr)); 
}

else if($data->method == "add_note")
{
	
	$conn = new mysqli($details['server_host'], $details['mysql_name'],$details['mysql_password'], $details['mysql_database']);	
	if ($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error);
	}
	
	$stmt = $conn->prepare('SELECT Body FROM Messages');

	$stmt->execute();

	$result = $stmt->get_result();

	$jsonData=array();
	$jsonData['messages']=$result;
 
	$conn->close();
	echo json_encode($jsonData);
 
}
?>