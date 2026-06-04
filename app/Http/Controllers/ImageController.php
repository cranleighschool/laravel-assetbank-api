<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ImageController extends Controller
{
    public function __construct(private readonly AssetBankController $assetBank) {}

    public function displayImage(int $id, int $target = 800): Response
    {
        $response = $this->assetBank->api('assets/'.$id);
        $photo = $response['displayUrl'];

        [$width, $height] = getimagesize($photo);

        if ($width > $height) {
            $imageWidth = $target;
            $imageHeight = (int) floor(($height / $width) * $target);
        } else {
            $imageWidth = (int) floor(($width / $height) * $target);
            $imageHeight = $target;
        }

        $source = imagecreatefromjpeg($photo);
        $canvas = imagecreatetruecolor($imageWidth, $imageHeight);

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $imageWidth, $imageHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 100);
        $imageData = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return response($imageData, 200, ['Content-Type' => 'image/jpeg']);
    }
}
