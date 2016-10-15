<?php
//resize class
//usage
/*
$im = new ImageResize($img_info);
$danger = $im->resize();
*/
class ImageResize {
    private $generate_image_file;
    private $generate_thumbnails;
    private $image_max_size;
    private $thumbnail_size;
    private $thumbnail_prefix;
    private $destination_dir;
    private $thumbnail_destination_dir;
    private $save_dir;
    private $quality;
    private $random_file_name;
    private $image_data;
    private $file_count;
    private $image_width;
    private $image_height;
    private $image_type;
    private $image_size_info;
    private $image_res;
    private $image_scale;
    private $new_width;
    private $new_height;
    private $new_canvas;
    private $new_file_name;
    private $curr_tmp_name;
    private $x_offset = 0;
    private $y_offset = 0;
    private $resized_response;
    private $thumb_response;
    private $unique_rnd_name;
    public $response;
     
    function __construct($image_data) {
            //set local vars
            $this->generate_image_file = $image_data["generate_image_file"];
            $this->generate_thumbnails = $image_data["generate_thumbnails"];
            $this->image_max_size = $image_data["image_max_size"];
            $this->thumbnail_size = $image_data["thumbnail_size"];
            $this->thumbnail_prefix = $image_data["thumbnail_prefix"];
            $this->destination_dir = $image_data["destination_folder"];
            $this->thumbnail_destination_dir = $image_data["thumbnail_destination_folder"];
            $this->random_file_name = $image_data["random_file_name"];
            $this->quality = $image_data["quality"];
            $this->image_data = $image_data["image_data"];
            $this->file_count = count($this->image_data['name']);
    }
     
    //resize function
    public function resize(){
        if($this->generate_image_file){
            $this->response["images"] = $this->resize_it();
        }
        if($this->generate_thumbnails){
            $this->response["thumbs"] = $this->thumbnail_it();
        }
        return $this->response;
    }
     
    //proportionally resize image
    private function resize_it(){
        if($this->file_count > 0){
            if(!is_array($this->image_data['name'])){
                throw new Exception('HTML file input field must be in array format!');
            }
            for ($x = 0; $x < $this->file_count; $x++){
                 
                if ($this->image_data['error'][$x] > 0) {
                    $this->upload_error_no = $this->image_data['error'][$x];
                    throw new Exception($this->get_upload_error());
                }  
                                 
                if(is_uploaded_file($this->image_data['tmp_name'][$x])){
 
                    $this->curr_tmp_name = $this->image_data['tmp_name'][$x];
                    $this->get_image_info();
                     
                    //create unique file name
                    if($this->random_file_name){
                        $this->new_file_name = uniqid().$this->get_extension();
                        $this->unique_rnd_name[$x] = $this->new_file_name;
                    }else{
                        $this->new_file_name = $this->image_data['name'][$x];
                    }
                                         
                    $this->curr_tmp_name = $this->image_data['tmp_name'][$x];
                    $this->image_res = $this->get_image_resource();
                    $this->save_dir = $this->destination_dir;                    
                    //do not resize if image is smaller than max size
                    if($this->image_width <= $this->image_max_size || $this->image_height <= $this->image_max_size){                 
                        $this->new_width = $this->image_width;
                        $this->new_height    =  $this->image_height;                     
                        if($this->image_resampling()){
                            $this->resized_response[] = $this->save_image();
                        }
                    }else{
                        $this->image_scale   = min($this->image_max_size/$this->image_width, $this->image_max_size/$this->image_height);
                        $this->new_width = ceil($this->image_scale * $this->image_width);
                        $this->new_height    = ceil($this->image_scale * $this->image_height);
                         
                        if($this->image_resampling()){
                            $this->resized_response[] = $this->save_image();
                        }
                    }
                }
            }
        }
        return $this->resized_response;
    }
 
    //generate cropped and resized thumbnails
    private function thumbnail_it(){
        if($this->file_count > 0){
            if(!is_array($this->image_data['name'])){
                throw new Exception('HTML file input field must be in array format!');
            }
            for ($x = 0; $x < $this->file_count; $x++){
                 
                if ($this->image_data['error'][$x] > 0) {
                    $this->upload_error_no = $this->image_data['error'][$x];
                    throw new Exception($this->get_upload_error());
                }  
 
                if(is_uploaded_file($this->image_data['tmp_name'][$x])){
                    $this->curr_tmp_name = $this->image_data['tmp_name'][$x];
                    $this->get_image_info();
                     
                    if($this->random_file_name && !empty($this->unique_rnd_name)){
                        $this->new_file_name = $this->thumbnail_prefix.$this->unique_rnd_name[$x];
                    }else if($this->random_file_name){
                        $this->new_file_name = $this->thumbnail_prefix.uniqid().$this->get_extension();
                    }else{
                        $this->new_file_name = $this->thumbnail_prefix.$this->image_data['name'][$x];
                    }
                     
                    $this->image_res = $this->get_image_resource();
                     
                    $this->new_width = $this->thumbnail_size;
                    $this->new_height = $this->thumbnail_size;
                    $this->save_dir = $this->thumbnail_destination_dir;  
                     
                    if($this->image_width > $this->image_height)
                    {
                        $this->x_offset = ($this->image_width - $this->image_height) / 2;
                        $this->image_width = $this->image_height  = $this->image_width - ($this->x_offset * 2);
                    }else{
                        $this->y_offset = ($this->image_height - $this->image_width) / 2;
                        $this->image_width = $this->image_height  = $this->image_height - ($this->y_offset * 2);
                    }
                     
                    if($this->image_resampling()){
                        $this->thumb_response[] = $this->save_image();
                    }
                }
            }
        }
        return $this->thumb_response;
    }
     
    //save image to destination
    private function save_image(){
        if(!file_exists($this->save_dir)){ //try and create folder if none exist
            if(!mkdir($this->save_dir, 0755, true)){
                throw new Exception($this->save_dir . ' - directory doesn\'t exist!');
            }
        }
         
        switch($this->image_type){//determine mime type
            case 'image/png':
                imagepng($this->new_canvas, $this->save_dir.$this->new_file_name); imagedestroy($this->new_canvas); return $this->new_file_name;
                break;
            case 'image/gif':
                imagegif($this->new_canvas, $this->save_dir.$this->new_file_name); imagedestroy($this->new_canvas); return $this->new_file_name;
                break;         
            case 'image/jpeg': case 'image/pjpeg':
                imagejpeg($this->new_canvas, $this->save_dir.$this->new_file_name, $this->quality); imagedestroy($this->new_canvas); return $this->new_file_name;
                break;
            default:
                imagedestroy($this->new_canvas);
                return false;
        }
    }
     
    //get image info
    private function get_image_info(){
        $this->image_size_info   = getimagesize($this->curr_tmp_name);
        if($this->image_size_info){
            $this->image_width       = $this->image_size_info[0]; //image width
            $this->image_height  = $this->image_size_info[1]; //image height
            $this->image_type        = $this->image_size_info['mime']; //image type
        }else{
            throw new Exception("Cette image n'est pas valide !");
        }
    }  
     
    //image resample
    private function image_resampling(){
        $this->new_canvas    = imagecreatetruecolor($this->new_width, $this->new_height);
        $ext = $this->image_type;

        if($ext=="image/png") {
            // désactivation du mode blending(melange de couleur) sur l'image
            imagealphablending($this->new_canvas, false);
            // définition de la couleur transparente pour remplir l'image
            $colorTransparent = imagecolorallocatealpha($this->new_canvas, 0, 0, 0, 127);
            // remplisage de l'image avec la couleur transparente
            imagefill($this->new_canvas, 0, 0, $colorTransparent);
            // enregistrement de l'image avec l'alpha(remplissage transparent)
            imagesavealpha($this->new_canvas, true);
        } elseif($ext=="image/gif") {
            // définition de la couleur transparente et recuperation de l'index de l'image
            $trnprt_indx = imagecolortransparent($this->image_res);
            // on se ressure que le fond est bien transparent
            if ($trnprt_indx >= 0) {
                // recuperation de la couleur associée à l'index
                $trnprt_color = imagecolorsforindex($this->image_res, $trnprt_indx);
                $trnprt_indx = imagecolorallocate($this->new_canvas, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                imagefill($this->new_canvas, 0, 0, $trnprt_indx);
                imagecolortransparent($this->new_canvas, $trnprt_indx);
            }
        }

        if(imagecopyresampled($this->new_canvas, $this->image_res, 0, 0, $this->x_offset, $this->y_offset, $this->new_width, $this->new_height, $this->image_width, $this->image_height)){
            return true;
        }  
    }
     
    //create image resource
    private function get_image_resource(){
        switch($this->image_type){
            case 'image/png':
                return imagecreatefrompng($this->curr_tmp_name);
                break;
            case 'image/gif':
                return imagecreatefromgif($this->curr_tmp_name);
                break;         
            case 'image/jpeg': case 'image/pjpeg':
                return imagecreatefromjpeg($this->curr_tmp_name);
                break;
            default:
                return false;
        }
    }
     
    private function get_extension(){
           if(empty($this->image_type)) return false;
           switch($this->image_type)
           {
               case 'image/gif': return '.gif';
               case 'image/jpeg': return '.jpg';
               case 'image/png': return '.png';
               default: return false;
           }
       }
        
    private function get_upload_error(){
        switch($this->upload_error_no){
            case 1 : return 'Helpizy n\'autorise pas d\'upload un fichier aussi lourd.';
            case 2 : return 'L\'image est trop lourd.';
            case 3 : return 'L\'image a était partiellement uploader.';
            case 4 : return 'Aucun fichier uploader.';
            case 5 : return 'Impossible de lire le fichier temporaire';
            case 6 : return 'Impossible de communiquer avec Helpizy';
        }
    }
 
}

/**
* Cette function de la documentation de php (php.net)
**/
function createthumb($data, $newname, $new_w, $new_h, $border=false, $transparency=true, $base64=false) {
    if(file_exists($newname)){
        @unlink($newname);
    }
    
    $arr = explode(".",$data['name']);
    $ext = $arr[sizeof($arr)-1];

    if($ext=="jpeg" || $ext=="jpg"){
        $img = @imagecreatefromjpeg($data['src']);
    } elseif($ext=="png"){
        $img = @imagecreatefrompng($data['src']);
    } elseif($ext=="gif") {
        $img = @imagecreatefromgif($data['src']);
    }
    if(!$img)
        return false;
    $old_x = imageSX($img);
    $old_y = imageSY($img);
    if($old_x < $new_w && $old_y < $new_h) {
        $thumb_w = $old_x;
        $thumb_h = $old_y;
    } elseif ($old_x > $old_y) {
        $thumb_w = $new_w;
        $thumb_h = floor(($old_y*($new_h/$old_x)));
    } elseif ($old_x < $old_y) {
        $thumb_w = floor($old_x*($new_w/$old_y));
        $thumb_h = $new_h;
    } elseif ($old_x == $old_y) {
        $thumb_w = $new_w;
        $thumb_h = $new_h;
    }
    
    $thumb_w = ($thumb_w<1) ? 1 : $thumb_w;
    $thumb_h = ($thumb_h<1) ? 1 : $thumb_h;
    $new_img = ImageCreateTrueColor($thumb_w, $thumb_h);

    if($transparency) {
        if($ext=="png") {
            imagealphablending($new_img, false);
            $colorTransparent = imagecolorallocatealpha($new_img, 0, 0, 0, 127);
            imagefill($new_img, 0, 0, $colorTransparent);
            imagesavealpha($new_img, true);
        } elseif($ext=="gif") {
            $trnprt_indx = imagecolortransparent($img);
            if ($trnprt_indx >= 0) {
                //its transparent
                $trnprt_color = imagecolorsforindex($img, $trnprt_indx);
                $trnprt_indx = imagecolorallocate($new_img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                imagefill($new_img, 0, 0, $trnprt_indx);
                imagecolortransparent($new_img, $trnprt_indx);
            }
        }
    } else {
        Imagefill($new_img, 0, 0, imagecolorallocate($new_img, 255, 255, 255));
    }
   
    imagecopyresampled($new_img, $img, 0,0,0,0, $thumb_w, $thumb_h, $old_x, $old_y);
    if($border) {
        $black = imagecolorallocate($new_img, 0, 0, 0);
        imagerectangle($new_img,0,0, $thumb_w, $thumb_h, $black);
    }
    if($base64) {
        ob_start();
        imagepng($new_img);
        $img = ob_get_contents();
        ob_end_clean();
        $return = base64_encode($img);
    } else {
        if($ext=="jpeg" || $ext=="jpg"){
            imagejpeg($new_img, $newname);
            $return = true;
        } elseif($ext=="png"){
            imagepng($new_img, $newname);
            $return = true;
        } elseif($ext=="gif") {
            imagegif($new_img, $newname);
            $return = true;
        }
    }
    imagedestroy($new_img);
    imagedestroy($img);
    return $return;
}