 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
 

<?php 
include_once 'OLXscraper.php';
$page =  isset( $_GET['page'] ) ? $_GET['page'] : 1;
ini_set('max_execution_time', -1);
$db=OLXscraper::initDB();
$numberOfPages = 1;
$maxPrice='150000';
$data = OLXscraper::getOLXListData('https://olx.com.eg/en/ajax/search/list/?view=&min_id=&q=&search%5Bcity_id%5D=&search%5Bregion_id%5D=5&search%5Bdistrict_id%5D=0&search%5Bdist%5D=0&search%5Bfilter_float_price%3Ato%5D='.$maxPrice.'&search%5Bcategory_id%5D=23&page='.$page,1,$numberOfPages,true); 

echo OLXscraper::pp($data);

foreach( $data as $dataItem ) {
	$newArr =[];
	foreach($dataItem as $key => $value){
		if(!is_array($value))
			$newArr[$key]= utf8_decode( utf8_encode( $value ) );
		elseif($key == 'images' ){
			$newArr['imageList']=[];
			foreach($value as $key1 => $value1){
				$newArr['imageList'][] = ['image' => $value1];
			}
		}elseif ($key == 'attrs' ){
			$newArr['attributeList']=[];
			foreach($value as $key2 => $value2){
				$newArr['attributeList'][] = [ 'attribute'=>$key2 , 'value'=>$value2];
			}
		}
	}
	$row = $db->createRow( 'ad',$newArr );
	try{
		$db->begin();
		$row->save();
		$db->commit();	
	}
	catch(Exception $e){
		var_dump($e);
		if( $_GET['goNext'] &&  $page < $_GET['maxNext']) {
	if( $_GET['goNext'] &&  $page < $_GET['maxNext']) {
	$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
	echo  "<script>
		window.location ='" ."http://$_SERVER[HTTP_HOST]$uri_parts[0]".'?page='.($page+1) .'&maxNext='.$_GET['maxNext'].'&goNext=1\'
		</script>';
	die();
}
}
		die();
		
	}
}

if( $_GET['goNext'] &&  $page < $_GET['maxNext']) {
	$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
	echo  "<script>
		window.location ='" ."http://$_SERVER[HTTP_HOST]$uri_parts[0]".'?page='.($page+1) .'&maxNext='.$_GET['maxNext'].'&goNext=1\'
		</script>';
	die();
}
die();