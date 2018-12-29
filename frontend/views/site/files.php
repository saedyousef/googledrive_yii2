<?php
use yii\helpers\Html;
?>

 <!DOCTYPE html>
 <html>
 <head>
 	<title>List of files from google  drive APIs</title>

 	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
 </head>
 <body>
 	<table class="table">
 		<th scope="col">Title</th>
 		<th scope="col">thumbnailLink</th>
 		<th scope="col">embedLink</th>
 		<th scope="col">modifiedDate</th>
 		<th scope="col">FileSize</th>
 		<th scope="col">ownerNames</th>
 		<?php 
 		$data = json_decode($files, true);
 		foreach($data as $key => $value){?>
 			<tr>
 				<td><?php echo $value['title'];?></td>
 				<td><a class="btn btn-primary" href="<?php echo $value['thumbnailLink'];?>" role="button">Thumbnail Link</a></td>
 				<td><a class="btn btn-primary" href="<?php echo $value['embedLink'];?>" role="button">Embed Link</a></td>
 				<td><?php echo $value['modifiedDate'];?></td>
 				<td><?php echo $value['fileSize'];?></td>
 				<td><?php echo $value['ownerNames'];?></td>
 			</tr>
 		<?php }?>
 	</table>
 </body>
 </html>