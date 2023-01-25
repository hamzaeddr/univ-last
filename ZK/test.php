<?php
//The socket functions described here are part of an extension to PHP which must be enabled at compile time by giving the --enable-sockets option to configure.
//Add extension=php_sockets.dll in php.ini and extension=sockets
//user defined rule 4
//super admin rule 14
//normal user 0

// Create connection
// $conn = new PDO('mysql:host=localhost;dbname=gu001','root'); 
// if ($conn) {
// echo "hamza connected";
// }
include "zklibrary.php";
echo 'Library Loaded</br>';
$zk = new ZKLibrary('172.20.0.199', 4370, 'udp');
echo 'Requesting for connection</br>';
$zk->connect();
echo 'Connected</br>';
$zk->disableDevice();
echo 'disabling device</br>';
$users = $zk->getUser();


$attendace = $zk->getAttendance();
?>
<table width="100%" border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
<thead>
  <tr>
    <td width="25">No</td>
    <td>UID</td>
    <td>ID</td>
    <td>Name</td>
    <td>Role</td>
    <td>Password</td>
  </tr>
</thead>

<tbody>
<?php
$no = 0;
foreach($users as $key=>$user)
{
  $no++;
?>

  <tr>
    <td align="right"><?php echo $no;?></td>
    <td><?php echo $key;?></td>
    <td><?php echo $user[0];?></td>
    <td><?php echo $user[1];?></td>
    <td><?php echo $user[2];?></td>
    <td><?php echo $user[3];?></td>
  </tr>

<?php
}
?>

</tbody>
</table>
<br /><br />
<table width="100%" border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
<thead>
  <tr>
    <td width="25">No</td>
    <td>UID</td>
    <td>ID</td>
    <td>State</td>
    <td>Date/Time</td>
  </tr>
</thead>

<tbody>
<?php
$no = 0;
// foreach($attendace as $key=>$at)
// {
//   $atend1 =  $at[0];
//   $atend2=  $at[1];
//   $atend3 =  $at[2];
//   $atend4 =  $at[3];


// $stmt = "INSERT INTO `checkinout`(`USERID`, `CHECKTIME`, `CHECKTYPE`,`Memoinfo`) VALUES ('" .$atend1. "','" .$atend4. "','" .$atend3. "','Hamza')";
//     $conn->exec($stmt);
//       if( $conn->exec($stmt)){
//         echo "insert succesufly";
//       }
//       else{
//         echo "insert not complited";
//       }
//     }
foreach($attendace as $key=>$at)
{
  $no++;
?>

  <tr>
    <td align="right"><?php echo $no;?></td>
    <td><?php echo $at[0];?></td>
    <td><?php echo $at[1];?></td>
    <td><?php echo $at[2];?></td>
    <td><?php echo $at[3];?></td>
  </tr>

<?php
}
?>

</tbody>
</table>
<?php

//$zk->deleteUser(2);

//$zk->clearAttendance();
//setUser($uid, $userid, $name, $password, $role)
//Reading fingerprint data
//for($i=0;$i<=9;$i++){
//$f = $zk->getUserTemplate(1,6); echo '</br>-----'; print_r($f); echo '</br>';
/*
echo 'FP length: '.$f[0].'</br>';
echo 'UID: '.$f[1].'</br>';
echo 'Finger ID: '.$f[2].'</br>';
echo 'Valid: '.$f[3].'</br>';
echo 'template: '.$f[4].'</br>';
*/

$zk->enableDevice();
$zk->disconnect();

?>
