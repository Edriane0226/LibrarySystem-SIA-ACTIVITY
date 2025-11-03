<?php
include "../config/connection.php";

// if (isset($_SESSION['student_id'])) {
//     $table = "staff";
// } elseif (isset($_SESSION['staff_id'])) {
//     $table = "students";
// } else {
//     header("Location: ../login.php");
//     exit();
// }

// $name = $_POST['first_name'];

// $query = "SELECT phone FROM $table WHERE first_name = ?";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("s", $name);
// $stmt->execute();
// $result = $stmt->get_result();

// $row = $result->fetch_assoc();
//     $phone = $row['phone'];

// $stmt->close();


// $pnum=$phone;
$smsgs=$_POST['msg'];

sendsmsx("09669145349","test");
function sendsmsx($pnum,$smsgs)
{
$ch =curl_init();
$url="http://192.168.1.251/default/en_US/send.html?";
$user="admin";
$pass="285952";
$ppn=substr($pnum,0,4);

switch($ppn) {  //pldt
case  "0817" : 
case  "0905" : 
case  "0906" : 
case  "0915" : 
case  "0916" : 
case  "0917" : 
case  "0926" : 
case  "0927" : 
case  "0935" : 
case  "0936" : 
case  "0937" : 
case  "0945" : 
case  "0955" :
case  "0956" :
case  "0965" :
case  "0966" :
case  "0967" :
case  "0973" :
case  "0975" :
case  "0976" :
case  "0977" :
case  "0978" :
case  "0979" :
case  "0994" :
case  "0995" : 
case  "0996" : 
case  "0997" : 
	 $line="1"; //tm globe
    break;
case "0813" :
case "0907" :
case "0908" :
case "0909" :
case "0910" :
case "0911" :
case "0912" :
case "0913" :
case "0914" :
case "0918" :
case "0919" :
case "0921" :
case "0928" :
case "0929" :
case "0930" :
case "0938" :
case "0940" :
case "0946" :
case "0947" :
case "0948" :
case "0949" :
case "0950" :
case "0951" :
case "0970" :
case "0981" :
case "0989" :
case "0992" :
case "0998" :
case "0999" :
case "0922" : 
case "0923" : 
case "0924" : 
case "0925" : 
case "0931" : 
case "0932" : 
case "0933" : 
case "0934" : 
case "0941" : 
case "0942" : 
case "0943" : 
case "0944" : 
    $line="2";  //tnt smart sun
    break;
case "0991":  //dito
case "0992":
case "0993":
case "0994":
case "0895":
case "0896":
case "0897":
case "0898":
  $line="3";
  break;
  
 default: 	
   $line="1";  
    break;
}
	
$line='1';   //temporary set to pldt
  echo "sending ".$smsgs." to ".$pnum;
$num=$pnum;
$msgs=$smsgs;
$fields=array('u'=>$user,'p'=>$pass,'l'=>$line,'n'=>$num,'m'=>$msgs);
$postvars='';
foreach($fields as $key=>$value) {  $postvars .=$key."=".$value."&" ;}
rtrim($postvars,'&');                              
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_USERPWD, "$user:$pass");
curl_setopt($ch,CURLOPT_POST, 5);
curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); //true
$response=curl_exec($ch);
//var_dump( $response);
if(!$response){ echo " unable to send..";} else { echo "sending was successful";}
curl_close($ch);

}
?>