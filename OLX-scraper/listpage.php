<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<?php 
include_once 'OLXscraper.php';
ini_set('max_execution_time', -1);
$db=OLXscraper::initDB();


//get filters from database
if( isset(  $_POST['delete_group'] ) ){
	$filter_group = $db->filter_group();
	$filter_group = $filter_group->where('id = '.$_POST['id']);
	$filter_group = $filter_group->delete();
}
if( isset(  $_POST['delete_filter'] ) ){
	$filter = $db->filter();
	$filter = $filter->where('id = '.$_POST['id']);
	$filter = $filter->delete();
}
if( isset(  $_POST['add_filter'] ) ){
	unset( $_POST['add_filter']);
	$row = $db->createRow( 'filter',$_POST );
	try{
		$db->begin();
		$row->save(); 
		$db->commit();	
	}
	catch(Exception $e){var_dump($e);die();}
}
if( isset(  $_POST['add_group'] ) ){
	unset(  $_POST['add_group'] );
	$row = $db->createRow( 'filter_group',$_POST );
	try{
		$db->begin();
		$row->save();
		$db->commit();	
	}
	catch(Exception $e){die();}
}

$condition = ' 0=1 ';
$filterGroups = $db->filter_group();

foreach($filterGroups as $filterGroup ){
	echo '<form method="post"> '. $filterGroup->name .
		'<input type="hidden" name="id" value="'.$filterGroup->id.'" > <button type="submit" value="1" name="delete_group">X</button></form>';
	echo "<ul>";
	foreach($filterGroup->filterList() as $filter ) {
		echo '<li>';
		echo '<form method="post">'.$filter->filter_field." ".$filter->operator." ".$filter->filter_value." ".
			' <input type="hidden" name="id" value="'.$filter->id.'" > <button type="submit" value="1"  name="delete_filter">X</button></form>';
		echo '</li>';
		
	}
	echo '<form method="post">'.
			'<input type="hidden" name="filter_group_id" value="'.$filterGroup->id.'" > 
			<input type="text" name="filter_field" value="" >
			<input type="text" name="operator" value="" >
			<input type="text" name="filter_value" value="" >
			<button type="submit" value="1"  name="add_filter">Add filter</button></form>';
	echo "</ul>";

}
	echo '<form method="post">'.
			'  <input type="text" name="name" value="" >
			  <button type="submit" value="1"  name="add_group">Add group</button></form>';

$orConditionArr = [];
$andConditionArr = [];
foreach($filterGroups as $filterGroup ){
	$cond='';
	if($filterGroup->name =="all" ){
		foreach($filterGroup->filterList() as $k=>$filter ) {
			$cond.=  ($k != 0 ?" AND  ": ' ' ).$filter->filter_field." ".$filter->operator." ".$filter->filter_value." ";
		}
		if( $cond !='' )$andConditionArr[] =$cond;
	}else {
		foreach($filterGroup->filterList() as $k=>$filter ) {
			$cond.=  ($k != 0 ?" AND  ": ' ' ).$filter->filter_field." ".$filter->operator." ".$filter->filter_value." ";
		}
		if( $cond !='' )$orConditionArr[] =$cond;
	}
	
	
}
foreach($orConditionArr as $k => $conditionOR ){
	$condition .= " OR  ". " (";
	$condition .=$conditionOR;
	$condition .= " ) ";
}
$conditionAnd  = ' 1=1 ';
foreach($andConditionArr as $k => $conditionAnd ){
	$conditionAndText .= " AND  ". " (";
	$conditionAndText .=$conditionAnd;
	$conditionAndText .= " ) ";
}
if( isset( $conditionAnd )) {
	$condition  = '('.$condition . ')'. $conditionAndText;
}

//run query
var_dump($condition);
$ads = $db->ad();
$ads = $ads->where($condition);
$ads = $ads->orderBy( 'ad.id', 'DESC' )->limit(100);
?>
	
	
	
<style type="text/css">
	table.tableizer-table {
		font-size: 19px;
		border: 1px solid #CCC; 
		font-family: Arial, Helvetica, sans-serif;
	} 
	.tableizer-table td {
		padding: 4px;
		margin: 3px;
		border: 1px solid #CCC;
	}
	.tableizer-table th {
		background-color: #104E8B; 
		color: #FFF;
		font-weight: bold;
	}
</style>
<table class="tableizer-table">
    <thead>
        <tr>
        <th>key </th>
        	<th>Summery </th>
        	<th>Ad Name</th>
        	<th>price</th>
         	<th>images</th>
            <th>Location</th>
            <th>phone</th>
            <th>url</th>
            <th>description</th>
            <th>atrributes</th>
			<th>created at</th>
        </tr>
    </thead>
    <tbody>
    	<?php foreach ( $ads as $k=>$ad ) { ?>
        <tr><td><?php echo $ad->id; ?></td>
        <td style="width:250px">
        <?php foreach ( $ad->attributeList() as $key =>  $attribute ) {
        	if( $attribute->attribute == 'موديل' )
					echo $attribute->value .'  ' ;
        	if( $attribute->attribute == 'السنة' )
					echo $attribute->value .'  ' ;
			}?></td>
			 <td><?php echo $ad->title ; ?></td>
			 <td><?php echo $ad->price ; ?></td>
        	<td><?php foreach ( $ad->imageList() as $key => $image ) {
					if( $key < 2 )echo '<img src = '.$image->image.' style="height:100px">';
			}?></td>
            <td><?php echo $ad->location ; ?></td>
            
            <td><?php echo $ad->phone ; ?></td>
            <td><?php echo $ad->url ; ?></td>
            <td><?php echo $ad->description ; ?></td>
            <td style="width:250px"><?php foreach ( $ad->attributeList() as $key =>  $attribute ) {
					echo '<b>'.$attribute->attribute .'</b>:'. $attribute->value .' <br> ' ;
			}?></td>
            <td><?php echo $ad->created_at ; ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
