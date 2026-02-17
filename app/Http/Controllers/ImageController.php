<?php

declare(strict_types=1);

namespace App\Http\Controllers;

class ImageController extends Controller
{
    public function displayImage($id, int $target = 800)
    {
        ini_set('memory_limit', '500M');
        header('Content-Type: image/jpeg');

        $target_height = $target;
        $target_width = $target;

        $assetBank = new AssetBankController;

        $response = $assetBank->api('assets/'.$id);
        $photo = $response['displayUrl'];
        [$width, $height] = getimagesize($photo);

        if ($width > $height) {
            $image_height = floor(($height / $width) * $target_width);
            $image_width = $target_width;
        } else {
            $image_width = floor(($width / $height) * $target_height);
            $image_height = $target_height;
        }

        $small = imagecreatetruecolor($image_width, $image_height);

        // Original Source
        $source = imagecreatefromjpeg($photo);

        // Resize
        imagecopyresampled($small, $source, 0, 0, 0, 0, $image_width, $image_height, $width, $height);

        // Display Image
        imagejpeg($small, null, 100);

        // Free up Memory
        imagedestroy($small);
    }
}
