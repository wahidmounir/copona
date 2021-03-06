<?php
class ControllerCommonYoutube extends Controller {

    public function index() {

        $start = microtime(true);
        /*
         * YouTube Thumbnail Enchancer by Hal Gatewood
         * License: The just-use-it license. Have fun!
         *
         * Dependances:
         * curl
         * GD Library
         * coffee
         *
         * Parameters:
         * inpt = YouTube URL or YouTube ID
         * quality = hq or mq or maxres /2017
         * refresh = skips the cache to grab a fresh one
         * play = show play button in middle
         *
         * Usage:
         * http://example.com/yt-thumb.php?quality=hq&inpt=http://www.youtube.com/watch?v=XZ4X1wcZ1GE
         * http://example.com/yt-thumb.php?quality=mq&inpt=http://www.youtube.com/watch?v=XZ4X1wcZ1GE
         * http://example.com/yt-thumb.php?quality=hq&inpt=XZ4X1wcZ1GE
         * http://example.com/yt-thumb.php?quality=mq&inpt=XZ4X1wcZ1GE
         * http://example.com/yt-thumb.php?quality=hq&inpt=XZ4X1wcZ1GE&play
         * http://example.com/yt-thumb.php?quality=hq&inpt=XZ4X1wcZ1GE&play&refresh
         *
         */
        // Youtube image file path
        $file_path = 'catalog/_media/youtube/';

        //prd(DIR_IMAGE);
        // create dir if not exist
        if(!file_exists(DIR_IMAGE . $file_path)){
            mkdir(DIR_IMAGE . 'catalog/_media/youtube/i', 0755, true);
        }

// PARAMETERS
        $is_url = false;

        $quality = in_array($this->request->request('quality'), ['hq', 'mq', 'maxres'])
            ? $this->request->request('quality')
            : 'maxres';

        $inpt = trim($this->request->request['inpt']);
        $show_play_icon = $this->request->request('play') !== null ? true : false;
        $play_btn_file_name = ($show_play_icon) ? "-play" : "";

        // prd($show_play_icon);


// ADD HTTP
        if (substr($inpt, 0, 4) == "www.") { $inpt = "http://" . $inpt;
            $is_url = true; }
        if (substr($inpt, 0, 8) == "youtube.") { $inpt = "http://" . $inpt;
            $is_url = true; }
        if (substr($inpt, 0, 8) == "youtu.be") { $inpt = "http://" . $inpt;
            $is_url = true; }

// IF URL GET ID
        if (substr($inpt, 0, 7) == "http://" OR substr($inpt, 0, 8) == "https://") {
            $is_url = true;
            $id = $this->getYouTubeIdFromURL($inpt);
        }


// IF NOT URL TRY ID AS INPUT
        if (!$is_url) { $id = $inpt; }

        // IF NOT ID GO THROUGH AN ERROR
        if (!$id) {
            header("Status: 404 Not Found");
            die("YouTube ID not found");
        }

// FILENAME
        $filename = ($quality == "mq") ? $id . "-mq" : $id;
        $filename .= $play_btn_file_name;


// IF EXISTS, GO
        /*
        pr($id);
        pr("http://img.youtube.com/vi/" . $id . "/" . $quality . "default.jpg");
        prd( microtime(true) - $start ); // */

        if (file_exists(DIR_IMAGE . $file_path . "i/" . $filename . ".png") AND !isset($this->request->get['refresh'])) {
            $this->url->getImageUrlOriginal($file_path . "i/" . $filename . ".png");

            header("Location: " . $this->url->getImageUrlOriginal($file_path . "i/" . $filename . ".png") );
            die;
        }

// CHECK IF YOUTUBE VIDEO
        $handle = curl_init("https://www.youtube.com/watch/?v=" . $id);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($handle);


// CHECK FOR 404 OR NO RESPONSE
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode == 404 OR ! $response) {
            header("Status: 404 Not Found");
            die("No YouTube video found or YouTube timed out. Try again soon.");
        }

        curl_close($handle);

// CREATE IMAGE FROM YOUTUBE THUMB

        if(!@getimagesize( "http://img.youtube.com/vi/" . $id . "/" . $quality . "default.jpg" )){
            $quality = "hq";
        }

        $image = imagecreatefromjpeg("http://img.youtube.com/vi/" . $id . "/" . $quality . "default.jpg");


// IF HIGH QUALITY WE CREATE A NEW CANVAS WITHOUT THE BLACK BARS
        if ($quality == "hq") {
            $cleft = 0;
            $ctop = 45;
            $canvas = imagecreatetruecolor(480, 270);
            imagecopy($canvas, $image, 0, 0, $cleft, $ctop, 480, 360);
            $image = $canvas;
        }


        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);



// ADD THE PLAY ICON

        $play_icon = $show_play_icon ? 'image/yt-icons/' . "play-" : 'image/yt-icons/' . "noplay-";
        $play_icon .= $quality . ".png";

        $logoImage = imagecreatefrompng($play_icon);

        imagealphablending($logoImage, true);

        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

// CENTER PLAY ICON
        $left = round($imageWidth / 2) - round($logoWidth / 2);
        $top = round($imageHeight / 2) - round($logoHeight / 2);


// CONVERT TO PNG SO WE CAN GET THAT PLAY BUTTON ON THERE
        imagecopy($image, $logoImage, $left, $top, 0, 0, $logoWidth, $logoHeight);
        imagepng($image, $filename . ".png", 9);


// MASHUP FINAL IMAGE AS A JPEG
        $input = imagecreatefrompng($filename . ".png");
        $output = imagecreatetruecolor($imageWidth, $imageHeight);
        $white = imagecolorallocate($output, 255, 255, 255);
        imagefilledrectangle($output, 0, 0, $imageWidth, $imageHeight, $white);
        imagecopy($output, $input, 0, 0, 0, 0, $imageWidth, $imageHeight);
//http://www.husis.lv/youtube/yt-thumb.php?inpt=Y_j2mCzb1es&play&refresh
// OUTPUT TO 'i' FOLDER

        $thumb = imagecreatetruecolor($this->config->get($this->config->get('config_theme') . '_image_additional_width'), $this->config->get($this->config->get('config_theme') . '_image_additional_height'));


// Resize
        imagecopyresampled($thumb, $output, 0, 0, 0, 0, $this->config->get($this->config->get('config_theme') . '_image_additional_width'), $this->config->get($this->config->get('config_theme') . '_image_additional_height'), $imageWidth, $imageHeight);
        $output = $thumb;


        imagepng($output, DIR_IMAGE .'/catalog/_media/youtube/' .
            "i/" . $filename . ".png", 5);

// UNLINK PNG VERSION
        @unlink($filename . ".png");

        pr(microtime(true) - $start );
        prd( $file_path . "i/" . $filename . ".png" );
// REDIRECT TO NEW IMAGE
        header("Location: " . $file_path . "i/" . $filename . ".png");
        die;
    }

// GET YOUTUBE ID FROM THE SLEW OF YOUTUBE URLS
// (FOUND ON STACKEXCHANGE SOMEWHERE)
    function getYouTubeIdFromURL($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
        preg_match($pattern, $url, $matches);
        return isset($matches[1]) ? $matches[1] : false;
    }

}