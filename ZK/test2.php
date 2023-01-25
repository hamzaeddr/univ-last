<?php

include "zklibrary.php";
echo 'Library Loaded</br>';
$zk = new ZKLibrary('172.20.0.199', 4370, 'udp');
echo 'Requesting for connection</br>';
$zk->connect();
echo 'Connected</br>';


// $template1_data = [
//   'pin' => 123,
//   'finger_id' => 2, // First fingerprint has 0 as index.
//   'size' => 514,    // Be careful, this is not string length of $template1_vx9 var.
//   'valid' => 1,
//   'template' => $template1_vx9
// ];

 // $tad->set_user_template($template1_data,);

// $uid=6;
// $userid='14';
// $name='altoy eddr';
// $password='1234';
// $role=LEVEL_ADMIN;
// // $zk->setUser(5, '4', 'ousamamohammed', '', LEVEL_ADMIN);
// $zk->setUser($uid, $userid, $name, $password, $role);
	// $template = $zk->getUserTemplate(6,6);
	// print_r($template);

	$template1_vx9 = "ocosgoulTUEdNKVRwRQ0I27BDTEkdMEONK9KQQunMVSBK6VPLEENk9MwgQ+DP3PBC1FTXEEG4ihpQQQ3vFQBO4K+WwERYilHAQ8ztktBEBbKQ0ELDtJrwQ7dqCiBCz+/IgEGKrBjQQhEO0zBFQNDQYEKFbhrQQdLF1wBDxclfUELMNFXwQRvvmHBCslKUAEZfU1OQRzmIU5BXRW0eoEKPMltgQnQGUyBJQSfRIEUSzIdAQ45l3gBByHUTMEJ5yVhQQmi0UZBFHvYPUEGeKxTAQ6rFGNBCIYURoEOZS9VwR+1M4RoE5m0DRUTF8DHd6HdqxHAxWmj393M28DDX2FkanKi/t7LGsDCWqGarmt1BaL/25nAwVaiipu/cgcQGKG6mcDBU6KYmr5wChQcobmJIsDBUKKJmZ1uExyi+ZaYwMFMgU2CQCSinYdnJsDBR4Ghl3Q4owa3dnfAwUamdlZlR5p2Zi7AwUSndERlfOpWZlfAwUOiQzVkLDhDopRUVTLAwT2iQ0ZjIzVMolNFRcDBN6I0ZlQebVaiEjRVwMEyolVVUxVxXKEBRUTAwS+iZVYyD3JhoQJFTMDBLKJlVUIKcWShBVVTwMIkoWVkFQhyaaEVZ1rAwh6hVlUPAW+iNGd3wMIToWdlBnWiRWZ3aMDDCqRmZjRpZmrAxASjd2Vnh2/gAA==";

$template1_data = [
  'pin' => 123,
  'finger_id' => 0, // First fingerprint has 0 as index.
  'size' => 514,    // Be careful, this is not string length of $template1_vx9 var.
  'valid' => 1,
  'template' => $template1_vx9
];

$zk->setUserTemplate( $template1_data, 4);

// 	$x="ocosgoulTUEdNKVRwRQ0I27BDTEkdMEONK9KQQunMVSBK6VPLEENk9MwgQ + DP3PBC1FTXEEG4ihpQQQ3vFQBO4K + WwERYilHAQ8ztktBEBbKQ0ELDtJrwQ7dqCiBCz + / + IgEGKrBjQQhEO0zBFQNDQYEKFbhrQQdLF1wBDxclfUELMNFXwQRvvmHBCslKUAEZfU1OQRzmIU5BXRW0eoEKPMltgQnQGUyBJQSfRIEUSzIdAQ45l3gBByHUTMEJ5yVhQQmi0UZBFHvYPUEGeKxTAQ6rFGNBCIYURoEOZS9VwR 1M4RoE5m0DRUTF8DHd6HdqxHAxWmj393M28DDX2FkanKi / t7LGsDCWqGarmt1BaL / 25nAwVaiipu / cgcQGKG6mcDBU6KYmr5wChQcobmJIsDBUKKJmZ1uExyi iZVYyD3JhoQJFTMDBLKJlVUIKcWShBVVTwMIkoWVkFQhyaaEVZ1rAwh6hVlUPAW + + + ZaYwMFMgU2CQCSinYdnJsDBR4Ghl3Q4owa3dnfAwUamdlZlR5p2Zi7AwUSndERlfOpWZlfAwUOiQzVkLDhDopRUVTLAwT2iQ0ZjIzVMolNFRcDBN6I0ZlQebVaiEjRVwMEyolVVUxVxXKEBRUTAwS iNGd3wMIToWdlBnWiRWZ3aMDDCqRmZjRpZmrAxASjd2Vnh2 / GAA ==";
//  $zk->setUserTemplate($x,3);

// if($zk->setUserTemplate($x,3)){
// 	echo 'clear done';
	
// }
// else{
	
// 	echo 'clear not done</br>';
// }

$zk->disableDevice();
echo 'disabling device</br>';

//$zk->deleteUser(1);

//Set new user or update
//super admin 14
//normal user 0
//$zk->setUser(2,2,'Ahmed Ali','0000',0);
//echo 'Setting user with new data';



$zk->enableDevice();
$zk->disconnect();
?>