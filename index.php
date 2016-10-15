<?php
require_once('ImageResize.php');

//example useage

if (isset($_FILES['image']) && !empty($_FILES['image'])) {
	/*$img_info = [
		"generate_image_file" 			=> 1,
		"generate_thumbnails" 			=> 1,
		"image_max_size"      			=> 204,
		"thumbnail_size"      			=> 128,
		"thumbnail_prefix"    			=> "tb_",
		"destination_folder"  			=> "./min/",
		"thumbnail_destination_folder"  => "./thumb/",
		"random_file_name"				=> 0,
		"quality"						=> 1200,
		"image_data"					=> $_FILES['image']
	];

	$im = new ImageResize($img_info);
	$danger = $im->resize();
	print_r($danger);*/
	//print_r($_FILES['image']);
	for ($i=0; $i < sizeof($_FILES['image']['name']); $i++) {
		$data = [
			"src" => $_FILES['image']['tmp_name'][$i],
			"name" =>$_FILES['image']['name'][$i]
		];
		//print_r($data);
		createthumb($data, "./thumb/tn_image".$i.".png", 128, 128, false, true, false);
	}
}

?>

<html>
	<head><title>Resize image</title></head>
	<body>
		<form action="" method="post" enctype="multipart/form-data">
		  Envoyez plusieurs fichiers : <br />
		  <input name="image[]" type="file" /><br />
		  <input name="image[]" type="file" /><br />
		  <input type="submit" value="Envoyer les fichiers" />
		</form>
	</body>
</html>

